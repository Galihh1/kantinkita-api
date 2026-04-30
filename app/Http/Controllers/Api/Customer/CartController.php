<?php
namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $cart = $this->getActiveCart($request->user()->id);

        if (!$cart) {
            return $this->success(['items' => [], 'tenant' => null, 'total' => 0, 'item_count' => 0]);
        }

        $cart->load(['items.menu.category', 'tenant']);

        return $this->success([
            'order_id'   => $cart->id,
            'tenant'     => $cart->tenant,
            'items'      => $cart->items,
            'total'      => $cart->total_amount,
            'item_count' => $cart->items->sum('quantity'),
        ]);
    }

    public function add(Request $request)
    {
        $request->validate(['menu_id' => 'required|exists:menus,id', 'quantity' => 'required|integer|min:1|max:99']);

        $menu = Menu::available()->findOrFail($request->menu_id);

        DB::beginTransaction();
        try {
            $cart = $this->getActiveCart($request->user()->id);

            if ($cart && $cart->tenant_id !== $menu->tenant_id) {
                return $this->error('Keranjang berisi item dari tenant lain. Kosongkan keranjang terlebih dahulu.', 422);
            }

            if (!$cart) {
                $cart = Order::create([
                    'order_number' => 'CART-' . $request->user()->id . '-' . time(),
                    'user_id'      => $request->user()->id,
                    'tenant_id'    => $menu->tenant_id,
                    'status'       => 'cart',
                    'total_amount' => 0, 'service_fee' => 0, 'grand_total' => 0,
                    'company_code' => 'UNIV',
                ]);
            }

            $existingItem = $cart->items()->where('menu_id', $menu->id)->first();

            if ($existingItem) {
                $newQty = $existingItem->quantity + $request->quantity;
                $existingItem->update(['quantity' => $newQty, 'subtotal' => $newQty * $menu->price]);
            } else {
                $cart->items()->create([
                    'menu_id'      => $menu->id,
                    'menu_name'    => $menu->name,
                    'price'        => $menu->price,
                    'quantity'     => $request->quantity,
                    'subtotal'     => $menu->price * $request->quantity,
                    'company_code' => 'UNIV',
                ]);
            }

            $this->updateCartTotal($cart);
            DB::commit();

            return $this->success($cart->fresh(['items', 'tenant']), 'Item berhasil ditambahkan ke keranjang');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Request $request, int $id)
    {
        $request->validate(['quantity' => 'required|integer|min:1|max:99']);

        $item = OrderItem::findOrFail($id);
        $cart = Order::where('id', $item->order_id)->where('user_id', $request->user()->id)->where('status', 'cart')->firstOrFail();

        $item->update(['quantity' => $request->quantity, 'subtotal' => $item->price * $request->quantity]);
        $this->updateCartTotal($cart);

        return $this->success($cart->fresh(['items', 'tenant']), 'Keranjang berhasil diperbarui');
    }

    public function remove(Request $request, int $id)
    {
        $item = OrderItem::findOrFail($id);
        $cart = Order::where('id', $item->order_id)->where('user_id', $request->user()->id)->where('status', 'cart')->firstOrFail();

        $item->delete();
        $this->updateCartTotal($cart);

        if ($cart->items()->count() === 0) {
            $cart->delete();
            return $this->success(null, 'Item dihapus, keranjang kosong');
        }

        return $this->success($cart->fresh(['items', 'tenant']), 'Item berhasil dihapus');
    }

    public function clear(Request $request)
    {
        $cart = $this->getActiveCart($request->user()->id);
        if ($cart) { $cart->items()->delete(); $cart->delete(); }

        return $this->success(null, 'Keranjang berhasil dikosongkan');
    }

    private function getActiveCart(int $userId): ?Order
    {
        return Order::where('user_id', $userId)->where('status', 'cart')->latest()->first();
    }

    private function updateCartTotal(Order $cart): void
    {
        $total = $cart->items()->sum('subtotal');
        $cart->update(['total_amount' => $total, 'grand_total' => $total]);
    }
}
