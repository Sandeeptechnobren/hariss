<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\StoreExpenceTypeRequest;
use App\Http\Requests\V1\Settings\Web\UpdateExpenceTypeRequest;
use App\Services\V1\Settings\Web\ExpenceTypeService;
use App\Http\Resources\V1\Settings\Web\ExpenceTypeResource;
use App\Helpers\ResponseHelper;
use Exception;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="ExpenceType",
 *     type="object",
 *     title="Expence Type",
 *      @OA\Property(property="uuid", type="string", example="b8f8a5d2-4e2e-4a9d-b6f8-d15a8a9a72f0"),
 *      @OA\Property(property="osa_code", type="string", example="OSA-001"),
 *      @OA\Property(property="name", type="string", example="Travel Allowance"),
 *      @OA\Property(property="status", type="boolean", example=true)

 * )
 */
class ExpenceTypeController extends Controller
{
    protected $service;

    public function __construct(ExpenceTypeService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/expence-types/list",
     *     summary="Get all Expence Types",
     *     tags={"Expence Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of all Expence Types",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Expence Types fetched successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ExpenceType"))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['osa_code', 'name', 'status']);
            $perPage = (int) $request->get('per_page', 50);

            $data = $this->service->getAll($perPage, $filters);

            return ResponseHelper::paginatedResponse(
                'Route expenses fetched successfully',
                ExpenceTypeResource::class,
                $data
            );
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'code'    => 500,
                'message' => 'Failed to fetch expense types: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Schema(
     *     schema="StoreExpenceTypeRequest",
     *     required={"name"},
     *     title="Store Expence Type Request",
     *     description="Request body for creating a new expence type",
     *      @OA\Property(property="name", type="string", example="Travel Allowance"),
     *      @OA\Property(property="status", type="boolean", example=true)
     * )
     *
     * @OA\Post(
     *     path="/api/settings/expence-types/add",
     *     summary="Create a new Expence Type",
     *     tags={"Expence Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreExpenceTypeRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Expence Type created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Expence Type created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/ExpenceType")
     *         )
     *     )
     * )
     */
    public function store(StoreExpenceTypeRequest $request)
    {
        try {
            $expenceType = $this->service->create($request->all());

            return response()->json([
                'status'  => true,
                'code'    => 201,
                'message' => 'Expense type created successfully',
                'data'    => $expenceType,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'code'    => 500,
                'message' => 'Failed to create expense type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/settings/expence-types/{uuid}",
     *     summary="Get details of a specific Expence Type",
     *     tags={"Expence Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the Expence Type",
     *         @OA\Schema(type="string", example="b8f8a5d2-4e2e-4a9d-b6f8-d15a8a9a72f0")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Expence Type details fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Expence Type details fetched"),
     *             @OA\Property(property="data", ref="#/components/schemas/ExpenceType")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Expence Type not found")
     * )
     */
    public function show(string $uuid)
    {
        try {
            $expenceType = $this->service->findByUuid($uuid);

            return response()->json([
                'status'  => true,
                'code'    => 200,
                'message' => 'Expense type retrieved successfully',
                'data'    => $expenceType,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'code'    => 404,
                'message' => 'Expense type not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * @OA\Schema(
     *     schema="UpdateExpenceTypeRequest",
     *     title="Update Expence Type Request",
     *     description="Schema for updating an existing expense type",
     *      @OA\Property(property="name", type="string", example="Travel Allowance"),
     *      @OA\Property(property="status", type="boolean", example=true)
     * )
     * @OA\Put(
     *     path="/api/settings/expence-types/update/{uuid}",
     *     summary="Update an existing Expence Type",
     *     tags={"Expence Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="UUID of the Expence Type to update",
     *         @OA\Schema(type="string", example="b8f8a5d2-4e2e-4a9d-b6f8-d15a8a9a72f0")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateExpenceTypeRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Expence Type updated successfully"
     *     )
     * )
     */
    public function update(updateExpenceTypeRequest $request, string $uuid)
    {
        try {
            $expenceType = $this->service->updateByUuid($uuid, $request->all());

            return response()->json([
                'status'  => true,
                'code'    => 200,
                'message' => 'Expense type updated successfully',
                'data'    => $expenceType,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'code'    => 500,
                'message' => 'Failed to update expense type: ' . $e->getMessage(),
            ], 500);
        }
    }

    // /**
    //  * @OA\Delete(
    //  *     path="/api/settings/expence-types/delete/{uuid}",
    //  *     summary="Delete an Expence Type",
    //  *     tags={"Expence Types"},
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(
    //  *         name="uuid",
    //  *         in="path",
    //  *         required=true,
    //  *         description="UUID of the Expence Type to delete",
    //  *         @OA\Schema(type="string", example="b8f8a5d2-4e2e-4a9d-b6f8-d15a8a9a72f0")
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Expence Type deleted successfully",
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="string", example="success"),
    //  *             @OA\Property(property="message", type="string", example="Expence Type deleted successfully"),
    //  *             @OA\Property(
    //  *                 property="data",
    //  *                 type="array",
    //  *                 @OA\Items(type="string", example="")
    //  *             )
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=404,
    //  *         description="Expence Type not found",
    //  *         @OA\JsonContent(
    //  *             type="object",
    //  *             @OA\Property(property="status", type="string", example="error"),
    //  *             @OA\Property(property="message", type="string", example="Expence Type not found")
    //  *         )
    //  *     )
    //  * )
    //  */
    // public function destroy(string $uuid)
    // {
    //     try {
    //         $this->service->destroy($uuid);

    //         return response()->json([
    //             'status'  => true,
    //             'code'    => 200,
    //             'message' => 'Expense type deleted successfully',
    //         ]);
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'status'  => false,
    //             'code'    => 500,
    //             'message' => 'Failed to delete expense type: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }
}
