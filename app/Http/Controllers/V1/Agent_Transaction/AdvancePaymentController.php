<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\V1\Agent_Transaction\StoreAdvancePaymentRequest;
use App\Http\Requests\V1\Agent_Transaction\AdvancePaymentUpdateRequest;
use App\Http\Resources\V1\Agent_Transaction\AdvancePaymentResource;
use App\Services\V1\Agent_Transaction\AdvancePaymentService;
use App\Exports\AdvancePaymentsFullExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\LogHelper;

class AdvancePaymentController extends Controller
{
    protected $service;

    public function __construct(AdvancePaymentService $service)
    {
        $this->service = $service;
    }
/**
 * @OA\Post(
 *     path="/api/agent_transaction/advancepayments/create",
 *     operationId="createAdvancePayment",
 *     tags={"Advance Payments"},
 *     summary="Create a new advance payment",
 *     security={{"bearerAuth":{}}},
 *     description="Creates a new advance payment record. Accepts multipart/form-data to handle receipt image upload.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 required={"payment_type", "companybank_id", "amount", "recipt_no", "recipt_date"},
 *                 @OA\Property(
 *                     property="osa_code",
 *                     type="string",
 *                     maxLength=50,
 *                     example="OSA-001"
 *                 ),
 *                 @OA\Property(
 *                     property="payment_type",
 *                     type="integer",
 *                     description="1: Cash, 2: Cheque, 3: Transfer",
 *                     enum={1,2,3},
 *                     example=1
 *                 ),
 *                 @OA\Property(
 *                     property="companybank_id",
 *                     type="integer",
 *                     example=5
 *                 ),
 *                 @OA\Property(
 *                     property="amount",
 *                     type="number",
 *                     format="float",
 *                     example=1500.50
 *                 ),
 *                 @OA\Property(
 *                     property="recipt_no",
 *                     type="string",
 *                     maxLength=50,
 *                     example="RCPT-2025-001"
 *                 ),
 *                 @OA\Property(
 *                     property="recipt_date",
 *                     type="string",
 *                     format="date",
 *                     example="2025-11-04"
 *                 ),
 *                 @OA\Property(
 *                     property="recipt_image",
 *                     type="string",
 *                     format="binary",
 *                     nullable=true,
 *                     description="Upload receipt image (optional)"
 *                 ),
 *                 @OA\Property(
 *                     property="cheque_no",
 *                     type="string",
 *                     maxLength=50,
 *                     nullable=true,
 *                     example="CHQ123456"
 *                 ),
 *                 @OA\Property(
 *                     property="cheque_date",
 *                     type="string",
 *                     format="date",
 *                     nullable=true,
 *                     example="2025-11-10"
 *                 ),
 *                 @OA\Property(
 *                     property="status",
 *                     type="integer",
 *                     description="Status of the advance payment",
 *                     nullable=true,
 *                     example=1
    *                   ),
 *                 @OA\Property(
 *                     property="agent_id",
 *                     type="integer",
 *                     nullable=true,
 *                     example=12
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Advance payment created successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="osa_code", type="string", example="OSA-001"),
 *             @OA\Property(property="amount", type="number", example=1500.50),
 *             @OA\Property(property="recipt_image", type="string", example="advance_payments/receipt1.jpg")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     )
 * )
 */
public function store(StoreAdvancePaymentRequest $request)
    {

        $advancePayment = $this->service->create($request->validated());
          if ($advancePayment) {
            LogHelper::store(
                '13',                        
                '76',                        
                'add',                          
                null,                    
                $advancePayment->getAttributes(),        
                auth()->id()                      
            );
        }
        return response()->json([
        'status'     => 'success',
        'code'       => 201,
        'message'    => 'Advance payments created successfully',
        'data'       => new AdvancePaymentResource($advancePayment),
        ]);
    }

public function index(Request $request)
    {
        $perPage = $request->get('per_page', 50);

        $filters = $request->only([
            'payment_type',
            'osa_code',
            'companybank_id',
            'agent_id'
        ]);
        $data = $this->service->list(
            [
                'payment_type' => $filters['payment_type'] ?? null,
                'osa_code' => $filters['osa_code'] ?? null,
                'companybank_id' => $filters['companybank_id'] ?? null,
                'agent_id' => $filters['agent_id'] ?? null,
            ],
            $perPage
        );
        return response()->json([
            'status'     => 'success',
            'code'       => 200,
            'message'    => 'Advance payments fetched successfully',
            'data'       => AdvancePaymentResource::collection($data->items()),
            'pagination' => [
                'page'         => $data->currentPage(),
                'limit'        => $data->perPage(),
                'totalPages'   => $data->lastPage(),
                'totalRecords' => $data->total(),
            ]
        ]);
    }

public function show($uuid)
{
    $payment = $this->service->getByUuid($uuid);

    if (!$payment) {
        return response()->json([
            'status'  => 'error',
            'code'    => 404,
            'message' => 'Advance payment not found',
        ], 404);
    }

    return response()->json([
        'status'  => 'success',
        'code'    => 200,
        'message' => 'Advance payment details fetched successfully',
        'data'    => new AdvancePaymentResource($payment),
    ]);
}

public function update(AdvancePaymentUpdateRequest $request, $uuid)
    {
        $payment = $this->service->updateByUuid($uuid, $request->validated());
        if (!$payment) {
            return response()->json([
                'status'  => 'error',
                'code'    => 404,
                'message' => 'Advance payment not found',
            ], 404);
        }
        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Advance payment updated successfully',
            'data'    => new AdvancePaymentResource($payment),
        ]);
    }

public function getBankDetails($id)
    {
        $customer = $this->service->getBankDetailsById($id);

        if (!$customer) {
            return response()->json([
                'status'  => 'error',
                'code'    => 404,
                'message' => 'Customer not found',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Bank details fetched successfully',
            'data'    => [
                'bank_name' => $customer->bank_name,
                'account_number' => $customer->bank_account_number,
            ]
        ]);
    }
public function exportAdvancePaymentHeader(Request $request)
{
    $uuid = $request->input('uuid'); 
    $format = strtolower($request->input('format', 'xlsx'));
    $extension = $format === 'csv' ? 'csv' : 'xlsx';
    $filename = 'advance_payments_export_' . now()->format('Ymd_His') . '.' . $extension;
    $path = 'advancepaymentexports/' . $filename;

    $export = new AdvancePaymentsFullExport($uuid);
    if ($format === 'csv') {
        Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
    } else {
        Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
    }

    $appUrl = rtrim(config('app.url'), '/');
    $fullUrl = $appUrl . '/storage/app/public/' . $path;

    return response()->json([
        'status' => 'success',
        'uuid' => $uuid,
        'download_url' => $fullUrl,
    ]);
}

}
