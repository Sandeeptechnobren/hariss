<?php

namespace App\Services\V1\Assets\Web;

use App\Models\ChillerRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Helpers\ApprovalHelper;
use App\Helpers\CommonLocationFilter;
use App\Helpers\DataAccessHelper;
use Throwable;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;


class ChillerRequestService
{
    public function getAll(int $perPage = 50, array $filters = [], bool $dropdown = false)
    {
        try {
            $user = auth()->user();

            if ($dropdown) {
                $query = ChillerRequest::select(['id', 'asset_code', 'asset_name'])
                    ->orderBy('asset_name', 'asc');

                foreach ($filters as $field => $value) {
                    if (empty($value)) continue;

                    if (in_array($field, ['asset_code', 'asset_name'])) {
                        $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                    } elseif ($field === 'warehouse_id') {
                        $query->where('warehouse_id', $value);
                    } else {
                        $query->where($field, $value);
                    }
                }

                return $query->get();
            }

            $query = ChillerRequest::with([
                'warehouse:id,warehouse_code,warehouse_name,area_id',
                'createdBy:id,name,username',
                'updatedBy:id,name,username',
                'customer:id,osa_code,name,district,category_id',
                'customer.category:id,customer_category_name'
            ])->orderBy('id', 'desc');

            $query = \App\Helpers\DataAccessHelper::filterAssets($query, $user);

            if (!empty($filters) && isset($filters['filter']) && is_array($filters['filter'])) {

                $warehouseIds = \App\Helpers\CommonLocationFilter::resolveWarehouseIds([
                    'company'   => $filters['filter']['company_id']   ?? null,
                    'region'    => $filters['filter']['region_id']    ?? null,
                    'area'      => $filters['filter']['area_id']      ?? null,
                    'warehouse' => $filters['filter']['warehouse_id'] ?? null,
                    'route'     => $filters['filter']['route_id']     ?? null,
                ]);

                if (!empty($warehouseIds)) {
                    $query->whereIn('warehouse_id', $warehouseIds);
                }
            }
            foreach ($filters as $field => $value) {

                if ($field === 'filter') continue;
                if ($value === null || $value === '') continue;

                // 🔹 TEXT SEARCH
                if (in_array($field, ['asset_code', 'asset_name'])) {
                    $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                } elseif (in_array($field, ['warehouse_id', 'salesman_id'])) {

                    $ids = collect(explode(',', $value))
                        ->map(fn($id) => trim($id))
                        ->filter(fn($id) => is_numeric($id))
                        ->values()
                        ->all();

                    if (!empty($ids)) {
                        $query->whereIn($field, $ids);
                    }

                    // 🔹 DEFAULT FILTER
                } else {
                    $query->where($field, $value);
                }
            }
            // 🔥 DEFAULT CURRENT MONTH FILTER
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth   = Carbon::now()->endOfMonth();
            $query->whereBetween('created_at', [$startOfMonth, $endOfMonth]);

            $query->orderBy('created_at', 'desc');

            $result = $query->paginate($perPage);
            $result->getCollection()->transform(function ($item) {

                if ($item->customer_id) {
                    $item->last_three_month_sales =
                        $this->calculateLastThreeMonthSales($item->customer_id);
                } else {
                    $item->last_three_month_sales = 0;
                }

                return ApprovalHelper::attach($item, 'Chiller_Request');
            });
            $result->getCollection()->transform(function ($item) {
                return ApprovalHelper::attach($item, 'Chiller_Request');
            });
            return $result;
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch assets: " . $e->getMessage());
        }
    }

    private function calculateLastThreeMonthSales(int $customerId): float
    {
        $latestInvoiceDate = DB::table('invoice_headers')
            ->where('customer_id', $customerId)
            ->max('invoice_date');

        if (!$latestInvoiceDate) {
            return 0;
        }

        $threeMonthsAgo = Carbon::parse($latestInvoiceDate)
            ->subMonths(3)
            ->toDateString();

        return (float) DB::table('invoice_headers as ih')
            ->join('invoice_details as id', 'ih.id', '=', 'id.header_id')
            ->where('ih.customer_id', $customerId)
            ->where('ih.invoice_date', '>=', $threeMonthsAgo)
            ->sum('id.item_total');
    }

