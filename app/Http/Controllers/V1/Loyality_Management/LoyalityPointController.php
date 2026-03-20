<?php

namespace App\Http\Controllers\V1\Loyality_Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Loyality_Management\LoyalityPointRequest;
use App\Http\Requests\V1\Loyality_Management\LoyalityPointUpdateRequest;
use App\Http\Resources\V1\Loyality_Management\LoyalityPointResource;
use App\Http\Resources\V1\Loyality_Management\LoyalityPointHeaderResource;
use App\Services\V1\Loyality_Management\LoyalityPointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Agent_Transaction\InvoiceHeader;
use App\Models\Agent_Transaction\InvoiceDetail;
use App\Models\BonusPoint;

class LoyalityPointController extends Controller
{
    protected LoyalityPointService $service;

    public function __construct(LoyalityPointService $service)
    {
        $this->service = $service;
    }

public function store(LoyalityPointRequest $request): JsonResponse
    {
        try {
            $loyality = $this->service->createFromInvoice($request->validated());
            if (!$loyality) {
                return response()->json([
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Failed to create loyality'
                ], 400);
            }
            return response()->json([
                'status' => 'success',
                'code' => 201,
                'data' => new LoyalityPointResource($loyality)
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Failed to create loyality',
                'error' => $e->getMessage()
            ], 400);
        }
    }

public function index(Request $request): JsonResponse
{
    try {
        $perPage  = $request->get('limit', 50);
        $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);

        $filters = $request->except(['limit', 'dropdown']);

        $data = $this->service->getAll($filters, $perPage, $dropdown);

        if ($dropdown) {
            return response()->json([
                'status' => 'success',
                'code'   => 200,
                'data'   => $data,
            ]);
        }

        $pagination = [
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
            'per_page'     => $data->perPage(),
            'total'        => $data->total(),
        ];

        return response()->json([
            'status'     => 'success',
            'code'       => 200,
            'message'    => 'Loyality points fetched successfully',
            'data'       => LoyalityPointHeaderResource::collection($data),
            'pagination' => $pagination,
        ], 200);

    } catch (\Exception $e) {

        return response()->json([
            'status'  => 'error',
            'code'    => 500,
            'message' => 'Failed to retrieve loyality points',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
public function show(string $uuid, Request $request): JsonResponse
{
    try {
        $filters = $request->only([
            'from_date',
            'to_date',
            'activity_type',
            'detail_customer_id',
            'customer_id',
            'tier_id',
            'search'
        ]);

        $data = $this->service->getByUuid($uuid, $filters);

        if (!$data) {
            return response()->json([
                'status'  => 'error',
                'code'    => 404,
                'message' => 'Record not found',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Record fetched successfully',
            'data'    => new LoyalityPointResource($data),
        ], 200);

    } catch (\Exception $e) {

        return response()->json([
            'status'  => 'error',
            'code'    => 500,
            'message' => 'Failed to retrieve record',
            'error'   => $e->getMessage(),
        ], 500);
    }
}


public function update(LoyalityPointUpdateRequest $request, string $uuid): JsonResponse
{
    try {
        $data = $this->service->update($uuid, $request->validated());

        if (!$data) {
            return response()->json([
                'status'  => 'error',
                'code'    => 404,
                'message' => 'Record not found',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Loyality record updated successfully',
            'data'    => new LoyalityPointResource($data),
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'status'  => 'error',
            'code'    => 500,
            'message' => 'Failed to update record',
            'error'   => $e->getMessage(),
        ]);
    }
}
public function destroy(string $uuid): JsonResponse
{
    try {
        $deleted = $this->service->deleteByUuid($uuid);

        if (!$deleted) {
            return response()->json([
                'status'  => 'error',
                'code'    => 404,
                'message' => 'Record not found',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Loyality record deleted successfully',
        ], 200);

    } catch (\Exception $e) {

        return response()->json([
            'status'  => 'error',
            'code'    => 500,
            'message' => 'Failed to delete record',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

public function getClosing(Request $request): JsonResponse
{
    try {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:agent_customers,id'
        ]);

        $customerId = $validated['customer_id'];
        $closing = $this->service->getClosingByCustomerId($customerId);

        if ($closing === null) {
            return response()->json([
                'status'  => 'error',
                'code'    => 200,
                'message' => 'No loyalty record found for this customer',
            ], 200);
        }

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Customer closing balance fetched successfully',
            'data'    => [
                'customer_id'   => $customerId,
                'total_closing' => $closing,
            ]
        ], 200);

    } catch (\Exception $e) {

        return response()->json([
            'status'  => 'error',
            'code'    => 500,
            'message' => 'Failed to fetch closing balance',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

// public function calculateRewards(Request $request)
// {
//     $request->validate([
//         'customer_id' => 'required|integer|exists:agent_customers,id'
//     ]);

//     $customerId = $request->customer_id;

//     $headers = InvoiceHeader::where('customer_id', $customerId)->pluck('id');

//     if ($headers->isEmpty()) {
//         return response()->json([
//             'status' => 'success',
//             'total_item_rewards' => 0,
//             'bonus_points' => 0,
//             'grand_total_rewards' => 0,
//             'message' => 'No invoices found for this customer'
//         ]);
//     }

//     $details = InvoiceDetail::with('item:id,rewards,volumes')
//                 ->whereIn('header_id', $headers)
//                 ->get();

//     if ($details->isEmpty()) {
//         return response()->json([
//             'status' => 'success',
//             'total_item_rewards' => 0,
//             'bonus_points' => 0,
//             'grand_total_rewards' => 0,
//             'message' => 'No invoice details found'
//         ]);
//     }

//     $totalItemRewards = 0;

//     foreach ($details as $detail) {
//         $item = $detail->item;
//         if (!$item) continue;

//         $quantity = $detail->quantity;
//         $volumes = $item->volumes;
//         $rewards = $item->rewards;

//         if ($volumes > 0 && $quantity >= $volumes) {
//             $perVolumeReward = $rewards / $volumes;
//             $itemReward = $quantity * $perVolumeReward;
//             $totalItemRewards += $itemReward;
//         }
//     }

//     $quantitiesByItem = [];
//     foreach ($details as $detail) {
//         if (!isset($quantitiesByItem[$detail->item_id])) {
//             $quantitiesByItem[$detail->item_id] = 0;
//         }
//         $quantitiesByItem[$detail->item_id] += $detail->quantity;
//     }

//     $bonusTotal = 0;

//     foreach ($quantitiesByItem as $itemId => $totalQuantity) {

//         $bonus = BonusPoint::where('item_id', $itemId)->first();
//         if (!$bonus) continue;

//         $bonusVolumes = $bonus->volume ?? 0;
//         $bonusPoints = $bonus->bonus_points ?? 0;

//         if ($bonusVolumes > 0 && $totalQuantity >= $bonusVolumes) {
//             $bonusTotal += $bonusPoints;
//         }
//     }

//     $grandTotalRewards = $totalItemRewards + $bonusTotal;

//     return response()->json([
//         'status' => 'success',
//         'total_item_rewards' => $totalItemRewards,
//         'bonus_points' => $bonusTotal,
//         'grand_total_rewards' => $grandTotalRewards
//     ]);
// }

public function getByCustomer(Request $request)
{
    $customerId = $request->customer_id;

    if ($customerId) {

        $request->validate([
            'customer_id' => 'integer|exists:agent_customers,id'
        ]);

        $invoices = InvoiceHeader::select('invoice_code', 'invoice_date','id')
            ->where('customer_id', $customerId)
            ->orderBy('invoice_date', 'desc')
            ->get();

        if ($invoices->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No invoices found for this customer'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'count' => $invoices->count(),
            'data' => $invoices
        ], 200);
    }
    $invoices = InvoiceHeader::select('invoice_code', 'invoice_date')
        ->orderBy('invoice_date', 'desc')
        ->get();

    return response()->json([
        'status' => 'success',
        'count' => $invoices->count(),
        'data' => $invoices
    ], 200);
}

}