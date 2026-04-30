<?php
namespace App\Console\Commands;

use App\Services\OrderService;
use Illuminate\Console\Command;

class CancelExpiredOrders extends Command
{
    protected $signature   = 'orders:cancel-expired';
    protected $description = 'Cancel all expired pending payment orders';

    public function __construct(private OrderService $orderService) { parent::__construct(); }

    public function handle(): int
    {
        $this->info('🔄 Checking expired orders...');
        $count = $this->orderService->cancelExpiredOrders();
        $this->info("✅ {$count} expired order(s) cancelled.");
        return Command::SUCCESS;
    }
}
