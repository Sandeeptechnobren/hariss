<?php

namespace App\Services\V1\Approval_process;

use App\Models\HtappWorkflowRequest;
use App\Models\HtappWorkflowRequestStep;
use App\Models\HtappWorkflowRequestStepApprover;
use App\Models\HtappWorkflow;
use App\Models\User;
use App\Models\Agent_Transaction\OrderHeader;
use App\Models\Agent_Transaction\AgentDeliveryHeaders;
use App\Models\Agent_Transaction\InvoiceHeader;

class HtappWorkflowStatusService
{
    public function getStatus($data)
    {
        $request = HtappWorkflowRequest::where('process_type', $data['process_type'])
            ->where('process_id', $data['process_id'])
            ->orderBy('id', 'DESC')
            ->first();

        if (! $request) {
            return ['message' => 'No workflow found for this process'];
        }

        $workflow = HtappWorkflow::find($request->workflow_id);

        $steps = HtappWorkflowRequestStep::where('workflow_request_id', $request->id)
            ->orderBy('step_order', 'ASC')
            ->get();

        $responseSteps = [];

        foreach ($steps as $step) {

            $approverRows = HtappWorkflowRequestStepApprover::where('request_step_id', $step->id)->get();

            $approvers = [];

            foreach ($approverRows as $ar) {
                $user = $ar->user_id ? User::find($ar->user_id) : null;

                $approvers[] = [
                    'user_id'      => $ar->user_id,
                    'name'         => $user ? $user->name : null,
                    'has_approved' => (bool)$ar->has_approved
                ];
            }

            $responseSteps[] = [
                'id'            => $step->id,
                'step_order'    => $step->step_order,
                'title'         => $step->title,
                'status'        => $step->status,
                'approval_type' => $step->approval_type,
                'message'       => $step->message,
                'notification'  => $step->notification,
                'assigned_to'   => $step->assigned_to,
                'approvers'     => $approvers
            ];
        }

        return [
            'workflow_request' => [
                'id'       => $request->id,
                'workflow' => $workflow ? $workflow->name : null,
                'status'   => $request->status
            ],
            'steps' => $responseSteps
        ];
    }


    // public function getOrderApprovalStatusByOsaCode(string $osaCode): array
    // {
    //     // 🔹 Fetch Agent Order Header by osa_code
    //     $order = AgentOrderHeader::where('order_code', $osaCode)
    //         ->select('id', 'order_code', 'status')
    //         ->first();

    //     if (! $order) {
    //         return ['message' => 'No order found for given OSA code'];
    //     }

    //     // 🔹 Fetch latest workflow request for this order
    //     $workflowRequest = HtappWorkflowRequest::where('process_type', 'order')
    //         ->where('process_id', $order->id)
    //         ->orderByDesc('id')
    //         ->first();

    //     if (! $workflowRequest) {
    //         return [
    //             'order' => [
    //                 'id'       => $order->id,
    //                 'order_code' => $order->order_code,
    //                 'status'   => $order->status
    //             ],
    //             'message' => 'No workflow found for this order'
    //         ];
    //     }

    //     $workflow = HtappWorkflow::find($workflowRequest->workflow_id);

    //     // 🔹 Fetch workflow steps
    //     $steps = HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
    //         ->orderBy('step_order', 'ASC')
    //         ->get();

    //     $responseSteps = [];

    //     foreach ($steps as $step) {

    //         $approverRows = HtappWorkflowRequestStepApprover::where('request_step_id', $step->id)->get();

    //         $approvers = [];

    //         foreach ($approverRows as $ar) {
    //             $user = $ar->user_id ? User::find($ar->user_id) : null;

    //             $approvers[] = [
    //                 'user_id'      => $ar->user_id,
    //                 'name'         => $user?->name,
    //                 'has_approved' => (bool) $ar->has_approved,
    //             ];
    //         }

    //         $responseSteps[] = [
    //             'id'            => $step->id,
    //             'step_order'    => $step->step_order,
    //             'title'         => $step->title,
    //             'status'        => $step->status,
    //             'approval_type' => $step->approval_type,
    //             'message'       => $step->message,
    //             'notification'  => $step->notification,
    //             'assigned_to'   => $step->assigned_to,
    //             'approvers'     => $approvers,
    //         ];
    //     }

