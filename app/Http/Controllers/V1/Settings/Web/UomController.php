<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Http\Requests\V1\Assets\Web\UpdateUomRequest;
use App\Http\Requests\V1\Settings\Web\StoreUomRequest;
use App\Http\Resources\V1\Settings\Web\UomResource;
use App\Services\V1\Settings\Web\UomService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="UOM",
 *     description="API Endpoints for Unit of Measurement management"
 * )
 */
class UomController extends Controller
{
    use ApiResponse;
    protected $uomService;

    public function __construct(UomService $uomService)
    {
        $this->uomService = $uomService;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/uom/list",
     *     tags={"UOM"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get all UOMs (including soft-deleted)",
     *     @OA\Response(
     *         response=200,
     *         description="All UOMs fetched successfully"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $filters  = $request->only(['name', 'osa_code', 'status']);
        $perPage  = $request->get('limit', 10);
        $dropdown = $request->boolean('dropdown', false);

        $uoms = $this->uomService->getAll(
            $perPage,
            $filters,
            $dropdown
        );

        // 🔹 DROPDOWN RESPONSE
        if ($dropdown) {
            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'UOMs fetched successfully',
                'data'    => $uoms
            ]);
        }

        // 🔹 NORMAL PAGINATED RESPONSE
        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Uom types fetched successfully',
            'data'    => UomResource::collection($uoms->items()),
            'pagination' => [
                'page'         => $uoms->currentPage(),
                'limit'        => $uoms->perPage(),
                'totalPages'   => $uoms->lastPage(),
                'totalRecords' => $uoms->total(),
            ]
        ]);
    }

    // public function index()
    // {
    //     $uoms = $this->uomService->getAll();
    //     return response()->json([
    //         'status' => 'success',
    //         'code' => 200,
    //         'message' => 'Uom types fetched successfully',
    //         'data' => UomResource::collection($uoms->items()),
    //         'pagination' => [
    //             'page' => $uoms->currentPage(),
    //             'limit' => $uoms->perPage(),
    //             'totalPages' => $uoms->lastPage(),
    //             'totalRecords' => $uoms->total(),
    //         ]
    //     ]);
    //     // return ResponseHelper::success(UomResource::collection($uoms), 'All UOMs fetched successfully');
    // }

    /**
     * @OA\Post(
     *     path="/api/settings/uom/add",
     *     tags={"UOM"},
     *     summary="Create a new UOM",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "osa_code"},
     *             @OA\Property(property="name", type="string", example="Piece")
     *         )
     *     ),
     *     @OA\Response(response=201, description="UOM created successfully"),
     *     @OA\Response(response=400, description="Invalid data")
     * )
     */
    public function store(StoreUomRequest $request)
    {
        $uom = $this->uomService->create($request->validated());
        // return $this->success($uom);
        return $this->success(
            new UomResource($uom),
            'UOM created successfully'
        );

        // return ResponseHelper::success(new UomResource($uom), 'UOM created successfully', 201);
    }

    // /**
    //  * @OA\Get(
    //  *     path="/api/settings/uom/{uuid}",
    //  *     tags={"UOM"},
    //  *     summary="Get a specific UOM by UUID",
    //  *     @OA\Parameter(
    //  *         name="uuid",
    //  *         in="path",
    //  *         required=true,
    //  *         description="UUID of the UOM",
    //  *         @OA\Schema(type="string", example="3a8f8c84-12b4-4e4e-aad9-3a7e1a7efc2a")
    //  *     ),
    //  *     @OA\Response(response=200, description="UOM fetched successfully"),
    //  *     @OA\Response(response=404, description="UOM not found")
    //  * )
    //  */
    // public function show(string $uuid)
    // {
    //     $uom = $this->uomService->getByUuid($uuid);

    //     return response()->json([
    //         'status' => 'success',
    //         'code' => 200,
    //         'message' => 'UOM type fetched successfully',
    //         'data' => new UomResource($uom)
    //     ]);
    //     // if (!$uom) {
    //     //     return ResponseHelper::error('UOM not found', 404);
    //     // }

    //     // return ResponseHelper::success(new UomResource($uom), 'UOM fetched successfully');
    // }

    // /**
    //  * @OA\Put(
    //  *     path="/api/settings/uom/{uuid}",
    //  *     tags={"UOM"},
    //  *     summary="Update a specific UOM",
    //  *     @OA\Parameter(
    //  *         name="uuid",
    //  *         in="path",
    //  *         required=true,
    //  *         description="UUID of the UOM",
    //  *         @OA\Schema(type="string", example="3a8f8c84-12b4-4e4e-aad9-3a7e1a7efc2a")
    //  *     ),
    //  *     @OA\RequestBody(
    //  *         required=true,
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="uom_name", type="string", example="Box"),
    //  *             @OA\Property(property="uom_code", type="string", example="BX")
    //  *         )
    //  *     ),
    //  *     @OA\Response(response=200, description="UOM updated successfully"),
    //  *     @OA\Response(response=404, description="UOM not found")
    //  * )
    //  */
    // public function update(UpdateUomRequest $request, string $uuid)
    // {
    //     $uom = $this->uomService->update($uuid, $request->validated());
    //     return response()->json([
    //         'status' => 'success',
    //         'code' => 200,
    //         'message' => 'UOM type fetched successfully',
    //         'data' => new UomResource($uom)
    //     ]);
    //     // if (!$uom) {
    //     //     return ResponseHelper::error('UOM not found', 404);
    //     // }

    //     // return ResponseHelper::success(new UomResource($uom), 'UOM updated successfully');
    // }

    // /**
    //  * @OA\Delete(
    //  *     path="/api/settings/uom/{uuid}",
    //  *     tags={"UOM"},
    //  *     summary="Soft delete a UOM",
    //  *     @OA\Parameter(
    //  *         name="uuid",
    //  *         in="path",
    //  *         required=true,
    //  *         description="UUID of the UOM",
    //  *         @OA\Schema(type="string", example="3a8f8c84-12b4-4e4e-aad9-3a7e1a7efc2a")
    //  *     ),
    //  *     @OA\Response(response=200, description="UOM soft deleted successfully"),
    //  *     @OA\Response(response=404, description="UOM not found")
    //  * )
    //  */
    // public function destroy(string $uuid)
    // {
    //     $uom = $this->uomService->softDelete($uuid);

    //     return response()->json([
    //         'status' => 'success',
    //         'code' => 200,
    //         'message' => 'UOM type fetched successfully',
    //         'data' => new UomResource($uom)
    //     ]);
    //     // if (!$uom) {
    //     //     return ResponseHelper::error('UOM not found', 404);
    //     // }

    //     // return ResponseHelper::success(null, 'UOM soft deleted successfully');
    // }
}
