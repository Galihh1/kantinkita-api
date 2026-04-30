<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MidtransService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(private MidtransService $midtrans) {}

    public function notification(Request $request)
    {
        Log::info('Midtrans notification received', $request->all());

        try {
            $payload = $request->all();

            if (isset($payload['signature_key'])) {
                $isValid = $this->midtrans->verifySignature(
                    $payload['order_id'] ?? '',
                    $payload['status_code'] ?? '',
                    $payload['gross_amount'] ?? '',
                    $payload['signature_key']
                );

                if (!$isValid) {
                    Log::warning('Invalid Midtrans signature', ['order_id' => $payload['order_id'] ?? '']);
                    return response()->json(['status' => 'invalid_signature'], 400);
                }
            }

            $this->midtrans->processNotification($payload);

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Midtrans notification error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }
}
