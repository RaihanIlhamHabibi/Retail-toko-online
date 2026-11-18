<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiOrder;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = ApiOrder::query();

        if ($request->user()->api_role !== 'admin') {
            $query->where('user_id', $request->user()->getKey());
        }

        $orders = $query
            ->with('items')
            ->latest('id')
            ->paginate($filters['per_page'] ?? 20);

        return response()->json($orders);
    }

    public function store(Request $request, InventoryService $inventory): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $order = $inventory->createOrder($request->user(), $data['items']);

        return response()->json($order, 201);
    }

    public function show(Request $request, int $order): JsonResponse
    {
        return response()->json($this->visibleOrder($request, $order)->load('items'));
    }

    public function startPayment(Request $request, int $order, InventoryService $inventory): JsonResponse
    {
        $order = $this->visibleOrder($request, $order);

        return response()->json($inventory->transition($order, 'payment_pending', $request->user()));
    }

    public function cancel(Request $request, int $order, InventoryService $inventory): JsonResponse
    {
        $order = $this->visibleOrder($request, $order);

        return response()->json($inventory->transition($order, 'cancelled', $request->user()));
    }

    public function updateStatus(Request $request, int $order, InventoryService $inventory): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:payment_pending,paid,packed,shipped,delivered,cancelled,refunded'],
        ]);
        $model = ApiOrder::query()->findOrFail($order);

        return response()->json($inventory->transition($model, $data['status'], $request->user()));
    }

    private function visibleOrder(Request $request, int $order): ApiOrder
    {
        $query = ApiOrder::query();

        if ($request->user()->api_role !== 'admin') {
            $query->where('user_id', $request->user()->getKey());
        }

        return $query->findOrFail($order);
    }
}
