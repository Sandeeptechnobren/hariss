<?php

namespace App\Services\V1\Agent_Transaction;

use App\Models\Agent_Transaction\AdvancePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\CompanyCustomer;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdvancePaymentService
{

// public function create(array $data): array
// {
//     try {
//         return DB::transaction(function () use ($data) {
//             $imageUrl = null;
            
//             if (!empty($data['recipt_image']) && $data['recipt_image']->isValid()) {
//                 $filename = Str::random(40) . '.' . $data['recipt_image']->getClientOriginalExtension();
//                 $path = $data['recipt_image']->storeAs('advance_payments', $filename, 'public');
//                 $baseUrl = config('app.url'); // e.g., https://api.coreexl.com/osa_developmentV2
//                 $imageUrl = $baseUrl . '/storage/' . $path;
//                 $data['recipt_image'] = '/storage/' . $path;
//             }
            
//             $record = AdvancePayment::create($data);

//             return [
//                 'success'     => true,
//                 'id'          => $record->id,
//                 'image_url'   => $imageUrl,
//                 'record'      => $record
//             ];
//         });
//     } catch (\Throwable $e) {
//         \Log::error('Advance payment creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
//         return [
//             'success'   => false,
//             'message'   => 'Failed to create advance payment. ' . $e->getMessage(),
//             'image_url' => null,
//         ];
//     }
// }
// public function create(array $data): AdvancePayment
// {
//     try {
//         return DB::transaction(function () use ($data) {
//             if (!empty($data['recipt_image']) && $data['recipt_image']->isValid()) {
//                 $filename = Str::random(40) . '.' . $data['recipt_image']->getClientOriginalExtension();
//                 $relativePath = 'advance_payments/' . $filename;
//                 $data['recipt_image']->storeAs('advance_payments', $filename, 'public');
//                 $appUrl = rtrim(config('app.url'), '/');
//                 $data['recipt_image'] = $appUrl . '/storage/app/public/' . $relativePath;
//             }

//             return AdvancePayment::create($data);
//         });
//     } catch (\Throwable $e) {
//         Log::error('Advance Payment Create Error: ' . $e->getMessage(), [
//             'trace' => $e->getTraceAsString(),
//         ]);

//         throw $e;
//     }
// }
public function create(array $data): AdvancePayment
{
    try {
        return DB::transaction(function () use ($data) {
            
            if (!empty($data['recipt_image']) && $data['recipt_image']->isValid()) {
                $filename = Str::random(40) . '.' . $data['recipt_image']->getClientOriginalExtension();
                $relativePath = 'advance_payments/' . $filename;

                $data['recipt_image']->storeAs('advance_payments', $filename, 'public');

                $appUrl = rtrim(config('app.url'), '/');
                $data['recipt_image'] = $appUrl . '/storage/app/public/' . $relativePath;
            }

            $advancePayment = AdvancePayment::create($data);

            /**
             * =======================================================
             * 🚀 APPLY APPROVAL WORKFLOW (SAVED GLOBAL PATTERN)
             * =======================================================
             */
            $workflow = DB::table('htapp_workflow_assignments')
                ->where('process_type', 'Distributor_Advance_Payment')
                ->where('is_active', true)
                ->first();

            if ($workflow) {
                app(\App\Services\V1\Approval_process\HtappWorkflowApprovalService::class)
                    ->startApproval([
                        'workflow_id'  => $workflow->workflow_id,
                        'process_type' => 'Distributor_Advance_Payment',
                        'process_id'   => $advancePayment->id,
                    ]);
            }

            return $advancePayment;
        });
    } catch (\Throwable $e) {
        // Log::error('Advance Payment Create Error: ' . $e->getMessage(), [
        //     'trace' => $e->getTraceAsString(),
        // ]);

        throw $e;
    }
}

// public function list(array $filters = [], int $perPage = 50)
//     {
//         $query = AdvancePayment::query()->with(['companyBank', 'agent']);
//         if (!empty($filters['payment_type'])) {
//             $query->where('payment_type', $filters['payment_type']);
//         }
//         if (!empty($filters['osa_code'])) {
//             $query->where('osa_code', 'like', '%' . $filters['osa_code'] . '%');
//         }
//         if (!empty($filters['companybank_id'])) {
//             $query->where('companybank_id', $filters['companybank_id']);
//         }
//         if (!empty($filters['agent_id'])) {
//             $query->where('agent_id', $filters['agent_id']);
//         }
//         $query->orderBy('id', 'desc');
//         return $query->paginate($perPage);
//     }
public function list(array $filters = [], int $perPage = 50)
{
    $query = AdvancePayment::query()->with(['companyBank', 'agent']);

    if (!empty($filters['payment_type'])) {
        $query->where('payment_type', $filters['payment_type']);
    }

    if (!empty($filters['osa_code'])) {
        $query->where('osa_code', 'like', '%' . $filters['osa_code'] . '%');
    }

    if (!empty($filters['companybank_id'])) {
        $query->where('companybank_id', $filters['companybank_id']);
    }

    if (!empty($filters['agent_id'])) {
        $query->where('agent_id', $filters['agent_id']);
    }
    //   $fromDate = !empty($filters['from_date'])
    // ? Carbon::parse($filters['from_date'])->toDateString()
    // : null;

    // $toDate = !empty($filters['to_date'])
    //     ? Carbon::parse($filters['to_date'])->toDateString()
    //     : null;

    // if ($fromDate || $toDate) {

    //     if ($fromDate && $toDate) {
    //         $query->whereDate('created_at', '>=', $fromDate)
    //             ->whereDate('created_at', '<=', $toDate);
    //     }
    //     elseif ($fromDate) {
    //         $query->whereDate('created_at', '>=', $fromDate);
    //     }
    //     elseif ($toDate) {
    //         $query->whereDate('created_at', '<=', $toDate);
    //     }

    // } else {
    //     // DEFAULT: today only
    //     $query->whereDate('created_at', Carbon::today());
    // }

    $query->orderBy('id', 'desc');

    $payments = $query->paginate($perPage);

    /**
     * =======================================================
     * 🔥 Inject Approval Workflow Status (Saved Pattern)
     * =======================================================
     */
    $payments->getCollection()->transform(function ($payment) {

        $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'Distributor_Advance_Payment')
            ->where('process_id', $payment->id)
            ->orderBy('id', 'DESC')
            ->first();
        
        if ($workflowRequest) {

            $currentStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
                ->orderBy('step_order')
                ->first();

            $totalSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)->count();

            $completedSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->count();

            $lastApprovedStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
                ->where('status', 'APPROVED')
                ->orderBy('step_order', 'desc')
                ->first();

            $payment->approval_status = $lastApprovedStep
                ? $lastApprovedStep->message
                : 'Initiated';

            $payment->current_step = $currentStep?->title;
            $payment->request_step_id = $currentStep?->id;
            $payment->progress = $totalSteps > 0
                ? ($completedSteps . '/' . $totalSteps)
                : null;

        } else {
            $payment->approval_status = null;
            $payment->current_step = null;
            $payment->request_step_id = null;
            $payment->progress = null;
        }

        return $payment;
    });

    return $payments;
}

public function getByUuid(string $uuid)
    {
        return AdvancePayment::with(['companyBank', 'agent'])
            ->where('uuid', $uuid)
            ->first();
    }
public function updateByUuid(string $uuid, array $data)
    {
        $payment = AdvancePayment::where('uuid', $uuid)->first();
        if (!$payment) {
            return null;
        }
        if (!empty($data['recipt_image']) && $data['recipt_image'] instanceof \Illuminate\Http\UploadedFile) {
            if ($data['recipt_image']->isValid()) {
                $path = $data['recipt_image']->store('receipts', 'public');
                $data['recipt_image'] = $path;
            }
        } else {
            unset($data['recipt_image']);
        }
        $payment->update($data);
        return $payment->fresh(['companyBank', 'agent']);
    }
public function getBankDetailsById($id)
    {
        return CompanyCustomer::select('bank_name', 'bank_account_number')->where('id', $id)->first();
    }


}