    public function generateCode(): string
    {
        do {
            $last = ChillerRequest::withTrashed()->latest('id')->first();
            $next = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'CR' . str_pad($next, 3, '0', STR_PAD_LEFT);
        } while (ChillerRequest::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }

    public function create(array $data): ChillerRequest
    {
        DB::beginTransaction();
        try {

            $data = array_merge($data, [
                'osa_code' => $this->generateCode(),
                'uuid'     => $data['uuid'] ?? Str::uuid()->toString(),
            ]);

            $fileColumns = [
                'password_photo_file',
                'lc_letter_file',
                'trading_licence_file',
                'outlet_stamp_file',
                'outlet_address_proof_file',
                'sign__customer_file',
                'national_id_file'
            ];

            foreach ($fileColumns as $col) {
                if (!empty($data[$col]) && $data[$col] instanceof \Illuminate\Http\UploadedFile) {
                    $filename = time() . '_' . $data[$col]->getClientOriginalName();
                    $data[$col]->storeAs('public/chillers', $filename);
                    $data[$col] = $filename;
                }
            }

            $chiller = ChillerRequest::create($data);

            DB::commit();
            $workflow = DB::table('htapp_workflow_assignments')
                ->where('process_type', 'Chiller_Request')
                ->where('is_active', true)
                ->first();

            if ($workflow) {
                app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                    ->startApproval([
                        "workflow_id"  => $workflow->workflow_id,
                        "process_type" => "Chiller_Request",
                        "process_id"   => $chiller->id
                    ]);
            }

            return $chiller;
        } catch (Throwable $e) {

            DB::rollBack();

            Log::error("Chiller Request creation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data'  => $data,
                'user'  => Auth::id(),
            ]);

            throw new \Exception("Something went wrong, please try again.", 0, $e);
        }
    }

    // public function findByUuid(string $uuid): ?ChillerRequest
    // {
    //     if (!Str::isUuid($uuid)) {
    //         throw new \Exception("Invalid UUID format: {$uuid}");
    //     }

    //     $chiller = ChillerRequest::where('uuid', $uuid)->first();

    //     if (!$chiller) {
    //         return null;
    //     }

    //     $workflowRequest = \DB::table('htapp_workflow_requests')
    //         ->where('process_type', 'Chiller_Request')
    //         ->where('process_id', $chiller->id)
    //         ->latest()
    //         ->first();

    //     $chiller->approval_status = null;
    //     $chiller->current_step    = null;
    //     $chiller->request_step_id = null;
    //     $chiller->progress        = null;

    //     if ($workflowRequest) {

    //         $currentStep = \DB::table('htapp_workflow_request_steps')
    //             ->where('workflow_request_id', $workflowRequest->id)
    //             ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
    //             ->orderBy('step_order')
    //             ->first();

    //         $totalSteps = \DB::table('htapp_workflow_request_steps')
    //             ->where('workflow_request_id', $workflowRequest->id)
    //             ->count();

    //         $approvedSteps = \DB::table('htapp_workflow_request_steps')
    //             ->where('workflow_request_id', $workflowRequest->id)
    //             ->where('status', 'APPROVED')
    //             ->count();

    //         $lastApprovedStep = \DB::table('htapp_workflow_request_steps')
    //             ->where('workflow_request_id', $workflowRequest->id)
    //             ->where('status', 'APPROVED')
    //             ->orderBy('step_order', 'desc')
    //             ->first();

    //         $chiller->approval_status = $lastApprovedStep
    //             ? $lastApprovedStep->message
    //             : 'Initiated';

    //         $chiller->current_step    = $currentStep->title ?? null;
    //         $chiller->request_step_id = $currentStep->id ?? null;
    //         $chiller->progress        = $totalSteps > 0
    //             ? "{$approvedSteps}/{$totalSteps}"
    //             : null;
    //     }

    //     return $chiller;
    // }
    public function findByUuid(string $uuid): ?ChillerRequest
    {
        if (!Str::isUuid($uuid)) {
            throw new \Exception("Invalid UUID format: {$uuid}");
        }

        $chiller = ChillerRequest::where('uuid', $uuid)->first();

        if (!$chiller) {
            return null;
        }

        return ApprovalHelper::attach($chiller, 'Chiller_Request');
    }

