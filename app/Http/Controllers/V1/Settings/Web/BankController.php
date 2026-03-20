<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\StoreBankRequest;
use App\Http\Requests\V1\Settings\Web\BankUpdateRequest;
use App\Http\Resources\V1\Settings\Web\BankResource;
use App\Services\V1\Settings\Web\BankService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class BankController extends Controller
{
    protected BankService $bankService;

    public function __construct(BankService $bankService)
    {
        $this->bankService = $bankService;
    }

/**
 * @OA\Post(
 *     path="/api/settings/banks/create",
 *     operationId="storeBank",
 *     tags={"Banks"},
 *     summary="Create a new bank",
 *     description="Create a new bank record in the system",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"bank_name","branch","city","account_number"},
 *             @OA\Property(property="osa_code", type="string", maxLength=50, example="OSA123"),
 *             @OA\Property(property="bank_name", type="string", maxLength=255, example="First National Bank"),
 *             @OA\Property(property="branch", type="string", maxLength=255, example="Downtown Branch"),
 *             @OA\Property(property="city", type="string", maxLength=100, example="New York"),
 *             @OA\Property(property="account_number", type="integer", example=1234567890),
 *             @OA\Property(property="status", type="integer", enum={0,1}, example=1)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Bank created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Bank created successfully."),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="uuid", type="string", example="e6300d91-d15d-49d4-8c0e-c66f980432a0"),
 *                 @OA\Property(property="osa_code", type="string", example="OSA123"),
 *                 @OA\Property(property="bank_name", type="string", example="First National Bank"),
 *                 @OA\Property(property="branch", type="string", example="Downtown Branch"),
 *                 @OA\Property(property="city", type="string", example="New York"),
 *                 @OA\Property(property="account_number", type="integer", example=1234567890),
 *                 @OA\Property(property="status", type="integer", example=1)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Failed to create bank",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Failed to create bank: error message")
 *         )
 *     )
 * )
 */
  public function store(StoreBankRequest $request): JsonResponse
    {
        try {
            $bank = $this->bankService->createBank($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Bank created successfully.',
                'data' => new BankResource($bank),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create bank: ' . $e->getMessage(),
            ], 500);
        }
    }

/**
 * @OA\Get(
 *     path="/api/settings/banks/list",
 *     operationId="listBanks",
 *     tags={"Banks"},
 *     summary="List all banks with optional filters",
 *     description="Fetch a paginated list of banks. Supports filtering by status, osa_code, bank_name, branch, city, and account_number.",
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         description="Number of records per page",
 *         required=false,
 *         @OA\Schema(type="integer", example=50)
 *     ),
 *     @OA\Parameter(
 *         name="osa_code",
 *         in="query",
 *         description="Filter by OSA code",
 *         required=false,
 *         @OA\Schema(type="string", example="OSA123")
 *     ),
 *     @OA\Parameter(
 *         name="bank_name",
 *         in="query",
 *         description="Filter by bank name",
 *         required=false,
 *         @OA\Schema(type="string", example="First National Bank")
 *     ),
 *     @OA\Parameter(
 *         name="branch",
 *         in="query",
 *         description="Filter by branch",
 *         required=false,
 *         @OA\Schema(type="string", example="Downtown Branch")
 *     ),
 *     @OA\Parameter(
 *         name="city",
 *         in="query",
 *         description="Filter by city",
 *         required=false,
 *         @OA\Schema(type="string", example="New York")
 *     ),
 *     @OA\Parameter(
 *         name="account_number",
 *         in="query",
 *         description="Filter by account number",
 *         required=false,
 *         @OA\Schema(type="integer", example=1234567890)
 *     ),
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         description="Filter by status (0 or 1)",
 *         required=false,
 *         @OA\Schema(type="integer", enum={0,1}, example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Banks fetched successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Banks fetched successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="uuid", type="string", example="e6300d91-d15d-49d4-8c0e-c66f980432a0"),
 *                     @OA\Property(property="osa_code", type="string", example="OSA123"),
 *                     @OA\Property(property="bank_name", type="string", example="First National Bank"),
 *                     @OA\Property(property="branch", type="string", example="Downtown Branch"),
 *                     @OA\Property(property="city", type="string", example="New York"),
 *                     @OA\Property(property="account_number", type="integer", example=1234567890),
 *                     @OA\Property(property="status", type="integer", example=1)
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="pagination",
 *                 type="object",
 *                 @OA\Property(property="page", type="integer", example=1),
 *                 @OA\Property(property="limit", type="integer", example=50),
 *                 @OA\Property(property="totalPages", type="integer", example=10),
 *                 @OA\Property(property="totalRecords", type="integer", example=500)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="message", type="string", example="An error occurred while fetching banks")
 *         )
 *     )
 * )
 */

    public function index(Request $request)
{
    $perPage = $request->get('per_page', 50);

    $filters = $request->only(['status', 'osa_code', 'bank_name','city','branch','account_number']);

    $data = $this->bankService->listBanks([
        'osa_code' => $filters['osa_code'] ?? null,
        'account_number' => $filters['account_number'] ?? null,
        'branch' => $filters['branch'] ?? null,
        'bank_name' => $filters['bank_name'] ?? null,
        'status' => $filters['status'] ?? null,
        'city' => $filters['city'] ?? null,
    ], $perPage);

    return response()->json([
        'status'     => 'success',
        'code'       => 200,
        'message'    => 'Banks fetched successfully',
        'data'       => BankResource::collection($data->items()),
        'pagination' => [
            'page'         => $data->currentPage(),
            'limit'        => $data->perPage(),
            'totalPages'   => $data->lastPage(),
            'totalRecords' => $data->total(),
        ]
    ]);
}

