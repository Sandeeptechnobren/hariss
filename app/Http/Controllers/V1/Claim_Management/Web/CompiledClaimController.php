<?php

namespace App\Http\Controllers\V1\Claim_Management\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Claim_Management\Web\CompiledClaimRequest;
use App\Http\Resources\V1\Claim_Management\Web\CompiledClaimResource;
use App\Services\V1\Claim_Management\Web\CompiledClaimService;
use Illuminate\Http\Request;
use App\Exports\CompiledClaimExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Claim_Management\Web\CompiledClaim;
use App\Http\Resources\V1\Claim_Management\Web\ClaimInvoiceDataResource;
use App\Helpers\CommonLocationFilter;
use Illuminate\Support\Facades\DB;
use App\Helpers\ApprovalHelper;

class CompiledClaimController extends Controller
{
    protected $service;
    public function __construct(CompiledClaimService $service)
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
            "message" => "Compiled Claim Listing",
            "data" => CompiledClaimResource::collection($data),
            "pagination" => [
                "total" => $data->total(),
                "per_page" => $data->perPage(),
                "current_page" => $data->currentPage(),
                "last_page" => $data->lastPage(),
            ]
        ]);
    }
    public function store(CompiledClaimRequest $request)
    {
        try {
            $claim = $this->service->create($request->validated());
            return response()->json([
                "status" => "success",
                "message" => "Claim Created Successfully",
                "data" => new CompiledClaimResource($claim)
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Failed to create claim",
                "error" => $e->getMessage(),
            ], 400);
        }
    }


    // public function export()
    // {
    //     $filters = request()->input('filters', []);
    //     $format = strtolower(request()->input('format', 'csv'));

    //     $filename = 'compiled_claims_' . now()->format('Ymd_His');
    //     $filePath = "exports/{$filename}";

    //     // Use MODEL instead of raw query
    //     $query = CompiledClaim::with('warehouse');  // relation used

    //     // Filters
    //     if (!empty($filters['warehouse_id'])) {
    //         $query->where('warehouse_id', $filters['warehouse_id']);
    //     }

    //     if (!empty($filters['claim_period'])) {
    //         $query->where('claim_period', $filters['claim_period']);
    //     }

    //     if (!empty($filters['status'])) {
    //         $query->where('status', $filters['status']);
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

    //     $export = new CompiledClaimExport($data);
    //     // dd($export);
    //     $filePath .= $format === 'xlsx' ? '.xlsx' : '.csv';

    //     $success = Excel::store(
    //         $export,
    //         $filePath,
    //         'public',
    //         $format === 'xlsx'
    //             ? \Maatwebsite\Excel\Excel::XLSX
    //             : \Maatwebsite\Excel\Excel::CSV
    //     );

    //     if (!$success) {
    //         throw new \Exception(strtoupper($format) . ' export failed.');
    //     }

    //     $appUrl = rtrim(config('app.url'), '/');
    //     $fullUrl = $appUrl . '/storage/app/public/' . $filePath;

    //     return response()->json(['url' => $fullUrl], 200);
    // }


    public function export()
    {
        $filters = request()->input('filter', []);
        $format = strtolower(request()->input('format', 'xlsx'));

        $filename = 'compiled_claims_' . now()->format('Ymd_His');
        $filePath = "exports/{$filename}";

        $fromDate = $filters['from_date'] ?? null;
        $toDate   = $filters['to_date'] ?? null;

        // warehouse filter (comma separated support)
        $warehouseIds = CommonLocationFilter::resolveWarehouseIds($filters);

        // ✅ fallback: direct warehouse_id support
        if (empty($warehouseIds) && !empty($filters['warehouse_id'])) {
            $warehouseIds = is_array($filters['warehouse_id'])
                ? $filters['warehouse_id']
                : explode(',', $filters['warehouse_id']);
        }

        // ✅ sanitize
        $warehouseIds = array_map('intval', array_filter($warehouseIds));


        $query = CompiledClaim::query()
            ->leftJoin('users as asm', function ($join) {
                $join->where('asm.role', 91)
                    ->whereRaw("asm.warehouse::jsonb @> ('[' || tbl_compiled_claim.warehouse_id || ']')::jsonb");
            })
            ->leftJoin('users as rsm', function ($join) {
                $join->where('rsm.role', 92)
                    ->whereRaw("rsm.warehouse::jsonb @> ('[' || tbl_compiled_claim.warehouse_id || ']')::jsonb");
            })
            ->select(
                'tbl_compiled_claim.*',
                'asm.name as asm_name',
                'rsm.name as rsm_name',
            )
            ->distinct('tbl_compiled_claim.id');
        if (!empty($warehouseIds)) {
            $query->whereIn('tbl_compiled_claim.warehouse_id', $warehouseIds);
        }

        // date filter
        if (!empty($fromDate)) {
            $query->whereDate('tbl_compiled_claim.created_at', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $query->whereDate('tbl_compiled_claim.created_at', '<=', $toDate);
        }

        // dd($query->count());
        // dd($query->count());
        $data = $query->get();

        if ($data->isEmpty()) {
            return response()->json([
                'message' => 'No data available for export'
            ], 404);
        }

        // ✅ attach approval data
        $data = $data->map(function ($item) {
            return \App\Helpers\ApprovalHelper::attach($item, 'CompiledClaim');
        });

        // ❗ FIX: remove resolve()
        $data = CompiledClaimResource::collection($data);

        $export = new CompiledClaimExport($data);

        $filePath .= $format === 'csv' ? '.xlsx' : '.xlsx';

        $success = Excel::store(
            $export,
            $filePath,
            'public',
            $format === 'xlsx'
                ? \Maatwebsite\Excel\Excel::XLSX
                : \Maatwebsite\Excel\Excel::CSV
        );

        if (!$success) {
            throw new \Exception(strtoupper($format) . ' export failed.');
        }

        $appUrl = rtrim(config('app.url'), '/');
        $fullUrl = $appUrl . '/storage/app/public/' . $filePath;

        return response()->json([
            'status' => 'success',
            'download_url' => $fullUrl,
        ], 200);
    }


    public function getAddCompiledData(Request $request)
    {
        $request->validate([
            "from_date" => "required|date",
            "to_date" => "required|date",
            "warehouse_id" => "required|string" // comma string aa rahi hai
        ]);

        // Convert comma string → array
        $warehouseIds = explode(',', $request->warehouse_id);

        // Replace warehouse_id in request payload
        $requestData = $request->all();
        $requestData['warehouse_id'] = $warehouseIds;

        $perPage = $request->per_page ?? 50;

        $result = $this->service->getAddCompiledData($requestData, $perPage);

        $compiledExists = $result['compiled_exists'];
        $data = $result['data'];

        return response()->json([
            "status" => "success",
            "compiled_exists" => $compiledExists,
            "message" => $compiledExists
                ? "Compiled claim exists. Invoice data excluded."
                : "Invoice Detail Filtered.",

            "data" => ClaimInvoiceDataResource::collection($data->items()),

            "pagination" => [
                "total" => $data->total(),
                "per_page" => $data->perPage(),
                "current_page" => $data->currentPage(),
                "last_page" => $data->lastPage(),
            ]
        ]);
    }


    public function globalFilter(Request $request)
    {
        $data = $this->service->globalFilter(
            $request->per_page ?? 50,
            $request->all()
        );

        return response()->json([
            "status" => "success",
            "message" => "Compiled Claim Listing",
            "data" => CompiledClaimResource::collection($data),
            "pagination" => [
                "total" => $data->total(),
                "per_page" => $data->perPage(),
                "current_page" => $data->currentPage(),
                "last_page" => $data->lastPage(),
            ]
        ]);
    }
}
