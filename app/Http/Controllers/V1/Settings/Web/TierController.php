<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\TierRequest;
use App\Http\Requests\V1\Settings\Web\UpdateTierRequest;
use App\Http\Resources\V1\Settings\Web\TierResource;
use App\Services\V1\Settings\Web\TierService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Helpers\LogHelper;
use App\Models\Tier;

class TierController extends Controller
{
    protected TierService $service;

    public function __construct(TierService $service)
    {
        $this->service = $service;
    }

 public function store(TierRequest $request): JsonResponse
    {
        try {
            $tier = $this->service->createReward($request->validated());
            if ($tier) {
            LogHelper::store(
            'settings',                 
            'tier',                
            'add',                   
            null,                      
            $tier->getAttributes(),     
            auth()->id()                
        );
    }
            return response()->json([
                'success' => true,
                'message' => 'Tier created successfully.',
                'data' => new TierResource($tier),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tier: ' . $e->getMessage(),
            ], 500);
        }
    }

public function index(Request $request)
 {
    $perPage = $request->get('per_page', 50);

    $filters = $request->only(['osa_code', 'name', 'period','minpurchase','maxpurchase','period_category','expiray_period']);

    $data = $this->service->listTiers([
        'osa_code' => $filters['osa_code'] ?? null,
        'name' => $filters['name'] ?? null,
        'period' => $filters['period'] ?? null,
        'minpurchase' => $filters['minpurchase'] ?? null,
        'maxpurchase' => $filters['maxpurchase'] ?? null,
        'period_category' => $filters['period_category'] ?? null,
        'expiray_period' => $filters['expiray_period'] ?? null,
    ], $perPage);

    return response()->json([
        'status'     => 'success',
        'code'       => 200,
        'message'    => 'Tiers fetched successfully',
        'data'       => TierResource::collection($data->items()),
        'pagination' => [
            'currentPage'    => $data->currentPage(),
            'perPage'        => $data->perPage(),
            'lastPage'       => $data->lastPage(),
            'total'          => $data->total(),
        ]
    ]);
}


public function show(string $uuid)
{
    $bank = $this->service->getByUuid($uuid);

    if (!$bank) {
        return response()->json([
            'status'  => 'error',
            'code'    => 404,
            'message' => 'Tiers not found',
            'data'    => null
        ], 404);
    }

    return response()->json([
        'status'  => 'success',
        'code'    => 200,
        'message' => 'Tiers fetched successfully',
        'data'    => new TierResource($bank)
    ]);
}

public function update(UpdateTierRequest $request, string $uuid)
{
    $oldTier = Tier::where('uuid', $uuid)->first();
    $previousData = $oldTier ? $oldTier->getOriginal() : null;

    $validatedData = $request->validated();
    $updatedBank = $this->service->updateTierByUuid($uuid, $validatedData);

    if ($updatedBank && $previousData) {
        LogHelper::store(
            'settings',                 
            'tier',                  
            'update',              
            $previousData,               
            $updatedBank->getAttributes(), 
            auth()->id()                
        );
    }

    if (!$updatedBank) {
        return response()->json([
            'status'  => 'error',
            'code'    => 404,
            'message' => 'Tier not found',
            'data'    => null
        ], 404);
    }

    return response()->json([
        'status'  => 'success',
        'code'    => 200,
        'message' => 'Tier updated successfully',
        'data'    => new TierResource($updatedBank)
    ]);
}

public function destroy(string $uuid): JsonResponse
{
    $oldTier = Tier::where('uuid', $uuid)->first();
    $previousData = $oldTier ? $oldTier->getOriginal() : null;

    try {
        $this->service->deleteTier($uuid);

        if ($previousData) { 
            LogHelper::store(
                'settings',    
                'tier',        
                'delete',    
                $previousData,   
                null,             
                auth()->id()     
            );
        }
        return response()->json([
            'success' => true,
            'message' => 'Tier deleted successfully.',
        ], 200);

    } catch (ModelNotFoundException $e) {

        return response()->json([
            'success' => false,
            'message' => 'Tier not found.',
        ], 404);

    } catch (Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Failed to delete tier: ' . $e->getMessage(),
        ], 500);
    }
}

public function updateCustomerTier(Request $request): JsonResponse
{
    $validated = $request->validate([
        'customer_id' => 'required|integer|exists:agent_customers,id',
    ]);

    try {
        $tier = $this->service->calculateCustomerTier($validated['customer_id']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Tier updated successfully',
            'data'    => $tier
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => $e->getMessage()
        ], 400);
    }
}

}