    public function updateByUuid(string $uuid, array $data): chillerRequest
    {

        $chiller = ChillerRequest::where("uuid", $uuid)->firstOrfail();
        if (!$chiller) {
            throw new \Exception("Chiller not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $fileColumns = [
                'password_photo_file',
                'lc_letter_file',
                'trading_licence_file',
                'outlet_stamp_file',
                'outlet_address_proof_file',
                'sign__customer_file',
                'national_id_file'
            ];

            foreach ($fileColumns as $col) {

                if (
                    !array_key_exists($col, $data) ||
                    !$data[$col] instanceof UploadedFile
                ) {
                    unset($data[$col]);
                    continue;
                }

                $originalName = $data[$col]->getClientOriginalName();
                $filename = time() . '_' . $originalName;

                $path = $data[$col]->storeAs('chillers', $filename, 'public');

                // ✅ Store same format: storage/chillers/filename.jpg
                $data[$col] = 'storage/' . $path;
            }

            // dd($data);
            $chiller->fill($data);
            $chiller->save();

            DB::commit();

            return $chiller;
        } catch (\Throwable $e) {
            // dd($e);
            DB::rollBack();

            Log::error("Chiller update failed", [
                'error'   => $e->getMessage(),
                'uuid'    => $uuid,
                'payload' => $data,
            ]);

            throw new \Exception("Something went wrong, please try again.", 0, $e);
        }
    }


