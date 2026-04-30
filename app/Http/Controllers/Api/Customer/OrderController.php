<?php
namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $orders = Order::with(['items', 'tenant', 'payment'])
            ->where('user_id', $request->user()->id)
            ->whereNotIn('status', ['cart'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10);

        return $this->success($orders);
    }

    public function show(Request $request, int $id)
    {
        $order = Order::with(['items.menu', 'tenant', 'payment', 'user'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return $this->success($order);
    }
}
