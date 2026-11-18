<?php

namespace Tests\Feature;

use App\Models\ApiProduct;
use App\Models\InventoryLog;
use App\Models\User;
use App\Services\JwtTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'commerce.jwt_secret' => 'test-only-secret-key-that-is-at-least-32-characters-long',
            'commerce.jwt_issuer' => 'http://localhost',
        ]);
    }

    public function test_public_catalog_searches_active_products_and_hides_inactive_products(): void
    {
        ApiProduct::query()->create([
            'name' => 'Kopi Pagi',
            'description' => 'Arabika',
            'price' => '65000.00',
            'stock_quantity' => 3,
            'category' => 'Minuman',
            'is_active' => true,
        ]);
        ApiProduct::query()->create([
            'name' => 'Kopi Arsip',
            'description' => 'Tidak dijual',
            'price' => '1.00',
            'stock_quantity' => 1,
            'category' => 'Minuman',
            'is_active' => false,
        ]);

        $this->getJson('/api/products?search=Kopi&category=Minuman')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.name', 'Kopi Pagi');
    }

    public function test_checkout_reserves_stock_and_writes_an_audit_log(): void
    {
        $user = User::factory()->create();
        $product = $this->product(stock: 3);

        $response = $this->withToken($this->tokenFor($user))->postJson('/api/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('total', '258000.00');
        $this->assertSame(1, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_logs', [
            'product_id' => $product->id,
            'action' => 'reserve',
            'quantity_change' => -2,
            'stock_after' => 1,
        ]);
    }

    public function test_checkout_with_insufficient_stock_rolls_back_order_stock_and_logs(): void
    {
        $user = User::factory()->create();
        $product = $this->product(stock: 1);

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/orders', [
                'items' => [['product_id' => $product->id, 'quantity' => 2]],
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('inventory_logs', 0);
        $this->assertSame(1, $product->fresh()->stock_quantity);
    }

    public function test_user_cannot_read_another_users_order(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = $this->product(stock: 2);
        $order = app(\App\Services\InventoryService::class)->createOrder($owner, [
            ['product_id' => $product->id, 'quantity' => 1],
        ]);

        $this->withToken($this->tokenFor($otherUser))
            ->getJson("/api/orders/{$order->id}")
            ->assertNotFound();
    }

    public function test_admin_can_list_all_orders_but_regular_user_only_sees_owned_orders(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['api_role' => 'admin'])->save();
        $product = $this->product(stock: 4);
        $inventory = app(\App\Services\InventoryService::class);

        $inventory->createOrder($owner, [
            ['product_id' => $product->id, 'quantity' => 1],
        ]);
        $inventory->createOrder($otherUser, [
            ['product_id' => $product->id, 'quantity' => 1],
        ]);

        $this->withToken($this->tokenFor($admin))
            ->getJson('/api/orders')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withToken($this->tokenFor($owner))
            ->getJson('/api/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_id', $owner->id);
    }

    public function test_payment_confirmation_preserves_reserved_stock_and_forbidden_transitions_fail(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['api_role' => 'admin'])->save();
        $product = $this->product(stock: 2);
        $order = app(\App\Services\InventoryService::class)->createOrder($user, [
            ['product_id' => $product->id, 'quantity' => 1],
        ]);
        $userToken = $this->tokenFor($user);

        $this->withToken($userToken)
            ->postJson("/api/orders/{$order->id}/payment")
            ->assertOk()
            ->assertJsonPath('status', 'payment_pending');

        $this->withToken($this->tokenFor($admin))
            ->patchJson("/api/orders/{$order->id}/status", ['status' => 'paid'])
            ->assertOk()
            ->assertJsonPath('status', 'paid');

        $this->assertSame(1, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_logs', [
            'order_id' => $order->id,
            'action' => 'confirm',
            'quantity_change' => 0,
            'stock_after' => 1,
        ]);

        $adminToken = $this->tokenFor($admin);
        $this->withToken($adminToken)
            ->patchJson("/api/orders/{$order->id}/status", ['status' => 'packed'])
            ->assertOk()
            ->assertJsonPath('status', 'packed');
        $this->withToken($adminToken)
            ->patchJson("/api/orders/{$order->id}/status", ['status' => 'shipped'])
            ->assertOk()
            ->assertJsonPath('status', 'shipped');

        $this->withToken($userToken)
            ->postJson("/api/orders/{$order->id}/cancel")
            ->assertUnprocessable();
    }

    public function test_cancellation_releases_reserved_stock_once(): void
    {
        $user = User::factory()->create();
        $product = $this->product(stock: 2);
        $order = app(\App\Services\InventoryService::class)->createOrder($user, [
            ['product_id' => $product->id, 'quantity' => 1],
        ]);
        $inventory = app(\App\Services\InventoryService::class);

        $inventory->transition($order, 'cancelled', $user);
        $this->assertSame(2, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_logs', [
            'order_id' => $order->id,
            'action' => 'release',
            'quantity_change' => 1,
            'stock_after' => 2,
        ]);
    }

    public function test_admin_restock_is_role_protected_and_audited(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['api_role' => 'admin'])->save();
        $product = $this->product(stock: 2);

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/inventory/restock', [
                'product_id' => $product->id,
                'quantity' => 5,
            ])
            ->assertForbidden();

        $this->withToken($this->tokenFor($admin))
            ->postJson('/api/inventory/restock', [
                'product_id' => $product->id,
                'quantity' => 5,
                'note' => 'Penerimaan barang',
            ])
            ->assertOk()
            ->assertJsonPath('stock_quantity', 7);

        $this->assertSame(7, $product->fresh()->stock_quantity);
        $this->assertSame(1, InventoryLog::query()->where('action', 'restock')->count());
    }

    public function test_logout_revokes_the_jwt(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
        $this->withToken($token)->getJson('/api/orders')->assertUnauthorized();
    }

    private function product(int $stock): ApiProduct
    {
        return ApiProduct::query()->create([
            'name' => 'Tumbler',
            'description' => 'Demo',
            'price' => '129000.00',
            'stock_quantity' => $stock,
            'category' => 'Lifestyle',
            'is_active' => true,
        ]);
    }

    private function tokenFor(User $user): string
    {
        return app(JwtTokenService::class)->issue($user)['access_token'];
    }
}