/**
 * @OA\Get(
 *     path="/api/settings/banks/show/{uuid}",
 *     operationId="getBankByUuid",
 *     tags={"Banks"},
 *     summary="Get a single bank by UUID",
 *     description="Fetch the details of a single bank using its UUID.",
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         description="UUID of the bank to fetch",
 *         required=true,
 *         @OA\Schema(type="string", example="e6300d91-d15d-49d4-8c0e-c66f980432a0")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Bank fetched successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Bank fetched successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="uuid", type="string", example="e6300d91-d15d-49d4-8c0e-c66f980432a0"),
 *                 @OA\Property(property="osa_code", type="string", example="OSA123"),
 *                 @OA\Property(property="bank_name", type="string", example="First National Bank"),
 *                 @OA\Property(property="branch", type="string", example="Downtown Branch"),
 *                 @OA\Property(property="city", type="string", example="New York"),
 *                 @OA\Property(property="account_number", type="integer", example=1234567890),
 *                 @OA\Property(property="status", type="integer", example=1)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Bank not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="code", type="integer", example=404),
 *             @OA\Property(property="message", type="string", example="Bank not found"),
 *             @OA\Property(property="data", type="null", example=null)
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="message", type="string", example="An error occurred while fetching bank")
 *         )
 *     )
 * )
 */

public function show(string $uuid)
{
    $bank = $this->bankService->getByUuid($uuid);

    if (!$bank) {
        return response()->json([
            'status'  => 'error',
            'code'    => 404,
            'message' => 'Bank not found',
            'data'    => null
        ], 404);
    }

    return response()->json([
        'status'  => 'success',
        'code'    => 200,
        'message' => 'Bank fetched successfully',
        'data'    => new BankResource($bank)
    ]);
}

/**
 * @OA\Put(
 *     path="/api/settings/banks/update/{uuid}",
 *     operationId="updateBankByUuid",
 *     tags={"Banks"},
 *     summary="Update a bank by UUID",
 *     description="Update the details of a bank using its UUID.",
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         description="UUID of the bank to update",
 *         required=true,
 *         @OA\Schema(type="string", example="e6300d91-d15d-49d4-8c0e-c66f980432a0")
 *     ),
 *     @OA\Parameter(
 *         name="osa_code",
 *         in="query",
 *         description="OSA code of the bank",
 *         required=false,
 *         @OA\Schema(type="string", example="OSA123")
 *     ),
 *     @OA\Parameter(
 *         name="bank_name",
 *         in="query",
 *         description="Name of the bank",
 *         required=false,
 *         @OA\Schema(type="string", example="First National Bank")
 *     ),
 *     @OA\Parameter(
 *         name="branch",
 *         in="query",
 *         description="Branch of the bank",
 *         required=false,
 *         @OA\Schema(type="string", example="Downtown Branch")
 *     ),
 *     @OA\Parameter(
 *         name="city",
 *         in="query",
 *         description="City of the bank",
 *         required=false,
 *         @OA\Schema(type="string", example="New York")
 *     ),
 *     @OA\Parameter(
 *         name="account_number",
 *         in="query",
 *         description="Account number of the bank",
 *         required=false,
 *         @OA\Schema(type="integer", example=1234567890)
 *     ),
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         description="Status of the bank (0 or 1)",
 *         required=false,
 *         @OA\Schema(type="integer", enum={0,1}, example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Bank updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Bank updated successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="uuid", type="string", example="e6300d91-d15d-49d4-8c0e-c66f980432a0"),
 *                 @OA\Property(property="osa_code", type="string", example="OSA123"),
 *                 @OA\Property(property="bank_name", type="string", example="First National Bank"),
 *                 @OA\Property(property="branch", type="string", example="Downtown Branch"),
 *                 @OA\Property(property="city", type="string", example="New York"),
 *                 @OA\Property(property="account_number", type="integer", example=1234567890),
 *                 @OA\Property(property="status", type="integer", example=1)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Bank not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="code", type="integer", example=404),
 *             @OA\Property(property="message", type="string", example="Bank not found"),
 *             @OA\Property(property="data", type="null", example=null)
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="message", type="string", example="An error occurred while updating bank")
 *         )
 *     )
 * )
 */

public function update(BankUpdateRequest $request, string $uuid)
{
    $validatedData = $request->validated();
    $updatedBank = $this->bankService->updateBankByUuid($uuid, $validatedData);

    if (!$updatedBank) {
        return response()->json([
            'status'  => 'error',
            'code'    => 404,
            'message' => 'Bank not found',
            'data'    => null
        ], 404);
    }

    return response()->json([
        'status'  => 'success',
        'code'    => 200,
        'message' => 'Bank updated successfully',
        'data'    => new BankResource($updatedBank)
    ]);
}
}