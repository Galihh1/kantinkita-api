<?php
namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class AutoBackupDatabase extends Command
{
    protected $signature   = 'backup:auto {--keep=30 : Days to keep backups}';
    protected $description = 'Automatically backup the database and clean old ones';

    public function __construct(private BackupService $backupService) { parent::__construct(); }

    public function handle(): int
    {
        $this->info('🔄 Starting auto backup...');
        try {
            $filename = $this->backupService->create();
            $this->info("✅ Backup created: {$filename}");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Backup failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
