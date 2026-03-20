<?php

namespace App\Http\Controllers\V1\Assets\Web;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Assets\Web\AllocateAssetsRequest;
use App\Http\Requests\V1\Assets\Web\BulkTransferRequestRequest;
use App\Http\Resources\V1\Assets\Web\BulkTransferRequestResource;
use App\Services\V1\Assets\Web\BulkTransferRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="BulkTransferRequest",
 *     type="object",
 *     title="Bulk Transfer Request",
 *     description="Schema for Bulk Transfer Request",
 *     @OA\Property(property="transfer_no", type="string", example="BTR001"),
 *     @OA\Property(property="osa_code", type="string", example="BTR-123"),
 *     @OA\Property(property="warehouse_id", type="integer", example=1),
 *     @OA\Property(property="status", type="integer", enum={0,1,2,3}, description="0=pending,1=approved,2=allocated,3=rejected", example=0)
 * )
 */
class BulkTransferRequestController extends Controller
{
    protected BulkTransferRequestService $service;

    public function __construct(BulkTransferRequestService $service)
    {
        $this->service = $service;
    }


    /**
     * @OA\Get(
     *     path="/web/assets_web/bulk_transfer/list",
     *     tags={"BulkTransferRequest"},
     *     summary="Get all Bulk Transfer Requests",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="transfer_no", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Records fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Records fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/BulkTransferRequest")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'transfer_no', 'warehouse_id']);
        $records = $this->service->getAll(20, $filters);

        return ResponseHelper::paginatedResponse(
            'Records fetched successfully',
            BulkTransferRequestResource::class,
            $records
        );
    }


    /**
     * @OA\Post(
     *     path="/web/assets_web/bulk_transfer/add",
     *     tags={"BulkTransferRequest"},
     *     summary="Create a Bulk Transfer Request",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/BulkTransferRequest")),
     *     @OA\Response(
     *         response=201,
     *         description="Record created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Record created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/BulkTransferRequest")
     *         )
     *     )
     * )
     */
    public function store(BulkTransferRequestRequest $request): JsonResponse
    {
        $data = $this->service->store($request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Record created successfully',
            'data'    => new BulkTransferRequestResource($data),
        ], 201);
    }


    /**
     * @OA\Get(
     *     path="/web/assets_web/bulk_transfer/{uuid}",
     *     tags={"BulkTransferRequest"},
     *     summary="Get Bulk Transfer Request by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Record fetched successfully",
     *         @OA\JsonContent(ref="#/components/schemas/BulkTransferRequest")
     *     ),
     *     @OA\Response(response=404, description="Record not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $record = $this->service->findByUuid($uuid);

            return response()->json([
                'status'  => 'success',
                'message' => 'Record fetched successfully',
                'data'    => new BulkTransferRequestResource($record),
            ], 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $e->getMessage()], 404);
        }
    }


    /**
     * @OA\Put(
     *     path="/web/assets_web/bulk_transfer/{uuid}",
     *     tags={"BulkTransferRequest"},
     *     summary="Update Bulk Transfer Request by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/BulkTransferRequest")),
     *     @OA\Response(response=200, description="Record updated successfully"),
     *     @OA\Response(response=404, description="Record not found")
     * )
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        try {
            // Use raw request data
            $data = $request->all();

            $record = $this->service->update($uuid, $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Record updated successfully',
                'data'    => new BulkTransferRequestResource($record),
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }



    // /**
    //  * @OA\Delete(
    //  *     path="/web/assets_web/bulk_transfer/{uuid}",
    //  *     tags={"BulkTransferRequest"},
    //  *     summary="Delete Bulk Transfer Request by UUID",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
    //  *     @OA\Response(response=200, description="Record deleted successfully"),
    //  *     @OA\Response(response=404, description="Record not found")
    //  * )
    //  */
    // public function destroy(string $uuid): JsonResponse
    // {
    //     try {
    //         $this->service->delete($uuid);

    //         return response()->json([
    //             'status'  => 'success',
    //             'message' => 'Record deleted successfully',
    //         ], 200);
    //     } catch (\Exception $e) {

    //         return response()->json(['message' => $e->getMessage()], 404);
    //     }
    // }


    /**
     * @OA\Get(
     *     path="/web/assets_web/bulk_transfer/global-search",
     *     tags={"BulkTransferRequest"},
     *     summary="Global search Bulk Transfer Request",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Records fetched successfully"),
     *     @OA\Response(response=500, description="Failed to search")
     * )
     */
    public function global_search(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);
        $searchTerm = $request->get('search');

        $records = $this->service->globalSearch($perPage, $searchTerm);

        return ResponseHelper::paginatedResponse(
            'Records fetched successfully',
            BulkTransferRequestResource::class,
            $records
        );
    }


    public function getModelNumbers(): JsonResponse
    {
        try {

            $data = $this->service->getAvailableModelNumbers();

            return response()->json([
                'status'  => 'success',
                'message' => 'Model numbers fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }


    public function getByRegion(Request $request): JsonResponse
    {
        try {

            $request->validate([
                'region_id' => 'required|integer'
            ]);

            $regionId = (int) $request->query('region_id');

            $data = $this->service->getByRegion($regionId);

            return response()->json([
                'status'  => 'success',
                'message' => 'Bulk transfer record count fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function getWarehouseAndChillers($id): JsonResponse
    {
        try {

            $data = $this->service->getWarehouseAndChillers((int)$id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Warehouse & chiller data fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }

    public function allocateAssets(AllocateAssetsRequest $request): JsonResponse
    {
        try {

            $result = $this->service->allocateAssets($request->validated());

            return response()->json([
                'status'  => 'success',
                'message' => 'Assets allocated successfully',
                'data'    => $result
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function countBySingleModel(Request $request): JsonResponse
    {
        try {
            $modelId = $request->input('model_id');

            if (!$modelId) {
                return response()->json([
                    'status'  => false,
                    'message' => 'model_id is required',
                    'data'    => null
                ], 422);
            }

            $count = $this->service->countBySingleModel((int) $modelId);

            return response()->json([
                'status'  => true,
                'message' => 'Available stock fetched successfully',
                'data'    => [
                    'model_id'        => (int) $modelId,
                    'available_stock' => $count
                ]
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }
}