    //     return [
    //         'order' => [
    //             'id'       => $order->id,
    //             'order_code' => $order->order_code,
    //             'status'   => $order->status
    //         ],
    //         'workflow_request' => [
    //             'id'       => $workflowRequest->id,
    //             'workflow' => $workflow?->name,
    //             'status'   => $workflowRequest->status
    //         ],
    //         'steps' => $responseSteps
    //     ];
    // }

// public function getOrderDeliveriesInvoicesApprovalByOrderCode(string $orderCode): array
//     {
//         $order = OrderHeader::where('order_code', $orderCode)
//             ->select('id', 'order_code', 'status')
//             ->first();

//         if (! $order) {
//             return ['message' => 'No order found for given order code'];
//         }
//         $orderWorkflowRequest = HtappWorkflowRequest::where('process_type', 'order')
//             ->where('process_id', $order->id)
//             ->latest('id')
//             ->first();

//         $orderStepsResponse = [];
//         if ($orderWorkflowRequest) {

//             $orderSteps = HtappWorkflowRequestStep::where(
//                 'workflow_request_id',
//                 $orderWorkflowRequest->id
//             )
//                 ->orderBy('step_order')
//                 ->get();

//             foreach ($orderSteps as $step) {

//                 $approvers = [];
//                 $approverRows = HtappWorkflowRequestStepApprover::where(
//                     'request_step_id',
//                     $step->id
//                 )->get();

//                 foreach ($approverRows as $ar) {
//                     $user = $ar->user_id ? User::find($ar->user_id) : null;

//                     $approvers[] = [
//                         'user_id'      => $ar->user_id,
//                         'name'         => $user?->name,
//                         'has_approved' => (bool) $ar->has_approved
//                     ];
//                 }

//                 $orderStepsResponse[] = [
//                     'id'            => $step->id,
//                     'step_order'    => $step->step_order,
//                     'title'         => $step->title,
//                     'status'        => $step->status,
//                     'approval_type' => $step->approval_type,
//                     'message'       => $step->message,
//                     'notification'  => $step->notification,
//                     'assigned_to'   => $step->assigned_to,
//                     'approvers'     => $approvers
//                 ];
//             }
//         }
//         $deliveriesResponse = [];
//         $deliveries = AgentDeliveryHeaders::where('order_code', $order->order_code)
//             ->select('id', 'delivery_code', 'status')
//             ->get();
//         foreach ($deliveries as $delivery) {
//             $deliveryWorkflowRequest = HtappWorkflowRequest::where('process_type', 'Agent_Delivery_Headers')
//                 ->where('process_id', $delivery->id)
//                 ->latest('id')
//                 ->first();
//             $deliveryStepsResponse = [];
//             if ($deliveryWorkflowRequest) {
//                 $steps = HtappWorkflowRequestStep::where(
//                     'workflow_request_id',
//                     $deliveryWorkflowRequest->id
//                 )
//                     ->orderBy('step_order')
//                     ->get();
//                 foreach ($steps as $step) {
//                     $approvers = [];
//                     $approverRows = HtappWorkflowRequestStepApprover::where(
//                         'request_step_id',
//                         $step->id
//                     )->get();
//                     foreach ($approverRows as $ar) {
//                         $user = $ar->user_id ? User::find($ar->user_id) : null;
//                         $approvers[] = [
//                             'user_id'      => $ar->user_id,
//                             'name'         => $user?->name,
//                             'has_approved' => (bool) $ar->has_approved
//                         ];
//                     }
//                     $deliveryStepsResponse[] = [
//                         'id'            => $step->id,
//                         'step_order'    => $step->step_order,
//                         'title'         => $step->title,
//                         'status'        => $step->status,
//                         'approval_type' => $step->approval_type,
//                         'message'       => $step->message,
//                         'notification'  => $step->notification,
//                         'assigned_to'   => $step->assigned_to,
//                         'approvers'     => $approvers
//                     ];
//                 }
//             }

//             $deliveriesResponse[] = [
//                 'delivery' => [
//                     'id'            => $delivery->id,
//                     'delivery_code' => $delivery->delivery_code,
//                     'status'        => $delivery->status
//                 ],
//                 'workflow_request' => $deliveryWorkflowRequest ? [
//                     'id'     => $deliveryWorkflowRequest->id,
//                     'status' => $deliveryWorkflowRequest->status
//                 ] : null,
//                 'steps' => $deliveryStepsResponse
//             ];
//         }
//         $invoicesResponse = [];
//         $invoices = InvoiceHeader::where('delivery_id', $delivery->id)
//             ->select('id', 'invoice_code', 'status', 'total_amount')
//             ->get();

//         foreach ($invoices as $invoice) {
//             $invoiceWorkflowRequest = HtappWorkflowRequest::where('process_type', 'Invoice_Header')
//                 ->where('process_id', $invoice->id)
//                 ->latest('id')
//                 ->first();
//             $invoiceStepsResponse = [];

//             if ($invoiceWorkflowRequest) {

//                 $steps = HtappWorkflowRequestStep::where(
//                     'workflow_request_id',
//                     $invoiceWorkflowRequest->id
//                 )
//                     ->orderBy('step_order')
//                     ->get();

//                 foreach ($steps as $step) {

//                     $approvers = [];
//                     $approverRows = HtappWorkflowRequestStepApprover::where(
//                         'request_step_id',
//                         $step->id
//                     )->get();

//                     foreach ($approverRows as $ar) {
//                         $user = $ar->user_id ? User::find($ar->user_id) : null;

//                         $approvers[] = [
//                             'user_id'      => $ar->user_id,
//                             'name'         => $user?->name,
//                             'has_approved' => (bool) $ar->has_approved
//                         ];
//                     }

//                     $invoiceStepsResponse[] = [
//                         'id'            => $step->id,
//                         'step_order'    => $step->step_order,
//                         'title'         => $step->title,
//                         'status'        => $step->status,
//                         'approval_type' => $step->approval_type,
//                         'message'       => $step->message,
//                         'notification'  => $step->notification,
//                         'assigned_to'   => $step->assigned_to,
//                         'approvers'     => $approvers
//                     ];
//                 }
//             }

//             $invoicesResponse[] = [
//                 'invoice' => [
//                     'id'           => $invoice->id,
//                     'invoice_code' => $invoice->invoice_code,
//                     'status'       => $invoice->status,
//                     'amount'       => $invoice->total_amount
//                 ],
//                 'workflow_request' => $invoiceWorkflowRequest ? [
//                     'id'     => $invoiceWorkflowRequest->id,
//                     'status' => $invoiceWorkflowRequest->status
//                 ] : null,
//                 'steps' => $invoiceStepsResponse
//             ];
//         }

//         /*
//      |--------------------------------------------------------------------------
//      | FINAL RESPONSE
//      |--------------------------------------------------------------------------
//      */
//         return [
//             'order' => [
//                 'id'         => $order->id,
//                 'order_code' => $order->order_code,
//                 'status'     => $order->status,
//                 'workflow'   => $orderWorkflowRequest ? [
//                     'id'     => $orderWorkflowRequest->id,
//                     'status' => $orderWorkflowRequest->status
//                 ] : null,
//                 'steps' => $orderStepsResponse
//             ],
//             'deliveries' => $deliveriesResponse,
//             'invoices'   => $invoicesResponse
//         ];
//     }
public function getOrderDeliveriesInvoicesApprovalByOrderCode(string $orderCode): array
{
    $order = OrderHeader::where('order_code', $orderCode)
        ->select('id', 'order_code', 'status','created_at')
        ->first();

    if (! $order) {
        return ['message' => 'No order found for given order code'];
    }

    /*
    |--------------------------------------------------------------------------
    | ORDER WORKFLOW
    |--------------------------------------------------------------------------
    */
    $orderWorkflowRequest = HtappWorkflowRequest::where('process_type', 'order')
        ->where('process_id', $order->id)
        ->latest('id')
        ->first();

    $orderStepsResponse = [];

    if ($orderWorkflowRequest) {
        $orderSteps = HtappWorkflowRequestStep::where('workflow_request_id', $orderWorkflowRequest->id)
            ->orderBy('step_order')
            ->get();

        foreach ($orderSteps as $step) {
            $approvers = [];

            $approverRows = HtappWorkflowRequestStepApprover::where('request_step_id', $step->id)->get();

            foreach ($approverRows as $ar) {
                $user = $ar->user_id ? User::find($ar->user_id) : null;

                $approvers[] = [
                    'user_id'      => $ar->user_id,
                    'name'         => $user?->name,
                    'has_approved' => (bool) $ar->has_approved
                ];
            }

            $orderStepsResponse[] = [
                'id'            => $step->id,
                'step_order'    => $step->step_order,
                'title'         => $step->title,
                'status'        => $step->status,
                'approval_type' => $step->approval_type,
                'message'       => $step->message,
                'notification'  => $step->notification,
                'assigned_to'   => $step->assigned_to,
                'approvers'     => $approvers
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELIVERIES + INVOICES
    |--------------------------------------------------------------------------
    */
    $deliveriesResponse = [];
    $allInvoicesResponse = [];

    $deliveries = AgentDeliveryHeaders::where('order_code', $order->order_code)
        ->select('id', 'delivery_code', 'status','created_at')
        ->get();

    foreach ($deliveries as $delivery) {

        // DELIVERY WORKFLOW
        $deliveryWorkflowRequest = HtappWorkflowRequest::where('process_type', 'Agent_Delivery_Headers')
            ->where('process_id', $delivery->id)
            ->latest('id')
            ->first();

        $deliveryStepsResponse = [];

        if ($deliveryWorkflowRequest) {
            $steps = HtappWorkflowRequestStep::where('workflow_request_id', $deliveryWorkflowRequest->id)
                ->orderBy('step_order')
                ->get();

            foreach ($steps as $step) {
                $approvers = [];

                $approverRows = HtappWorkflowRequestStepApprover::where('request_step_id', $step->id)->get();

                foreach ($approverRows as $ar) {
                    $user = $ar->user_id ? User::find($ar->user_id) : null;

                    $approvers[] = [
                        'user_id'      => $ar->user_id,
                        'name'         => $user?->name,
                        'has_approved' => (bool) $ar->has_approved
                    ];
                }

                $deliveryStepsResponse[] = [
                    'id'            => $step->id,
                    'step_order'    => $step->step_order,
                    'title'         => $step->title,
                    'status'        => $step->status,
                    'approval_type' => $step->approval_type,
                    'message'       => $step->message,
                    'notification'  => $step->notification,
                    'assigned_to'   => $step->assigned_to,
                    'approvers'     => $approvers
                ];
            }
        }

        // INVOICES FOR THIS DELIVERY
        $invoices = InvoiceHeader::where('delivery_id', $delivery->id)
            ->select('id', 'invoice_code', 'status', 'total_amount','created_at')
            ->get();

        foreach ($invoices as $invoice) {

            $invoiceWorkflowRequest = HtappWorkflowRequest::where('process_type', 'Invoice_Header')
                ->where('process_id', $invoice->id)
                ->latest('id')
                ->first();

            $invoiceStepsResponse = [];

            if ($invoiceWorkflowRequest) {
                $steps = HtappWorkflowRequestStep::where('workflow_request_id', $invoiceWorkflowRequest->id)
                    ->orderBy('step_order')
                    ->get();

                foreach ($steps as $step) {
                    $approvers = [];

                    $approverRows = HtappWorkflowRequestStepApprover::where('request_step_id', $step->id)->get();

                    foreach ($approverRows as $ar) {
                        $user = $ar->user_id ? User::find($ar->user_id) : null;

                        $approvers[] = [
                            'user_id'      => $ar->user_id,
                            'name'         => $user?->name,
                            'has_approved' => (bool) $ar->has_approved
                        ];
                    }

                    $invoiceStepsResponse[] = [
                        'id'            => $step->id,
                        'step_order'    => $step->step_order,
                        'title'         => $step->title,
                        'status'        => $step->status,
                        'approval_type' => $step->approval_type,
                        'message'       => $step->message,
                        'notification'  => $step->notification,
                        'assigned_to'   => $step->assigned_to,
                        'approvers'     => $approvers
                    ];
                }
            }

            $allInvoicesResponse[] = [
                'invoice' => [
                    'id'           => $invoice->id,
                    'invoice_code' => $invoice->invoice_code,
                    'status'       => $invoice->status,
                    'amount'       => $invoice->total_amount,
                    'created_at'       => $invoice->created_at
                ],
                'workflow_request' => $invoiceWorkflowRequest ? [
                    'id'     => $invoiceWorkflowRequest->id,
                    'status' => $invoiceWorkflowRequest->status
                ] : null,
                'steps' => $invoiceStepsResponse
            ];
        }

        $deliveriesResponse[] = [
            'delivery' => [
                'id'            => $delivery->id,
                'delivery_code' => $delivery->delivery_code,
                'status'        => $delivery->status,
                'created_at'        => $delivery->created_at
            ],
            'workflow_request' => $deliveryWorkflowRequest ? [
                'id'     => $deliveryWorkflowRequest->id,
                'status' => $deliveryWorkflowRequest->status
            ] : null,
            'steps' => $deliveryStepsResponse
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FINAL RESPONSE
    |--------------------------------------------------------------------------
    */
    return [
        'order' => [
            'id'         => $order->id,
            'order_code' => $order->order_code,
            'status'     => $order->status,
            'created_at'     => $order->created_at,
            'workflow'   => $orderWorkflowRequest ? [
                'id'     => $orderWorkflowRequest->id,
                'status' => $orderWorkflowRequest->status
            ] : null,
            'steps' => $orderStepsResponse
        ],
        'deliveries' => $deliveriesResponse,
        'invoices'   => $allInvoicesResponse
    ];
}

}
