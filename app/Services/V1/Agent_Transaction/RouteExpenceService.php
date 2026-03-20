<?php

namespace App\Services\V1\Agent_Transaction;

use App\Models\Agent_Transaction\RouteExpence;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Error;
use Exception;

class RouteExpenceService
{
    /**
     * Get all route expenses with filters and pagination.
     */
    // public function getAll(int $perPage = 50, array $filters = [])
    // {
    //     try {
    //         $query = RouteExpence::with([
    //             'warehouse:id,warehouse_code,warehouse_name',
    //             'route:id,route_code,route_name',
    //             'salesman:id,osa_code,name',
    //             'expenceType:id,osa_code,name'
    //         ])->latest();

    //         foreach ($filters as $field => $value) {
    //             if (!empty($value)) {
    //                 // Text search filters (case-insensitive)
    //                 if (in_array($field, ['osa_code', 'description'])) {
    //                     $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
    //                 }
    //                 // Boolean or exact match filters
    //                 elseif (in_array($field, ['status', 'warehouse_id', 'route_id', 'salesman_id', 'expence_type'])) {
    //                     $query->where($field, $value);
    //                 }
    //             }
    //         }

    //         // Optional relationship-based filters
    //         if (!empty($filters['route_name'])) {
    //             $query->whereHas('route', function ($q) use ($filters) {
    //                 $q->whereRaw('LOWER(route_name) LIKE ?', ['%' . strtolower($filters['route_name']) . '%']);
    //             });
    //         }

    //         if (!empty($filters['warehouse_name'])) {
    //             $query->whereHas('warehouse', function ($q) use ($filters) {
    //                 $q->whereRaw('LOWER(warehouse_name) LIKE ?', ['%' . strtolower($filters['warehouse_name']) . '%']);
    //             });
    //         }

    //         return $query->paginate($perPage);
    //     } catch (Throwable $e) {
    //         Log::error("Failed to fetch route expenses", [
    //             'error' => $e->getMessage(),
    //             'filters' => $filters,
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //         throw new Exception("Something went wrong while fetching route expenses, please try again.");
    //     }
    // }
public function getAll(int $perPage = 50, array $filters = [])
{
    try {
        $query = RouteExpence::with([
            'warehouse:id,warehouse_code,warehouse_name',
            'route:id,route_code,route_name',
            'salesman:id,osa_code,name',
            'expenceType:id,osa_code,name'
        ])->latest();

        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if (in_array($field, ['osa_code', 'description'])) {
                    $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
                } elseif (in_array($field, ['status', 'warehouse_id', 'route_id', 'salesman_id', 'expence_type'])) {
                    $query->where($field, $value);
                }
            }
        }

        if (!empty($filters['route_name'])) {
            $query->whereHas('route', function ($q) use ($filters) {
                $q->whereRaw('LOWER(route_name) LIKE ?', ['%' . strtolower($filters['route_name']) . '%']);
            });
        }

        if (!empty($filters['warehouse_name'])) {
            $query->whereHas('warehouse', function ($q) use ($filters) {
                $q->whereRaw('LOWER(warehouse_name) LIKE ?', ['%' . strtolower($filters['warehouse_name']) . '%']);
            });
        }

        $expenses = $query->paginate($perPage);

