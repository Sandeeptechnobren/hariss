<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\BonusRequest;
use App\Http\Requests\V1\Settings\Web\UpdateBonusRequest;
use App\Http\Resources\V1\Settings\Web\BonusPointResource;
use App\Services\V1\Settings\Web\BonusService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Helpers\LogHelper;
use App\Models\BonusPoint;

class BonusController extends Controller
{
    protected BonusService $service;

    public function __construct(BonusService $service)
    {
        $this->service = $service;
    }

     public function store(BonusRequest $request): JsonResponse
    {
        try {
            $reward = $this->service->createBonus($request->validated());
            if ($reward) {
                        LogHelper::store(
                        'settings',                 
                        'bonus',                
                        'add',                   
                        null,                      
                        $reward->getAttributes(),     
                        auth()->id()                
                    );
                }
            return response()->json([
                'success' => true,
                'message' => 'Bonus created successfully.',
                'data' => new BonusPointResource($reward),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create bonus: ' . $e->getMessage(),
            ], 500);
        }
    }

public function index(Request $request)
 {
    $perPage = $request->get('per_page', 50);

    $filters = $request->only(['osa_code', 'item_id', 'volume','bonus_points']);

    $data = $this->service->listBonus([
        'osa_code' => $filters['osa_code'] ?? null,
        'item_id' => $filters['item_id'] ?? null,
        'volume' => $filters['volume'] ?? null,
        'bonus_points' => $filters['bonus_points'] ?? null,
    ], $perPage);

    return response()->json([
        'status'     => 'success',
        'code'       => 200,
        'message'    => 'BonusPoints fetched successfully',
        'data'       => BonusPointResource::collection($data),
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
            'message' => 'Bonus not found',
            'data'    => null
        ], 404);
    }

    return response()->json([
        'status'  => 'success',
        'code'    => 200,
        'message' => 'Bonus fetched successfully',
        'data'    => new BonusPointResource($bank)
    ]);
}

public function update(UpdateBonusRequest $request, string $uuid): JsonResponse
{
    $oldBonus = BonusPoint::where('uuid', $uuid)->first();
    $previousData = $oldBonus ? $oldBonus->getOriginal() : null;
    try {
        $updated = $this->service->updateBonus($uuid, $request->validated());

        if ($updated && $previousData) {
                    LogHelper::store(
                        'settings',                    
                        'bonus',                    
                        'update',                       
                        $previousData,                 
                        $updated->getAttributes(),      
                        auth()->id()                   
                    );
                }

        return response()->json([
            'success' => true,
            'message' => 'Bonus updated successfully.',
            'data' => new BonusPointResource($updated),
        ], 200);

    } catch (Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Failed to update bonus: ' . $e->getMessage(),
        ], 500);
    }
}
}