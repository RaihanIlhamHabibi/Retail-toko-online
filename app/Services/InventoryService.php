<?php

namespace App\Services;

use App\Models\ApiOrder;
use App\Models\ApiOrderItem;
use App\Models\ApiProduct;
use App\Models\InventoryLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    private const MAX_AMOUNT_CENTS = 999_999_999_999;

    private const TRANSITIONS = [
        'pending' => ['payment_pending', 'cancelled'],
        'payment_pending' => ['paid', 'cancelled'],
        'paid' => ['packed', 'cancelled', 'refunded'],
        'packed' => ['shipped'],
        'shipped' => ['delivered'],
        'delivered' => [],
        'cancelled' => ['refunded'],
        'refunded' => [],
    ];

    public function createOrder(User $user, array $requestedItems): ApiOrder
    {
        return DB::transaction(function () use ($user, $requestedItems) {
            $quantities = collect($requestedItems)->keyBy('product_id');
            $products = ApiProduct::query()
                ->whereIn('id', $quantities->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $quantities->count()) {
                throw ValidationException::withMessages([
                    'items' => ['One or more products are unavailable.'],
                ]);
            }

            $totalCents = 0;
            $order = new ApiOrder([
                'order_number' => (string) Str::ulid(),
                'user_id' => $user->getKey(),
                'status' => 'pending',
                'currency' => 'IDR',
                'total' => '0.00',
            ]);
            $order->save();

            foreach ($products as $product) {
                $quantity = (int) $quantities[$product->getKey()]['quantity'];

                if (! $product->is_active || $product->stock_quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => ["Insufficient stock or inactive product: {$product->name}."],
                    ]);
                }

                $unitPriceCents = $this->toCents((string) $product->price);
                $subtotalCents = $unitPriceCents * $quantity;
                $totalCents += $subtotalCents;

                if ($subtotalCents > self::MAX_AMOUNT_CENTS || $totalCents > self::MAX_AMOUNT_CENTS) {
                    throw ValidationException::withMessages([
                        'items' => ['The order total exceeds the supported amount.'],
                    ]);
                }

                $product->decrement('stock_quantity', $quantity);

                $order->items()->save(new ApiOrderItem([
                    'product_id' => $product->getKey(),
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $this->toAmount($unitPriceCents),
                    'subtotal' => $this->toAmount($subtotalCents),
                ]));

                $this->logInventory(
                    $product,
                    $order,
                    $user,
                    'reserve',
                    $quantity,
                    -$quantity,
                    $product->stock_quantity,
                    'Stock reserved for order'
                );
            }

            $order->forceFill(['total' => $this->toAmount($totalCents)])->save();

            return $order->load('items');
        }, 3);
    }

    public function transition(ApiOrder $order, string $nextStatus, User $actor): ApiOrder
    {
        return DB::transaction(function () use ($order, $nextStatus, $actor) {
            $lockedOrder = ApiOrder::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $allowed = self::TRANSITIONS[$lockedOrder->status] ?? [];

            if (! in_array($nextStatus, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => ["Order cannot transition from {$lockedOrder->status} to {$nextStatus}."],
                ]);
            }

            $items = $lockedOrder->items()->orderBy('product_id')->get();

            if ($nextStatus === 'cancelled') {
                $products = ApiProduct::query()
                    ->whereIn('id', $items->pluck('product_id'))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($items as $item) {
                    $product = $products->get($item->product_id);

                    if (! $product) {
                        throw ValidationException::withMessages([
                            'order' => ['A product for this order no longer exists.'],
                        ]);
                    }

                    $product->increment('stock_quantity', $item->quantity);
                    $this->logInventory(
                        $product,
                        $lockedOrder,
                        $actor,
                        'release',
                        $item->quantity,
                        $item->quantity,
                        $product->stock_quantity,
                        'Reserved stock released after cancellation'
                    );
                }
            } elseif ($lockedOrder->status === 'payment_pending' && $nextStatus === 'paid') {
                $products = ApiProduct::query()
                    ->whereIn('id', $items->pluck('product_id'))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($items as $item) {
                    $product = $products->get($item->product_id);

                    if (! $product) {
                        throw ValidationException::withMessages([
                            'order' => ['A product for this order no longer exists.'],
                        ]);
                    }

                    $this->logInventory(
                        $product,
                        $lockedOrder,
                        $actor,
                        'confirm',
                        $item->quantity,
                        0,
                        $product->stock_quantity,
                        'Payment confirmed; reserved stock retained'
                    );
                }
            }

            $lockedOrder->forceFill(['status' => $nextStatus])->save();

            return $lockedOrder->load('items');
        }, 3);
    }

    public function restock(ApiProduct $product, int $quantity, User $actor, ?string $note): ApiProduct
    {
        return DB::transaction(function () use ($product, $quantity, $actor, $note) {
            $lockedProduct = ApiProduct::query()
                ->whereKey($product->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedProduct->stock_quantity > 4_294_967_295 - $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['The restock quantity exceeds the supported stock amount.'],
                ]);
            }

            $lockedProduct->increment('stock_quantity', $quantity);

            $this->logInventory(
                $lockedProduct,
                null,
                $actor,
                'restock',
                $quantity,
                $quantity,
                $lockedProduct->stock_quantity,
                $note
            );

            return $lockedProduct->refresh();
        }, 3);
    }

    private function logInventory(
        ApiProduct $product,
        ?ApiOrder $order,
        User $actor,
        string $action,
        int $quantity,
        int $quantityChange,
        int $stockAfter,
        ?string $note
    ): void {
        InventoryLog::query()->create([
            'product_id' => $product->getKey(),
            'order_id' => $order?->getKey(),
            'user_id' => $actor->getKey(),
            'action' => $action,
            'quantity' => $quantity,
            'quantity_change' => $quantityChange,
            'stock_after' => $stockAfter,
            'note' => $note,
        ]);
    }

    private function toCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function toAmount(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