        /**
         * =======================================================
         * 🔥 Inject approval workflow status (SAVED PATTERN)
         * =======================================================
         */
        $expenses->getCollection()->transform(function ($expense) {

            $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'Route_Expense')
                ->where('process_id', $expense->id)
                ->orderBy('id', 'DESC')
                ->first();

            if ($workflowRequest) {

                $currentStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                    ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
                    ->orderBy('step_order')
                    ->first();

                $totalSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                    ->count();

                $completedSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                    ->where('status', 'APPROVED')
                    ->count();

                $lastApprovedStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                    ->where('status', 'APPROVED')
                    ->orderBy('step_order', 'DESC')
                    ->first();

                $expense->approval_status = $lastApprovedStep
                    ? $lastApprovedStep->message
                    : 'Initiated';

                $expense->current_step    = $currentStep?->title;
                $expense->request_step_id = $currentStep?->id;
                $expense->progress        = $totalSteps > 0
                    ? ($completedSteps . '/' . $totalSteps)
                    : null;

            } else {
                $expense->approval_status = null;
                $expense->current_step    = null;
                $expense->request_step_id = null;
                $expense->progress        = null;
            }

            return $expense;
        });

        return $expenses;

    } catch (Throwable $e) {
        Log::error("Failed to fetch route expenses", [
            'error' => $e->getMessage(),
            'filters' => $filters,
            'trace' => $e->getTraceAsString()
        ]);

        throw new Exception("Something went wrong while fetching route expenses, please try again.");
    }
}

    /**
     * Generate unique osa_code.
     */
    private function generateOsaCode(): string
    {
        do {
            $last = RouteExpence::withTrashed()->latest('id')->first();
            $nextNumber = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
            $osa_code = 'EXP' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        } while (RouteExpence::withTrashed()->where('osa_code', $osa_code)->exists());

        return $osa_code;
    }

    /**
     * Create new route expense record.
     */
    // public function create(array $data)
    // {
    //     DB::beginTransaction();

    //     try {
    //         $data = array_merge($data, [
    //             'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
    //             'osa_code' => $data['osa_code'] ?? $this->generateOsaCode(),
    //             'created_user' => Auth::id(),
    //             'updated_user' => Auth::id(),
    //             'status' => $data['status'] ?? true,
    //         ]);

    //         $routeExpence = RouteExpence::create($data);

    //         DB::commit();
    //         return $routeExpence;
    //     } catch (Throwable $e) {
    //         DB::rollBack();

    //         Log::error("Route expense creation failed", [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //             'data' => $data,
    //             'user' => Auth::id(),
    //         ]);

    //         $friendlyMessage = $e instanceof Error ? "Server error occurred." : "Something went wrong, please try again.";
    //         throw new Exception($friendlyMessage, 0, $e);
    //     }
    // }
public function create(array $data)
{
    DB::beginTransaction();

    try {
        $data = array_merge($data, [
            'uuid'         => $data['uuid'] ?? Str::uuid()->toString(),
            'osa_code'     => $data['osa_code'] ?? $this->generateOsaCode(),
            'created_user' => Auth::id(),
            'updated_user' => Auth::id(),
            'status'       => $data['status'] ?? true,
        ]);

        $routeExpence = RouteExpence::create($data);

        DB::commit();

        /**
         * =====================================================
         * 🚀 APPLY WORKFLOW AUTOMATICALLY (SAVED PATTERN)
         * =====================================================
         */
        $workflow = DB::table('htapp_workflow_assignments')
            ->where('process_type', 'Route_Expense')
            ->where('is_active', true)
            ->first();

        if ($workflow) {
            app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                ->startApproval([
                    'workflow_id'  => $workflow->workflow_id,
                    'process_type' => 'Route_Expense',
                    'process_id'   => $routeExpence->id,
                ]);
        }

        return $routeExpence;

    } catch (Throwable $e) {
        DB::rollBack();

        Log::error("Route expense creation failed", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'data'  => $data,
            'user'  => Auth::id(),
        ]);

        $friendlyMessage = $e instanceof Error
            ? "Server error occurred."
            : "Something went wrong, please try again.";

        throw new Exception($friendlyMessage, 0, $e);
    }
}

    /**
     * Find a single route expense by UUID.
     */
    public function findByUuid(string $uuid): ?RouteExpence
    {
        if (!Str::isUuid($uuid)) {
            return null;
        }

        return RouteExpence::with([
            'warehouse:id,warehouse_code,warehouse_name',
            'route:id,route_code,route_name',
            'salesman:id,osa_code,name',
            'expenceType:id,osa_code,name'
        ])->where('uuid', $uuid)->first();
    }

    /**
     * Update route expense by UUID.
     */
    public function updateByUuid(string $uuid, array $validated)
    {
        $routeExpence = $this->findByUuid($uuid);
        if (!$routeExpence) {
            throw new Exception("Route expense not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $data = array_merge($validated, [
                'updated_user' => Auth::id(),
            ]);

            $routeExpence->update($data);

            DB::commit();
            return $routeExpence->fresh();
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("Route expense update failed", [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
                'payload' => $validated,
            ]);

            throw new Exception("Something went wrong while updating the route expense.", 0, $e);
        }
    }

    /**
     * Soft delete route expense by UUID.
     */
    public function deleteByUuid(string $uuid)
    {
        $routeExpence = $this->findByUuid($uuid);
        if (!$routeExpence) {
            throw new Exception("Route expense not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $routeExpence->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("Route expense delete failed", [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
            ]);

            throw new Exception("Something went wrong while deleting the route expense.", 0, $e);
        }
    }
}
