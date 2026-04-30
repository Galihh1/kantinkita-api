<?php
namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewOrderReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("tenant.{$this->order->tenant_id}")];
    }

    public function broadcastAs(): string { return 'NewOrderReceived'; }

    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id'           => $this->order->id,
                'order_number' => $this->order->order_number,
                'status'       => $this->order->status,
                'grand_total'  => $this->order->grand_total,
                'notes'        => $this->order->notes,
                'created_at'   => $this->order->created_at,
                'user'         => [
                    'id'        => $this->order->user->id,
                    'full_name' => $this->order->user->full_name,
                    'phone'     => $this->order->user->phone,
                ],
                'items' => $this->order->items->map(fn($item) => [
                    'id'        => $item->id,
                    'menu_name' => $item->menu_name,
                    'price'     => $item->price,
                    'quantity'  => $item->quantity,
                    'subtotal'  => $item->subtotal,
                ]),
            ],
        ];
    }
}
