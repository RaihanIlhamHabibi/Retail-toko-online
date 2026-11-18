<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiOrder;
use App\Models\ApiProduct;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class InventoryController extends Controller
{
    public function restock(Request $request, InventoryService $inventory): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $product = ApiProduct::query()->findOrFail($data['product_id']);

        return response()->json($inventory->restock(
            $product,
            $data['quantity'],
            $request->user(),
            $data['note'] ?? null
        ));
    }

    public function lowStock(Request $request): JsonResponse
    {
        $data = $request->validate([
            'threshold' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);
        $threshold = $data['threshold'] ?? config('commerce.low_stock_threshold');

        return response()->json([
            'threshold' => $threshold,
            'data' => ApiProduct::query()
                ->where('is_active', true)
                ->where('stock_quantity', '<=', $threshold)
                ->orderBy('stock_quantity')
                ->get(),
        ]);
    }

    public function dailySales(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $date = $data['date'] ?? Carbon::today()->toDateString();
        $paidStatuses = ['paid', 'packed', 'shipped', 'delivered'];
        $sales = ApiOrder::query()
            ->whereDate('created_at', $date)
            ->whereIn('status', $paidStatuses);

        return response()->json([
            'date' => $date,
            'currency' => 'IDR',
            'order_count' => (clone $sales)->count(),
            'total' => (string) $sales->sum('total'),
        ]);
    }
}
