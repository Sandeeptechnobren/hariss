<?php

namespace App\Http\Controllers\V1\EfrisAPI;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\V1\EfrisAPI\UraInvoiceService;
use App\Models\InvoiceHeader;

class UraInvoiceController extends Controller
{
    protected $service;

    public function __construct(UraInvoiceService $service)
    {
        $this->service = $service;
    }

    // public function sync(Request $request)
    // {
    //     try {

    //         $request->validate([
    //             'invoice_ids' => 'required'
    //         ]);

    //         $ids = [];

    //         if ($request->invoice_ids === 'all') {

    //             $ids = InvoiceHeader::whereNull('ura_invoice_id')
    //                 ->where('status', 1)
    //                 ->pluck('id')
    //                 ->toArray();
    //         } elseif (is_array($request->invoice_ids)) {

    //             $ids = array_map('intval', $request->invoice_ids);
    //         } else {

    //             $ids = [(int) $request->invoice_ids];
    //         }

    //         if (empty($ids)) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'No invoices found for sync'
    //             ]);
    //         }

    //         $results = [];
    //         $successCount = 0;
    //         $failCount = 0;
    //         // dd($ids);
    //         foreach ($ids as $id) {

    //             try {

    //                 $response = $this->service->syncInvoice($id);
    //                 // dd($response);
    //                 if ($response['success'] ?? false) {
    //                     $successCount++;
    //                 } else {
    //                     $failCount++;
    //                 }

    //                 $results[] = [
    //                     'invoice_id' => $id,
    //                     'success' => $response['success'] ?? false,
    //                     'message' => $response['message'] ?? '',
    //                     'returnCode' => $response['returnCode'] ?? null,
    //                     'inner_response' => $response['inner_response'] ?? null
    //                 ];
    //             } catch (\Throwable $e) {

    //                 $failCount++;

    //                 \Log::error('Invoice Sync Failed', [
    //                     'invoice_id' => $id,
    //                     'message' => $e->getMessage()
    //                 ]);

    //                 $results[] = [
    //                     'invoice_id' => $id,
    //                     'success' => false,
    //                     'message' => $e->getMessage()
    //                 ];
    //             }
    //         }

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Invoice sync completed',
    //             'total' => count($ids),
    //             'success_count' => $successCount,
    //             'fail_count' => $failCount,
    //             'data' => $results
    //         ]);
    //     } catch (\Throwable $e) {

    //         return response()->json([
    //             'status' => false,
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }


    public function sync(Request $request)
    {
        try {

            $request->validate([
                'invoice_ids' => 'required'
            ]);

            $ids = [];

            // 🔥 HANDLE IDS
            if ($request->invoice_ids === 'all') {

                $ids = InvoiceHeader::whereNull('ura_invoice_id')
                    ->where('status', 1)
                    ->pluck('id')
                    ->toArray();
            } elseif (is_array($request->invoice_ids)) {

                $ids = array_map('intval', $request->invoice_ids);
            } else {

                $ids = [(int) $request->invoice_ids];
            }

            if (empty($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No invoices found for sync'
                ], 422);
            }

            $results = [];
            $successCount = 0;
            $failCount = 0;

            foreach ($ids as $id) {

                try {

                    $response = $this->service->syncInvoice($id);

                    // 🔥 STRICT EFRIS CHECK
                    $isSuccess =
                        ($response['returnCode'] ?? null) === "00" &&
                        strtoupper($response['message'] ?? '') === "SUCCESS";

                    if ($isSuccess) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }

                    $results[] = [
                        'invoice_id' => $id,
                        'success' => $isSuccess,
                        'message' => $response['message'] ?? '',
                        'returnCode' => $response['returnCode'] ?? null,
                        'inner_response' => $response['inner_response'] ?? null
                    ];
                } catch (\Throwable $e) {

                    $failCount++;

                    \Log::error('Invoice Sync Failed', [
                        'invoice_id' => $id,
                        'error' => $e->getMessage()
                    ]);

                    $results[] = [
                        'invoice_id' => $id,
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
            }

            // 🔥 FINAL STATUS DECISION
            $status = $successCount > 0;

            return response()->json([
                'status' => $status,
                'message' => $status
                    ? 'Invoice sync completed'
                    : 'All invoice sync failed',
                'total' => count($ids),
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'data' => $results
            ], $status ? 200 : 422);
        } catch (\Throwable $e) {

            \Log::error('Invoice Sync Fatal Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function getInvoices(Request $request)
    {
        try {
            $data = $this->service->getInvoices(
                $request->all()
            );

            return response()->json([
                'success' => true,
                'message' => 'Invoices fetched successfully',
                'data' => $data
            ]);
        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
