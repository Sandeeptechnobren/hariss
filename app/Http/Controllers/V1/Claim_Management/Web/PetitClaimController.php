<?php

namespace App\Http\Controllers\V1\Claim_Management\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Claim_Management\Web\PetitClaimRequest;
use App\Http\Resources\V1\Claim_Management\Web\PetitClaimResource;
use App\Services\V1\Claim_Management\Web\PetitClaimService;
use Illuminate\Http\Request;
use Exception;
use App\Models\Claim_Management\Web\PetitClaim;
use App\Exports\PetitClaimExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use App\Helpers\CommonLocationFilter;


class PetitClaimController extends Controller
{
    protected $service;
    public function __construct(PetitClaimService $service)
    {
        $this->service = $service;
    }
    public function index(Request $request)
    {
        $data = $this->service->getAll(
            $request->per_page ?? 50,
            $request->all()
        );
        return response()->json([
            "status" => "success",
            "message" => "Petit Claim Listing",
            "data" => PetitClaimResource::collection($data),
            "pagination" => [
                "total" => $data->total(),
                "per_page" => $data->perPage(),
                "current_page" => $data->currentPage(),
                "last_page" => $data->lastPage(),
            ]
        ]);
    }

    public function showByUuid($uuid): JsonResponse
    {
        try {
            $claim = $this->service->getByUuid($uuid);

            return response()->json([
                'status' => true,
                'message' => 'Petit claim fetched successfully',
                'data' => new PetitClaimResource($claim), // ✅ FIX
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function store(PetitClaimRequest $request)
    {
        try {
            $data = $request->validated();
            if ($request->hasFile('claim_file')) {
                $data['claim_file'] = $request->file('claim_file');
            }

            $claim = $this->service->create($data);

            return response()->json([
                "status" => "success",
                "message" => "Petit Claim Created Successfully",
                "data" => new PetitClaimResource($claim)
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Failed to create petit claim",
                "error" => $e->getMessage(),
            ], 400);
        }
    }

    // public function export()
    // {
    //     $filters = request()->input('filters', []);
    //     $format = strtolower(request()->input('format', 'csv'));

    //     $filename = 'petit_claims_' . now()->format('Ymd_His');
    //     $filePath = "exports/{$filename}";
    //     $query = PetitClaim::with('warehouse');
    //     if (!empty($filters['warehouse_id'])) {
    //         $query->where('warehouse_id', $filters['warehouse_id']);
    //     }
    //     if (!empty($filters['status'])) {
    //         $query->where('status', $filters['status']);
    //     }
    //     if (!empty($filters['claim_type'])) {
    //         $query->where('claim_type', $filters['claim_type']);
    //     }
    //     if (!empty($filters['from_date'])) {
    //         $query->whereDate('created_at', '>=', $filters['from_date']);
    //     }
    //     if (!empty($filters['to_date'])) {
    //         $query->whereDate('created_at', '<=', $filters['to_date']);
    //     }
    //     $data = $query->get();

    //     if ($data->isEmpty()) {
    //         return response()->json(['message' => 'No data available for export'], 404);
    //     }
    //     $export = new PetitClaimExport($data);
    //     $filePath .= $format === 'xlsx' ? '.xlsx' : '.csv';
    //     Excel::store(
    //         $export,
    //         $filePath,
    //         'public',
    //         $format === 'xlsx'
    //             ? \Maatwebsite\Excel\Excel::XLSX
    //             : \Maatwebsite\Excel\Excel::CSV
    //     );
    //     $appUrl = rtrim(config('app.url'), '/');
    //     $fullUrl = $appUrl . '/storage/app/public/' . $filePath;
    //     return response()->json(['url' => $fullUrl], 200);
    // }


    public function export(Request $request)
    {
        $filters = $request->input('filter', []);
        $format = strtolower($request->input('format', 'xlsx'));

        $filename = 'PetitPromotion_' . now()->format('Ymd_His');
        $filePath = "exports/{$filename}";

        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;

        // $salesmanIds = !empty($filters['salesman_id'])
        //     ? explode(',', $filters['salesman_id'])
        //     : [];

        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        $export = new PetitClaimExport(
            $fromDate,
            $toDate,
            $warehouseIds
        );

        $filePath .= $format === 'csv' ? '.xlsx' : '.xlsx';

        Excel::store(
            $export,
            $filePath,
            'public',
            $format === 'xlsx'
                ? \Maatwebsite\Excel\Excel::XLSX
                : \Maatwebsite\Excel\Excel::CSV
        );
        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $filePath;
        return response()->json(['url' => $fullUrl], 200);
    }

    public function globalFilter(Request $request)
    {
        $data = $this->service->globalFilter(
            $request->per_page ?? 50,
            $request->all()
        );

        return response()->json([
            "status" => "success",
            "message" => "Petit Claim Listing",
            "data" => PetitClaimResource::collection($data),
            "pagination" => [
                "total" => $data->total(),
                "per_page" => $data->perPage(),
                "current_page" => $data->currentPage(),
                "last_page" => $data->lastPage(),
            ]
        ]);
    }
}
