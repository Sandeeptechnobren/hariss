<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\RewardCategoryRequest;
use App\Http\Requests\V1\Settings\Web\RewardUpdateRequest;
use App\Http\Resources\V1\Settings\Web\RewardCategoryResource;
use App\Services\V1\Settings\Web\RewardCategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Helpers\LogHelper;
use App\Models\RewardCategory;

class RewardCategoryController extends Controller
{
    protected RewardCategoryService $service;

    public function __construct(RewardCategoryService $service)
    {
        $this->service = $service;
    }

/**
 * @OA\Post(
 *     path="/api/settings/rewards/create",
 *     summary="Create a new reward category",
 *     description="Create reward category with image upload",
 *     tags={"Rewards"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *
 *                 @OA\Property(
 *                     property="osa_code",
 *                     type="string",
 *                     nullable=true,
 *                     example="OSA001"
 *                 ),
 *
 *                 @OA\Property(
 *                     property="name",
 *                     type="string",
 *                     example="Silver Reward"
 *                 ),
 *
 *                 @OA\Property(
 *                     property="image",
 *                     type="string",
 *                     format="binary",
 *                     description="Image file (jpg,jpeg,png,webp)"
 *                 ),
 *
 *                 @OA\Property(
 *                     property="points_required",
 *                     type="integer",
 *                     example=100
 *                 ),
 *
 *                 @OA\Property(
 *                     property="stock_qty",
 *                     type="number",
 *                     example=20
 *                 ),
 *
 *                 @OA\Property(
 *                     property="type",
 *                     type="string",
 *                     example="gift"
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Reward created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Reward created successfully."),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Failed to create reward.")
 *         )
 *     )
 * )
 */
 
 public function store(RewardCategoryRequest $request): JsonResponse
    {
        try {
            $reward = $this->service->createReward($request->validated());
            if ($reward) {
            LogHelper::store(
            'settings',                 
            'rewards',                
            'add',                   
            null,                      
            $reward->getAttributes(),     
            auth()->id()                
        );
    }
            return response()->json([
                'success' => true,
                'message' => 'Reward created successfully.',
                'data' => new RewardCategoryResource($reward),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create reward: ' . $e->getMessage(),
            ], 500);
        }
    }

public function index(Request $request)
 {
    $perPage = $request->get('per_page', 50);

    $filters = $request->only(['osa_code', 'name', 'image','points_required','stock_qty','type']);

    $data = $this->service->listRewards([
        'osa_code' => $filters['osa_code'] ?? null,
        'name' => $filters['name'] ?? null,
        'image' => $filters['image'] ?? null,
        'points_required' => $filters['points_required'] ?? null,
        'stock_qty' => $filters['stock_qty'] ?? null,
        'type' => $filters['type'] ?? null,
    ], $perPage);

    return response()->json([
        'status'     => 'success',
        'code'       => 200,
        'message'    => 'RewardCategory fetched successfully',
        'data'       => RewardCategoryResource::collection($data->items()),
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
            'message' => 'RewardCategory not found',
            'data'    => null
        ], 404);
    }

    return response()->json([
        'status'  => 'success',
        'code'    => 200,
        'message' => 'RewardCategory fetched successfully',
        'data'    => new RewardCategoryResource($bank)
    ]);
}

/**
 * @OA\Put(
 *     path="/api/settings/rewards/update/{uuid}",
 *     summary="Update reward category",
 *     description="Update reward category details including image upload",
 *     tags={"Rewards"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         required=true,
 *         description="Reward Category ID",
 *         @OA\Schema(type="string")
 *     ),
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *
 *                 @OA\Property(
 *                     property="osa_code",
 *                     type="string",
 *                     nullable=true,
 *                     example="OSA001"
 *                 ),
 *
 *                 @OA\Property(
 *                     property="name",
 *                     type="string",
 *                     example="Updated Reward Name"
 *                 ),
 *
 *                 @OA\Property(
 *                     property="image",
 *                     type="string",
 *                     format="binary",
 *                     nullable=true,
 *                     description="Upload new image (optional)"
 *                 ),
 *
 *                 @OA\Property(
 *                     property="points_required",
 *                     type="integer",
 *                     example=150
 *                 ),
 *
 *                 @OA\Property(
 *                     property="stock_qty",
 *                     type="number",
 *                     example=30
 *                 ),
 *
 *                 @OA\Property(
 *                     property="type",
 *                     type="string",
 *                     example="gift"
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Reward updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Reward updated successfully."),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Server error"
 *     )
 * )
 */
public function update(RewardUpdateRequest $request, string $uuid): JsonResponse
{
    $oldReward = RewardCategory::where('uuid', $uuid)->first();
    $previousData = $oldReward ? $oldReward->getOriginal() : null;
    try {
        $updated = $this->service->updateReward($uuid, $request->validated());
        if ($updated && $previousData) {
            LogHelper::store(
                'settings',                 
                'reward',               
                'update',                   
                $previousData,                 
                $updated->getAttributes(), 
                auth()->id()                 
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Reward updated successfully.',
            'data' => new RewardCategoryResource($updated),
        ], 200);

    } catch (Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Failed to update reward: ' . $e->getMessage(),
        ], 500);
    }
}
}