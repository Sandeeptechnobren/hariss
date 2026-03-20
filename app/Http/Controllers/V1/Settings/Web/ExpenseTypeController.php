<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\ExpenseTypeRequest;
use App\Http\Resources\V1\Settings\Web\ExpenseTypeResource;
use App\Models\ExpenseType;
use App\Services\V1\Settings\Web\ExpenseTypeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Schema(
 *     schema="ExpenseType",
 *     type="object",
 *     required={"expense_type_code","expense_type_name","expense_type_status"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="expense_type_code", type="string", example="EXT001"),
 *     @OA\Property(property="expense_type_name", type="string", example="Travel Expenses"),
 *     @OA\Property(property="expense_type_status", type="integer", enum={0,1}, example=0),
 *     @OA\Property(property="created_user", type="integer", example=1),
 *     @OA\Property(property="updated_user", type="integer", nullable=true, example=2),
 *     @OA\Property(property="created_date", type="string", format="date-time", example="2025-09-17 11:00:00"),
 *     @OA\Property(property="updated_date", type="string", format="date-time", example="2025-09-17 12:30:00")
 * )
 */
class ExpenseTypeController extends Controller
{
    use ApiResponse;

    protected ExpenseTypeService $expenseTypeService;

    public function __construct(ExpenseTypeService $expenseTypeService)
    {
        $this->expenseTypeService = $expenseTypeService;
    }

    // /**
    //  * @OA\Get(
    //  *     path="/api/settings/expense_type/list",
    //  *     summary="Get all expense types",
    //  *     tags={"Expense Types"},
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Response(response=200, description="List", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ExpenseType")))
    //  * )
    //  */
    // public function index(): JsonResponse
    // {
    //     $filters = request()->only(['expense_type_code', 'expense_type_name', 'status']);
    //     $perPage = (int) request()->get('per_page', 10);

    //     // correct parameter order: filters first, perPage second
    //     $paginator = $this->expenseTypeService->getAll($filters, $perPage);

    //     // $response = [
    //     //     ExpenseTypeResource::collection($paginator->items()),
    //     //     'pagination' => [
    //     //         'current_page' => $paginator->currentPage(),
    //     //         'per_page'     => $paginator->perPage(),
    //     //         'total'        => $paginator->total(),
    //     //         'last_page'    => $paginator->lastPage(),
    //     //     ],
    //     // ];
    //     return $this->success(
    //         ExpenseTypeResource::collection($paginator->items()),
    //         'Expense types retrieved successfully',
    //         200,
    //         [
    //             'current_page' => $paginator->currentPage(),
    //             'per_page'     => $paginator->perPage(),
    //             'total'        => $paginator->total(),
    //             'last_page'    => $paginator->lastPage(),
    //         ],
    //     );
    // }





    // /**
    //  * @OA\Get(
    //  *     path="/api/settings/expense_type/{id}",
    //  *     summary="Get expense type by ID",
    //  *     tags={"Expense Types"},
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
    //  *     @OA\Response(response=200, description="Details", @OA\JsonContent(ref="#/components/schemas/ExpenseType")),
    //  *     @OA\Response(response=404, description="Not Found")
    //  * )
    //  */
    // public function show($id): JsonResponse
    // {
    //     $data = new ExpenseTypeResource($this->expenseTypeService->getById($id));
    //     return $this->success($data, 'Expense type retrieved successfully');
    // }

    // /**
    //  * @OA\Post(
    //  *     path="/api/settings/expense_type/create",
    //  *     summary="Create a new expense type",
    //  *     tags={"Expense Types"},
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\RequestBody(
    //  *         required=true,
    //  *         @OA\JsonContent(
    //  *             required={"expense_type_name","expense_type_status"},
    //  *             @OA\Property(property="expense_type_name", type="string", example="Meals"),
    //  *             @OA\Property(property="expense_type_status", type="integer", enum={0,1}, example=0)
    //  *         )
    //  *     ),
    //  *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/ExpenseType"))
    //  * )
    //  */
    // public function store(ExpenseTypeRequest $request): JsonResponse
    // {
    //     $newExpense = $this->expenseTypeService->create($request->validated());
    //     return $this->success($newExpense, 'Expense type created successfully', 201);
    // }

    // /**
    //  * @OA\Put(
    //  *     path="/api/settings/expense_type/{id}/update",
    //  *     summary="Update expense type",
    //  *     tags={"Expense Types"},
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=2)),
    //  *     @OA\RequestBody(
    //  *         required=true,
    //  *         @OA\JsonContent(
    //  *             required={"expense_type_name","expense_type_status"},
    //  *             @OA\Property(property="expense_type_name", type="string", example="Lodging"),
    //  *             @OA\Property(property="expense_type_status", type="integer", enum={0,1}, example=1)
    //  *         )
    //  *     ),
    //  *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/ExpenseType")),
    //  *     @OA\Response(response=404, description="Not Found")
    //  * )
    //  */
    // public function update(ExpenseTypeRequest $request, $id): JsonResponse
    // {
    //     $updated = $this->expenseTypeService->update($id, $request->validated());
    //     return $this->success(new ExpenseTypeResource($updated), 'Expense type updated successfully');
    // }

    // /**
    //  * @OA\Delete(
    //  *     path="/api/settings/expense_type/{id}/delete",
    //  *     summary="Delete expense type",
    //  *     tags={"Expense Types"},
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=3)),
    //  *     @OA\Response(response=204, description="Deleted"),
    //  *     @OA\Response(response=404, description="Not Found")
    //  * )
    //  */
    // public function destroy($id): JsonResponse
    // {
    //     $deleted = $this->expenseTypeService->delete($id);

    //     if ($deleted) {
    //         return response()->json([
    //             'status'  => 'success',
    //             'code'    => 200,
    //             'message' => 'Expense Type deleted successfully'
    //         ], 200);
    //     }

    //     return response()->json([
    //         'status'  => 'error',
    //         'code'    => 404,
    //         'message' => 'Selected Expense Type does not exist'
    //     ], 404);
    // }
}
