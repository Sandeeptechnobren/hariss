<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\SalesmanTypeRequest;
use App\Http\Resources\V1\Settings\Web\SalesmanTypeResource;
use App\Models\SalesmanType;
use App\Services\V1\Settings\Web\SalesmanTypeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="SalesmanType",
 *     type="object",
 *     required={"salesman_type_name", "salesman_type_status", "salesman_created_user"},
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="salesman_type_code", type="string", example="SMT001"),
 *     @OA\Property(property="salesman_type_name", type="string", example="Senior Salesman"),
 *     @OA\Property(property="salesman_type_status", type="integer", enum={0,1}, example=0, description="0=Active, 1=Inactive"),
 *     @OA\Property(property="salesman_created_user", type="integer", example=1),
 *     @OA\Property(property="salesman_updated_user", type="integer", nullable=true, example=2),
 *     @OA\Property(property="salesman_created_date", type="string", format="date-time", example="2025-09-17 10:00:00"),
 *     @OA\Property(property="salesman_updated_date", type="string", format="date-time", example="2025-09-17 11:30:00")
 * )
 */
class SalesmanTypeController extends Controller
{
    use ApiResponse;

    protected SalesmanTypeService $salesmanTypeService;

    public function __construct(SalesmanTypeService $salesmanTypeService)
    {
        $this->salesmanTypeService = $salesmanTypeService;
    }
    /**
     * @OA\Get(
     *     path="/api/settings/salesman_type/list",
     *     summary="Get all salesman types",
     *     tags={"Salesman Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of salesman types",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/SalesmanType"))
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $filters  = request()->all();
        $perPage  = request()->get('per_page', 10);
        $dropdown = request()->boolean('dropdown', false);

        $result = $this->salesmanTypeService->getAll(
            $filters,
            $perPage,
            $dropdown
        );

        // 🔹 DROPDOWN RESPONSE
        if ($dropdown) {
            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Salesman types retrieved successfully',
                'data'    => $result
            ]);
        }

        // 🔹 NORMAL PAGINATED RESPONSE (UNCHANGED)
        return $this->success(
            SalesmanTypeResource::collection($result->items()),
            'Salesman types retrieved successfully',
            200,
            [
                'current_page' => $result->currentPage(),
                'per_page'     => $result->perPage(),
                'total'        => $result->total(),
                'last_page'    => $result->lastPage(),
            ]
        );
    }

    // public function index(): JsonResponse
    // {
    //     $paginator = $this->salesmanTypeService->getAll();
    //     // dd($paginator);
    //     return $this->success(
    //         SalesmanTypeResource::collection($paginator->items()),
    //         'Salesman types retrieved successfully',
    //         200,
    //         [
    //             'current_page' => $paginator->currentPage(),
    //             'per_page'     => $paginator->perPage(),
    //             'total'        => $paginator->total(),
    //             'last_page'    => $paginator->lastPage(),
    //         ]
    //     );
    // }


    /**
     * @OA\Get(
     *     path="/api/settings/salesman_type/{id}",
     *     summary="Get salesman type by ID",
     *     tags={"Salesman Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Salesman type details", @OA\JsonContent(ref="#/components/schemas/SalesmanType")),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show($id): JsonResponse
    {
        $data = new SalesmanTypeResource($this->salesmanTypeService->getById($id));
        return $this->success($data, 'Salesman type retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/settings/salesman_type/create",
     *     summary="Create a new salesman type",
     *     tags={"Salesman Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"salesman_type_name","salesman_type_status","salesman_created_user"},
     *             @OA\Property(property="salesman_type_name", type="string", example="Junior Salesman"),
     *             @OA\Property(property="salesman_type_status", type="integer", enum={0,1}, example=0),
     *             @OA\Property(property="salesman_created_user", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/SalesmanType"))
     * )
     */
    public function store(SalesmanTypeRequest $request): JsonResponse
    {
        $salesmanType = $this->salesmanTypeService->create($request->validated());
        return $this->success(new SalesmanTypeResource($salesmanType), 'Salesman type created successfully', 201);
    }

    /**
     * @OA\Put(
     *     path="/api/settings/salesman_type/{id}/update",
     *     summary="Update salesman type",
     *     tags={"Salesman Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=2)),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"salesman_type_name","salesman_type_status","salesman_updated_user"},
     *             @OA\Property(property="salesman_type_name", type="string", example="Area Manager"),
     *             @OA\Property(property="salesman_type_status", type="integer", enum={0,1}, example=1),
     *             @OA\Property(property="salesman_updated_user", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SalesmanType")),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'salesman_type_code' => 'sometimes|string|max:50',
            'salesman_type_name'   => 'sometimes|string|max:100',
            'salesman_type_status' => 'sometimes|integer|in:0,1',
        ]);
        $updated = $this->salesmanTypeService->update($id, $validated);
        return $this->success(new SalesmanTypeResource($updated), 'Salesman type updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/api/settings/salesman_type/{id}/delete",
     *     summary="Delete salesman type",
     *     tags={"Salesman Types"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=3)),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function destroy($id): JsonResponse
    {
        // dd($id);
        $deleted = $this->salesmanTypeService->delete($id);
        if ($deleted) {
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Saleman type deleted successfully',
            ]);
        }

        return $this->fail('Failed to delete salesman type', 500);
    }
}
