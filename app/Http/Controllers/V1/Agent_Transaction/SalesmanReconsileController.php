<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\SalesmanReconsileRequest;
use App\Http\Resources\V1\Agent_Transaction\SalesmanReconsileHeaderResource;
use App\Http\Resources\V1\Agent_Transaction\SalesmanReconsileListResource;
use App\Http\Resources\V1\Agent_Transaction\SalesmanReconsileResource;
use App\Services\V1\Agent_Transaction\SalesmanReconsileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use App\Helpers\LogHelper;

class SalesmanReconsileController extends Controller
{
    protected SalesmanReconsileService $service;

    public function __construct(SalesmanReconsileService $service)
    {
        $this->service = $service;
    }


    public function list(Request $request): JsonResponse
    {
        try {

            $filters = $request->only([
                'salesman_id',
                'warehouse_id',
                'from_date',
                'to_date',
                'osa_code',
                'limit',
            ]);

            $records = $this->service->list($filters);

            return response()->json([
                'status' => 'success',
                'data' => SalesmanReconsileHeaderResource::collection($records),
                'pagination' => [
                    'current_page' => $records->currentPage(),
                    'per_page'     => $records->perPage(),
                    'total'        => $records->total(),
                    'last_page'    => $records->lastPage(),
                ],

            ], 200);
        } catch (Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch reconciliation list',
            ], 500);
        }
    }



    public function index(Request $request): JsonResponse
    {
        $salesmanId  = (int) $request->salesman_id;
        $invoiceDate = $request->invoice_date; // YYYY-MM-DD
        // dd($invoiceDate);
        $result = $this->service->getSalesmanItemSummary(
            $salesmanId,
            $invoiceDate
        );
        // dd($result);
        return response()->json([
            'status'             => 'success',
            'salesman_id'        => $salesmanId,
            'invoice_date'       => $invoiceDate,
            'grand_total_amount' => $result['grand_total_amount'],
            'count'              => $result['items']->count(),
            'data'               => $result['items'], // ✅ NO Resource
        ]);
    }


    public function store(SalesmanReconsileRequest $request): JsonResponse
    {
        try {

            $response = $this->service->create($request->validated());

            // 🔹 Already exists
            if ($response['status'] === 'exists') {
                return response()->json([
                    'status'  => 'exists',
                    'message' => $response['message'],
                ], 200);
            }
            if ($response) {
            LogHelper::store(
                '13',                        
                '125',                        
                'add',                          
                null,                    
                $response->getAttributes(),        
                auth()->id()                      
            );
        }

            // 🔹 Created successfully (HEADER + DETAILS)
            return response()->json([
                'status'  => 'created',
                'message' => $response['message'],
                'data'    => new SalesmanReconsileHeaderResource($response['data']),
            ], 201);
        } catch (Throwable $e) {

            // ❌ Any issue → no data added (transaction rolled back in service)

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create salesman reconciliation',
            ], 500);
        }
    }





    public function block(Request $request): JsonResponse
    {
        try {

            if (! $request->salesman_id) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'salesman_id is required',
                ], 422);
            }

            $salesman = $this->service->blockSalesman((int) $request->salesman_id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Salesman blocked successfully',
                'data'    => [
                    'salesman_id' => $salesman->id,
                    'is_block'    => $salesman->is_block,
                ],
            ]);
        } catch (Exception $e) {

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function getByUuid(string $uuid)
    {
        try {

            $header = SalesmanReconsileHeader::with([
                'details.item:id,name,erp_code'
            ])
                ->where('uuid', $uuid)
                ->whereNull('deleted_at')
                ->first();

            if (! $header) {
                return null;
            }

            return $header;
        } catch (Throwable $e) {

            Log::error('Salesman Reconciliation Fetch By UUID Failed', [
                'uuid'  => $uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception(
                'Unable to fetch salesman reconciliation details',
                500,
                $e
            );
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {

            $record = $this->service->getByUuid($uuid);

            if (! $record) {
                return response()->json([
                    'status'  => 'not_found',
                    'message' => 'Salesman reconciliation not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data'   => new SalesmanReconsileHeaderResource($record),
            ], 200);
        } catch (Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch salesman reconciliation',
            ], 500);
        }
    }
}
