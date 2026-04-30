<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    use ApiResponse;

    public function __construct(private BackupService $backupService) {}

    public function index()
    {
        return $this->success(['data' => $this->backupService->list()]);
    }

    public function create()
    {
        try {
            $filename = $this->backupService->create();
            return $this->success(['filename' => $filename], 'Backup berhasil dibuat', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function restore(Request $request)
    {
        $request->validate(['filename' => 'required|string']);
        try {
            $this->backupService->restore($request->filename);
            return $this->success(null, 'Restore berhasil');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy(string $filename)
    {
        try {
            $this->backupService->delete($filename);
            return $this->success(null, 'Backup berhasil dihapus');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function download(string $filename)
    {
        $filename = basename($filename);
        $path     = storage_path('app/backups/' . $filename);

        if (!file_exists($path)) {
            return $this->error('File tidak ditemukan.', 404);
        }

        return response()->download($path);
    }
}
