<?php
namespace App\Services;

use App\Models\ActivityLog;
use Carbon\Carbon;

class BackupService
{
    private string $backupPath;

    public function __construct()
    {
        $this->backupPath = 'backups';
    }

    public function create(): string
    {
        $filename = 'backup_' . now()->format('Ymd_His') . '.sql';
        $dir      = storage_path('app/' . $this->backupPath);
        $fullPath = $dir . '/' . $filename;

        if (!file_exists($dir)) mkdir($dir, 0755, true);

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s 2>&1',
            escapeshellarg(config('database.connections.mysql.host')),
            escapeshellarg(config('database.connections.mysql.port')),
            escapeshellarg(config('database.connections.mysql.username')),
            escapeshellarg(config('database.connections.mysql.password')),
            escapeshellarg(config('database.connections.mysql.database')),
            escapeshellarg($fullPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Backup gagal: ' . implode("\n", $output));
        }

        $this->cleanOldBackups();
        ActivityLog::record('backup', "Backup database berhasil: {$filename}");

        return $filename;
    }

    public function restore(string $filename): void
    {
        $fullPath = storage_path('app/' . $this->backupPath . '/' . $filename);

        if (!file_exists($fullPath)) {
            throw new \Exception('File backup tidak ditemukan.');
        }

        $command = sprintf(
            'mysql --host=%s --port=%s --user=%s --password=%s %s < %s 2>&1',
            escapeshellarg(config('database.connections.mysql.host')),
            escapeshellarg(config('database.connections.mysql.port')),
            escapeshellarg(config('database.connections.mysql.username')),
            escapeshellarg(config('database.connections.mysql.password')),
            escapeshellarg(config('database.connections.mysql.database')),
            escapeshellarg($fullPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Restore gagal: ' . implode("\n", $output));
        }

        ActivityLog::record('backup', "Restore database berhasil dari: {$filename}");
    }

    public function list(): array
    {
        $path  = storage_path('app/' . $this->backupPath);
        $files = [];

        if (!file_exists($path)) return [];

        foreach (glob($path . '/*.sql') as $file) {
            $files[] = [
                'filename'   => basename($file),
                'size'       => filesize($file),
                'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                'type'       => str_contains(basename($file), 'auto') ? 'auto' : 'manual',
            ];
        }

        usort($files, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        return $files;
    }

    public function delete(string $filename): void
    {
        // Sanitize filename - no path traversal
        $filename = basename($filename);
        $fullPath = storage_path('app/' . $this->backupPath . '/' . $filename);

        if (!file_exists($fullPath)) {
            throw new \Exception('File backup tidak ditemukan.');
        }

        unlink($fullPath);
        ActivityLog::record('backup', "Backup dihapus: {$filename}");
    }

    private function cleanOldBackups(): void
    {
        $path     = storage_path('app/' . $this->backupPath);
        $keepDays = 30;

        if (!file_exists($path)) return;

        foreach (glob($path . '/*.sql') as $file) {
            if (Carbon::createFromTimestamp(filemtime($file))->diffInDays(now()) > $keepDays) {
                unlink($file);
            }
        }
    }
}