    public function deleteByUuid(string $uuid): void
    {
        $chillerRequest = $this->findByUuid($uuid);
        if (!$chillerRequest) {
            throw new \Exception("ChillerRequest not found for UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $chillerRequest->deleted_user = Auth::id();
            $chillerRequest->save();
            $chillerRequest->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("ChillerRequest delete failed", [
                'error' => $e->getMessage(),
                'uuid'  => $uuid,
            ]);

            throw new \Exception("Something went wrong while deleting Chiller Request: " . $e->getMessage(), 0, $e);
        }
    }

    public function globalSearch($perPage = 10, $searchTerm = null)
    {
        try {
            $query = ChillerRequest::with([
                'salesman:id,osa_code,name',
                'route:id,route_code,route_name',
                'customer:id,osa_code,name',
                'createdBy:id,username',
                'updatedBy:id,username',
            ]);

            if (!empty($searchTerm)) {
                $searchTerm = strtolower($searchTerm);
                $likeSearch = '%' . $searchTerm . '%';

                $query->where(function ($q) use ($likeSearch) {
                    $q->orWhereRaw('LOWER(osa_code) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(owner_name) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(asset_number) LIKE ?', [$likeSearch]);
                });
            }

            $paginator = $query->paginate($perPage);
            $paginator->getCollection()->transform(function ($item) {
                return ApprovalHelper::attach($item, 'Chiller_Request');
            });

            return $paginator;
        } catch (\Exception $e) {
            Log::error('ChillerRequest search failed', [
                'error'  => $e->getMessage(),
                'search' => $searchTerm,
            ]);

            throw new \Exception(
                'Failed to search Chiller Requests: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }


    public function filterChillerRequests(array $filters)
    {
        $query = ChillerRequest::query();

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['model_number'])) {
            $query->where('model_number', $filters['model_number']);
        }

        if (!empty($filters['salesman_id'])) {
            $query->where('salesman_id', $filters['salesman_id']);
        }

        $query->where('status', '!=', 5);

        $query->whereIn('id', function ($q) {
            $q->select('process_id')
                ->from('htapp_workflow_requests')
                ->where('process_type', 'Approved_CRF_Request')
                ->whereIn('id', function ($q2) {
                    $q2->select('workflow_request_id')
                        ->from('htapp_workflow_request_steps')
                        ->whereIn('status', ['APPROVED', 'PENDING']);
                });
        });
        $paginated = $query->paginate(50);

        $chillerRequestIds = $paginated->pluck('id')->toArray();

        if (empty($chillerRequestIds)) {
            return $paginated;
        }

        $workflowRequests = DB::table('htapp_workflow_requests')
            ->where('process_type', 'Approved_CRF_Request')
            ->whereIn('process_id', $chillerRequestIds)
            ->pluck('id', 'process_id');

        $workflowIds = $workflowRequests->values();

        $approvedSteps = DB::table('htapp_workflow_request_steps')
            ->whereIn('workflow_request_id', $workflowIds)
            ->where('status', 'APPROVED')
            ->get()
            ->groupBy('workflow_request_id');

        $pendingSteps = DB::table('htapp_workflow_request_steps')
            ->whereIn('workflow_request_id', $workflowIds)
            ->where('status', 'PENDING')
            ->get()
            ->groupBy('workflow_request_id');

        $paginated->getCollection()->transform(function ($item) use ($workflowRequests, $approvedSteps, $pendingSteps) {
            $workflowId = $workflowRequests[$item->id] ?? null;

            return [
                'chiller_request'     => $item,
                'workflow_request_id' => $workflowId,
                'approved_steps'      => $approvedSteps[$workflowId] ?? [],
                'pending_steps'       => $pendingSteps[$workflowId] ?? [],
            ];
        });

        return $paginated;
    }


    public function getApprovedChillerRequests(array $filters)
    {
        // 1. Filter users
        $usersQuery = User::query();

        if (!empty($filters['region'])) {
            $usersQuery->whereJsonContains('region', (int)$filters['region']);
        }

        if (!empty($filters['area'])) {
            $usersQuery->whereJsonContains('area', (int)$filters['area']);
        }

        if (!empty($filters['warehouse'])) {
            $usersQuery->whereJsonContains('warehouse', (int)$filters['warehouse']);
        }

        $userIds = $usersQuery->pluck('id')->toArray();

        if (empty($userIds)) {
            return [];
        }

        $chillerRequestIds = ChillerRequest::whereIn('created_user', $userIds)
            ->orWhereIn('updated_user', $userIds)
            ->pluck('id')
            ->toArray();

        if (empty($chillerRequestIds)) {
            return [];
        }

        $workflowRequests = DB::table('htapp_workflow_requests')
            ->where('process_type', 'CHILLER_REQUEST')
            ->whereIn('process_id', $chillerRequestIds)
            ->pluck('id', 'process_id');

        if ($workflowRequests->isEmpty()) {
            return [];
        }

        // 4. Get APPROVED workflow steps
        $approvedSteps = DB::table('htapp_workflow_request_steps')
            ->whereIn('workflow_request_id', $workflowRequests->values())
            ->where('status', 'APPROVED')
            ->get()
            ->groupBy('workflow_request_id');

        if ($approvedSteps->isEmpty()) {
            return [];
        }

        // 5. Build final response
        $result = [];

        foreach ($workflowRequests as $chillerId => $workflowRequestId) {

            // Skip non-approved
            if (!isset($approvedSteps[$workflowRequestId])) {
                continue;
            }

            $result[] = [
                'chiller_request'     => ChillerRequest::find($chillerId),
                'workflow_request_id' => $workflowRequestId,
                'steps'               => $approvedSteps[$workflowRequestId],
            ];
        }

        return $result;
    }

    public function exportCRFRequests(
        $status = null,
        $region_id = null,
        $user_id = null,
        $warehouse_id = null,
        $route_id = null,
        $salesman_id = null,
        $model_id = null
    ) {
        $region_id     = $region_id     ? explode(',', $region_id)     : [];
        $user_id       = $user_id       ? explode(',', $user_id)       : [];
        $warehouse_id  = $warehouse_id  ? explode(',', $warehouse_id)  : [];
        $route_id      = $route_id      ? explode(',', $route_id)      : [];
        $salesman_id   = $salesman_id   ? explode(',', $salesman_id)   : [];
        $model_id      = $model_id      ? explode(',', $model_id)      : [];

        $q = DB::table('chiller_requests AS ch')
            ->select([
                'iro.status AS status_iro',
                'ch.id AS crf_id',

                'ac.name AS customer_name',
                'ac.osa_code AS customer_code',
                'ac.street AS city',
                'ac.district',
                'ac.contact_no AS phone1',
                'ac.contact_no2 AS phone2',

                'fr.osa_code AS fridge_code',
                'fr.serial_number',
                'fr.model_number',
                'fr.branding AS type',

                'wh.warehouse_name',
                'wh.warehouse_code',

                'ar.area_code AS region_code',
                'ar.area_name AS region_name',

                'rt.route_code',
                'rt.route_name',

                'sm.osa_code AS salesman_code',
                'sm.name AS salesman_name',

                'ch.created_at'
            ])

            ->leftJoin('tbl_iro_headers AS iro', 'iro.id', '=', 'ch.iro_id')
            ->leftJoin('agent_customers AS ac', 'ac.id', '=', 'ch.customer_id')
            ->leftJoin('tbl_add_chillers AS fr', 'fr.id', '=', 'ac.fridge_id')
            ->leftJoin('tbl_warehouse AS wh', 'wh.id', '=', 'ch.warehouse_id')
            ->leftJoin('tbl_areas AS ar', 'ar.id', '=', 'wh.area_id')
            ->leftJoin('tbl_route AS rt', 'rt.id', '=', 'ac.route_id')
            ->leftJoin('salesman AS sm', 'sm.id', '=', 'ch.salesman_id')

            /** JSON SAFE JOIN FOR USERS */
            ->leftJoin('users AS u', function ($j) {
                $j->on(DB::raw("
                CASE WHEN jsonb_typeof(u.area::jsonb) = 'array'
                        THEN ((u.area::jsonb)->>0)::int
                     ELSE (u.area::text)::int
                END
            "), '=', DB::raw("wh.area_id"))
                    ->whereIn('u.role', [1])
                    ->where('u.status', 1);
            });


        /** STATUS FILTER */
        if ($status !== null) {
            $q->where('ch.status', (int)$status);
        }

        if (!empty($warehouse_id)) {
            $q->whereIn('ch.warehouse_id', $warehouse_id);
        }
        if (!empty($region_id)) {
            $q->whereIn('wh.region_id', $region_id);
        }
        if (!empty($route_id)) {
            $q->whereIn('ac.route_id', $route_id);
        }
        if (!empty($salesman_id)) {
            $q->whereIn('ch.salesman_id', $salesman_id);
        }
        if (!empty($user_id)) {
            $q->whereIn('u.id', $user_id);
        }

        /** MODEL FILTER */
        if (!empty($model_id)) {
            $sizes = DB::table('am_model_number')
                ->whereIn('id', $model_id)
                ->pluck('size')
                ->toArray();
            if (!empty($sizes)) {
                $q->whereIn('ch.model_number', $sizes);
            }
        }

        $q->orderBy('ch.id', 'DESC');

        return $q->get();
    }



    public function getAgreementByIrDetailId($id)
    {
        try {
            $userId = Auth::id();
            if (!$userId || !$id) {
                return null;
            }

            return DB::table('tbl_ir_details as d')
                ->join('tbl_ir_headers as h', 'h.id', '=', 'd.header_id')
                ->leftJoin('chiller_requests as cr', 'cr.id', '=', 'd.crf_id')
                ->leftJoin('tbl_add_chillers as ac', 'ac.id', '=', 'd.fridge_id')
                ->leftJoin('agent_customers as cust', 'cust.id', '=', 'ac.customer_id')
                ->leftJoin('tbl_route as rt', 'rt.id', '=', 'cust.route_id')
                ->leftJoin('tbl_agreement as ag', 'ag.id', '=', 'd.agreement_id')
                ->leftJoin('tbl_iro_headers as iro', 'iro.id', '=', 'h.iro_id')
                ->leftJoin('salesman as sm', 'sm.id', '=', 'h.salesman_id')
                ->leftJoin('users as u', 'u.id', '=', 'iro.created_user')
                ->select([
                    'd.id as ir_detail_id',

                    'h.osa_code',
                    'h.created_at as ir_date',

                    'cr.owner_name',
                    'cr.outlet_id',
                    'cr.location',

                    'ac.assets_category',
                    'ac.serial_number',
                    'ac.model_number',
                    'ac.branding',

                    'cust.name as customer_name',
                    'cust.district',
                    'cust.contact_no',

                    'rt.route_code',
                    'rt.route_name',

                    'ag.installed_img1',
                    'ag.installed_img2',
                    'ag.created_at as agreement_date',

                    'sm.name as salesman_name',
                    'sm.osa_code as salesman_code',

                    'u.name as ka_name'
                ])
                ->where('d.id', $id)
                ->where(function ($q) use ($userId) {
                    $q->where('h.created_user', $userId)
                        ->orWhere('h.salesman_id', $userId)
                        ->orWhere('iro.created_user', $userId);
                })
                ->first();
        } catch (Throwable $e) {
            Log::error('Agreement PDF data fetch failed', [
                'ir_detail_id' => $id,
                'user_id'      => Auth::id(),
                'file'         => $e->getFile(),
                'line'         => $e->getLine(),
                'message'      => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getCRFData(int $perPage = 50, array $filters = [])
    {
        try {
            $user   = auth()->user();
            $filter = $filters['filter'] ?? [];

            $query = ChillerRequest::query()
                ->select([
                    'id',
                    'uuid',
                    'osa_code',
                    'owner_name',
                    'contact_number',
                    'warehouse_id',
                    'salesman_id',
                    'model',
                    'customer_id',
                    'outlet_id',
                    'status',
                    'created_user',
                    'created_at',
                ])
                ->with([
                    'warehouse:id,warehouse_name,warehouse_code',
                    'salesman:id,name,osa_code',
                    'modelNumber:id,code,name',
                    'customer:id,osa_code,name',
                    'outlet:id,outlet_channel',
                    'createdBy:id,username',
                ])
                ->where('fridge_status', 0)
                ->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('htapp_workflow_requests as wr')
                        ->whereColumn('wr.process_id', 'chiller_requests.id')
                        ->where('wr.process_type', 'Chiller_Request')
                        ->whereExists(function ($step) {
                            $step->select(DB::raw(1))
                                ->from('htapp_workflow_request_steps as ws')
                                ->whereColumn('ws.workflow_request_id', 'wr.id')
                                ->where('ws.status', 'APPROVED')
                                ->whereRaw("
                                ws.step_order = (
                                    SELECT MAX(s2.step_order)
                                    FROM htapp_workflow_request_steps s2
                                    WHERE s2.workflow_request_id = wr.id
                                    AND s2.status = 'APPROVED'
                                )
                            ");
                        });
                });

            if (method_exists(DataAccessHelper::class, 'filterAgentTransaction')) {
                $query = DataAccessHelper::filterAgentTransaction($query, $user);
            }

            $warehouseIds = $this->parseIds($filter['warehouse_id'] ?? null);

            if (!empty($warehouseIds)) {
                $query->whereIn('warehouse_id', $warehouseIds);
            } elseif (!empty($filter)) {
                $resolved = CommonLocationFilter::resolveWarehouseIds([
                    'company'   => $filter['company_id'] ?? null,
                    'region'    => $filter['region_id'] ?? null,
                    'area'      => $filter['area_id'] ?? null,
                    'warehouse' => null,
                    'route'     => $filter['route_id'] ?? null,
                ]);

                if (!empty($resolved)) {
                    $query->whereIn('warehouse_id', $resolved);
                }
            }

            // 🔹 Salesman
            $salesmanIds = $this->parseIds($filter['salesman_id'] ?? null);
            if (!empty($salesmanIds)) {
                $query->whereIn('salesman_id', $salesmanIds);
            }

            // 🔹 Model
            $modelIds = $this->parseIds($filter['model_id'] ?? null);
            if (!empty($modelIds)) {
                $query->whereIn('model', $modelIds);
            }

            // 🔹 Date
            if (!empty($filter['from_date']) || !empty($filter['to_date'])) {
                $from = !empty($filter['from_date'])
                    ? Carbon::parse($filter['from_date'])->startOfDay()
                    : null;

                $to = !empty($filter['to_date'])
                    ? Carbon::parse($filter['to_date'])->endOfDay()
                    : null;

                $query->when($from && $to, fn($q) => $q->whereBetween('created_at', [$from, $to]))
                    ->when($from && !$to, fn($q) => $q->where('created_at', '>=', $from))
                    ->when(!$from && $to, fn($q) => $q->where('created_at', '<=', $to));
            }
            return $query->latest()->paginate($perPage)
                ->through(function ($item) {
                    $item->makeHidden([
                        'warehouse_id',
                        'salesman_id',
                        'customer_id',
                        'outlet_id',
                        'created_user',
                    ]);

                    $item->salesman?->makeHidden([
                        'route',
                        'route_name',
                        'route_id',
                    ]);

                    return $item;
                });
        } catch (Throwable $e) {
            Log::error('ChillerRequestService::getCRFData failed', [
                'filters' => $filters,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function parseIds($input): array
    {
        if (empty($input)) return [];

        $values = is_array($input) ? $input : [$input];

        return collect($values)
            ->flatMap(fn($v) => explode(',', $v))
            ->map(fn($v) => (int) trim($v))
            ->filter()
            ->values()
            ->all();
    }
    // public function getCRFData(int $perPage = 50, array $filters = [])
    // {
    //     try {
    //         $user   = auth()->user();
    //         $filter = $filters['filter'] ?? [];
    //         // dd($filter);
    //         $query = ChillerRequest::select([
    //             'id',
    //             'uuid',
    //             'osa_code',
    //             'owner_name',
    //             'contact_number',
    //             'warehouse_id',
    //             'salesman_id',
    //             'model',
    //             'customer_id',
    //             'outlet_id',
    //             'status',
    //             'created_user',
    //             'created_at',
    //         ])
    //             ->with([
    //                 'warehouse:id,warehouse_name,warehouse_code',
    //                 'salesman:id,name,osa_code',
    //                 'modelNumber:id,code,name',
    //                 'customer:id,osa_code,name',
    //                 'outlet:id,outlet_channel',
    //                 'createdBy:id,username',
    //             ])
    //             ->where('fridge_status', 0)
    //             ->whereExists(function ($q) {
    //                 $q->select(DB::raw(1))
    //                     ->from('htapp_workflow_requests as wr')
    //                     ->whereColumn('wr.process_id', 'chiller_requests.id')
    //                     ->where('wr.process_type', 'Chiller_Request')
    //                     ->whereExists(function ($step) {
    //                         $step->select(DB::raw(1))
    //                             ->from('htapp_workflow_request_steps as ws')
    //                             ->whereColumn('ws.workflow_request_id', 'wr.id')
    //                             ->where('ws.status', 'APPROVED')
    //                             // ->where('ws.message', 'Chiller Manager Accepted')
    //                             ->whereRaw("
    //                             ws.step_order = (
    //                                 SELECT MAX(s2.step_order)
    //                                 FROM htapp_workflow_request_steps s2
    //                                 WHERE s2.workflow_request_id = wr.id
    //                                 AND s2.status = 'APPROVED'
    //                             )
    //                         ");
    //                     });
    //             });

    //         // dd($query->toRawSql());
    //         if (method_exists(DataAccessHelper::class, 'filterAgentTransaction')) {
    //             $query = DataAccessHelper::filterAgentTransaction($query, $user);
    //         }

    //         if (!empty($filter)) {

    //             $warehouseIds = CommonLocationFilter::resolveWarehouseIds([
    //                 'company'   => $filter['company_id']   ?? null,
    //                 'region'    => $filter['region_id']    ?? null,
    //                 'area'      => $filter['area_id']      ?? null,
    //                 'warehouse' => $filter['warehouse_id'] ?? null,
    //                 'route'     => $filter['route_id']     ?? null,
    //             ]);

    //             if (!empty($warehouseIds)) {
    //                 $query->whereIn('warehouse_id', $warehouseIds);
    //             }
    //         }

    //         if (!empty($filter['salesman_id'])) {
    //             $salesmanIds = is_array($filter['salesman_id'])
    //                 ? $filter['salesman_id']
    //                 : explode(',', $filter['salesman_id']);

    //             $query->whereIn('salesman_id', array_map('intval', $salesmanIds));
    //         }
    //         if (!empty($filter['model_id'])) {

    //             $modelIds = is_array($filter['model_id'])
    //                 ? $filter['model_id']
    //                 : explode(',', $filter['model_id']);

    //             $query->whereIn(
    //                 'model', // DB column
    //                 array_filter(array_map('intval', $modelIds))
    //             );
    //         }

    //         if (!empty($filter['from_date']) && !empty($filter['to_date'])) {

    //             $from = Carbon::parse($filter['from_date'])->startOfDay();
    //             $to   = Carbon::parse($filter['to_date'])->endOfDay();

    //             $query->whereBetween('created_at', [$from, $to]);
    //         } elseif (!empty($filter['from_date'])) {

    //             $query->where('created_at', '>=', Carbon::parse($filter['from_date'])->startOfDay());
    //         } elseif (!empty($filter['to_date'])) {

    //             $query->where('created_at', '<=', Carbon::parse($filter['to_date'])->endOfDay());
    //         }
    //         // dd($query->count());
    //         $results = $query->latest()->paginate($perPage);

    //         $results->getCollection()->transform(function ($item) {
    //             $item->makeHidden([
    //                 'warehouse_id',
    //                 'salesman_id',
    //                 'customer_id',
    //                 'outlet_id',
    //                 'created_user',
    //             ]);

    //             if ($item->salesman) {
    //                 $item->salesman->makeHidden([
    //                     'route',
    //                     'route_name',
    //                     'route_id',
    //                 ]);
    //             }

    //             return $item;
    //         });

    //         return $results;
    //     } catch (Throwable $e) {
    //         Log::error('ChillerRequestService::globalFilter failed', [
    //             'filters' => $filters,
    //             'error'   => $e->getMessage(),
    //         ]);

    //         throw $e;
    //     }
    // }



    public function globalFilter(int $perPage = 50, array $filters = [])
    {
        try {
            $user   = auth()->user();
            $filter = $filters['filter'] ?? [];

            $query = ChillerRequest::query(); // ❌ no eager load, no extra data

            // 🔐 Agent access control
            // if (method_exists(DataAccessHelper::class, 'filterAgentTransaction')) {
            //     $query = DataAccessHelper::filterAgentTransaction($query, $user);
            // }
            $query = DataAccessHelper::filterWarehouses($query, $user);

            // 🌍 Location hierarchy filter
            if (!empty($filter)) {
                $warehouseIds = CommonLocationFilter::resolveWarehouseIds([
                    'company'   => $filter['company_id']   ?? null,
                    'region'    => $filter['region_id']    ?? null,
                    'area'      => $filter['area_id']      ?? null,
                    'warehouse' => $filter['warehouse_id'] ?? null,
                    'route'     => $filter['route_id']     ?? null,
                ]);

                if (!empty($warehouseIds)) {
                    $query->whereIn('warehouse_id', $warehouseIds);
                }
            }

            $statusInput = $filter['status'] ?? $filter['request_status'] ?? null;

            if (!empty($statusInput)) {
                $statuses = is_array($statusInput)
                    ? $statusInput
                    : explode(',', $statusInput);

                $statuses = array_filter(array_map('intval', $statuses));

                if (!empty($statuses)) {
                    $query->whereIn('status', $statuses);
                }
            }
            if (!empty($filter['model_id'])) {
                $modelIds = is_array($filter['model_id'])
                    ? $filter['model_id']
                    : explode(',', $filter['model_id']);

                $query->whereIn('model', array_map('intval', $modelIds));
            }

            // 📅 Date range filter
            if (!empty($filter['from_date']) && !empty($filter['to_date'])) {
                $from = Carbon::parse($filter['from_date'])->startOfDay();
                $to   = Carbon::parse($filter['to_date'])->endOfDay();
                $query->whereBetween('created_at', [$from, $to]);
            } elseif (!empty($filter['from_date'])) {
                $query->where('created_at', '>=', Carbon::parse($filter['from_date'])->startOfDay());
            } elseif (!empty($filter['to_date'])) {
                $query->where('created_at', '<=', Carbon::parse($filter['to_date'])->endOfDay());
            }

            // 📄 Final result
            // dd($query->count());
            $result = $query->latest()->paginate($perPage);
            $result->getCollection()->transform(function ($item) {
                return \App\Helpers\ApprovalHelper::attach($item, 'Chiller_Request');
            });

            return $result;
        } catch (Throwable $e) {
            Log::error('ChillerRequestService::globalFilter failed', [
                'filters' => $filters,
                'error'   => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function generateAndStorePdf($uuid)
    {
        $data = DB::table('chiller_requests')->where('uuid', $uuid)->first();

        if (!$data) {
            return null;
        }

        $imageFields = [
            'national_id_file' => 'National Id Front',
            'national_id1_file' => 'National Id Back',
            'password_photo_file' => 'Password Photo',
            'outlet_address_proof_file' => 'Outlet Address Proof',
            'trading_licence_file' => 'Trading License',
            'lc_letter_file' => 'Lc Letter',
            'outlet_stamp_file' => 'Outlet Stamp',
            'sign__customer_file' => 'Sign Customer',
        ];

        $images = [];

        foreach ($imageFields as $column => $label) {
            if (!empty($data->$column)) {
                $images[] = [
                    'label' => $label,
                    'path' => $data->$column
                ];
            }
        }

        $pdf = Pdf::loadView('chiller-images', [
            'images' => $images,
            'uuid' => $uuid
        ]);

        $fileName = "chiller_requests/{$uuid}.pdf";
        Storage::disk('public')->put($fileName, $pdf->output());

        return $fileName;
    }
}
