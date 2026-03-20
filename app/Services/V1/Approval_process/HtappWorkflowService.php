<?php

namespace App\Services\V1\Approval_process;

use App\Models\HtappWorkflow;
use App\Models\HtappWorkflowStep;
use App\Models\HtappWorkflowStepApprover;
use App\Models\HtappWorkflowRequestStepApprover;
use App\Models\HtappWorkflowRequestStep;
use App\Models\User;
use App\Models\HtappWorkflowRequest;
use Illuminate\Support\Facades\DB;

class HtappWorkflowService
{
    public function saveWorkflow($data)
    {
        return DB::transaction(function () use ($data) {
            $workflow = HtappWorkflow::create([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active'   => $data['is_active'] ?? true
            ]);
            foreach ($data['steps'] as $stepData) {
                $step = HtappWorkflowStep::create([
                    'workflow_id'   => $workflow->id,
                    'step_order'    => $stepData['step_order'],
                    'title'         => $stepData['title'],
                    'approval_type' => $stepData['approval_type'],
                    'message'       => $stepData['message'] ?? null,
                    'notification'  => $stepData['notification'] ?? null,
                    'confirmationMessage'  => $stepData['confirmationMessage'] ?? null,
                    'permissions'   => json_encode($stepData['permissions'] ?? [])
                ]);
                if (!empty($stepData['user_ids'])) {
                    foreach ($stepData['user_ids'] as $uid) {
                        $new = HtappWorkflowStepApprover::create([
                            'workflow_step_id' => $step->id,
                            'user_id'          => $uid,
                            'role_id'          => null
                        ]);
                    }
                }
                if (!empty($stepData['role_ids'])) {
                    foreach ($stepData['role_ids'] as $rid) {
                        HtappWorkflowStepApprover::create([
                            'workflow_step_id' => $step->id,
                            'user_id'          => null,
                            'role_id'          => $rid
                        ]);
                    }
                }
            }
            return $workflow;
        });
    }

    public function getList()
    {
        $workflows = HtappWorkflow::orderBy('id', 'DESC')->get();
        $result = [];
        foreach ($workflows as $wf) {
            $steps = HtappWorkflowStep::where('workflow_id', $wf->id)
                ->orderBy('step_order')
                ->get();
            $stepData = [];
            foreach ($steps as $step) {
                $approvers = HtappWorkflowStepApprover::where('workflow_step_id', $step->id)->get();
                $apprData = [];
                foreach ($approvers as $appr) {
                    if ($appr->user_id) {
                        $user = \App\Models\User::find($appr->user_id);
                        $apprData[] = [
                            'type'      => 'USER',
                            'user_id'   => $appr->user_id,
                            'name'      => $user ? $user->name : null,
                            'uuid'      => $appr->uuid,
                            'user_uuid' => $user ? $user->uuid ?? null : null
                        ];
                    }
                    if ($appr->role_id) {
                        $role = \Spatie\Permission\Models\Role::find($appr->role_id);
                        $apprData[] = [
                            'type'      => 'ROLE',
                            'role_id'   => $appr->role_id,
                            'name'      => $role ? $role->name : null,
                            'uuid'      => $appr->uuid
                        ];
                    }
                }
                $stepData[] = [
                    'uuid'          => $step->uuid,
                    'step_id'       => $step->id,
                    'step_order'    => $step->step_order,
                    'title'         => $step->title,
                    'approval_type' => $step->approval_type,
                    'message'       => $step->message,
                    'notification'  => $step->notification,
                    'confirmationMessage'  => $step->confirmationMessage,
                    'permissions'   => json_decode($step->permissions, true),
                    'approvers'     => $apprData
                ];
            }
            $result[] = [
                'uuid'          => $wf->uuid,
                'workflow_id'   => $wf->id,
                'name'          => $wf->name,
                'description'   => $wf->description,
                'is_active'     => $wf->is_active,
                'steps'         => $stepData
            ];
        }
        return $result;
    }
// public function updateWorkflow(array $data)
//     {
//         return DB::transaction(function () use ($data) {
//             $workflow = HtappWorkflow::findOrFail($data['workflow_id']);
//             $workflow->update([
//                 'name'        => $data['name'],
//                 'description' => $data['description'] ?? null,
//                 'is_active'   => $data['is_active'],
//             ]);
//             foreach ($data['steps'] as $stepData) {
//                 if (!empty($stepData['step_id'])) {
//                     $step = HtappWorkflowStep::where('id', $stepData['step_id'])
//                         ->where('workflow_id', $workflow->id)
//                         ->first();
//                     if (!$step) {
//                         $step = new HtappWorkflowStep();
//                         $step->workflow_id = $workflow->id;
//                     }
//                 } else {
//                     $step = new HtappWorkflowStep();
//                     $step->workflow_id = $workflow->id;
//                 }
//                 $step->step_order    = $stepData['step_order'];
//                 $step->title         = $stepData['title'];
//                 $step->approval_type = $stepData['approval_type'];
//                 $step->message       = $stepData['message'] ?? null;
//                 $step->notification  = $stepData['notification'] ?? null;
//                 $step->confirmationMessage  = $stepData['confirmationMessage'] ?? null;
//                 $step->permissions   = json_encode($stepData['permissions'] ?? []);
//                 $step->save();
//                 HtappWorkflowStepApprover::where('workflow_step_id', $step->id)->delete();
//                 if (!empty($stepData['user_ids'])) {
//                     foreach ($stepData['user_ids'] as $userId) {
//                         HtappWorkflowStepApprover::create([
//                             'workflow_step_id' => $step->id,
//                             'user_id'          => $userId,
//                             'role_id'          => null,
//                         ]);
//                     }
//                 }
//                 if (!empty($stepData['role_ids'])) {
//                     foreach ($stepData['role_ids'] as $roleId) {
//                         HtappWorkflowStepApprover::create([
//                             'workflow_step_id' => $step->id,
//                             'user_id'          => null,
//                             'role_id'          => $roleId,
//                         ]);
//                     }
//                 }
//             }
//             return [
//                 'message'     => 'Workflow updated successfully',
//                 'workflow_id' => $workflow->id
//             ];
//         });
//     }
public function updateWorkflow(array $data)
{
    return DB::transaction(function () use ($data) {
        if (empty($data['steps'])) {
            throw new \Exception("Workflow must contain at least one step.");
        }
        $workflow = HtappWorkflow::findOrFail($data['workflow_id']);
        $workflow->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active'   => $data['is_active'],
        ]);
        $incomingStepIds = collect($data['steps'])
            ->pluck('step_id')
            ->filter()
            ->toArray();
        $stepsToDelete = HtappWorkflowStep::where('workflow_id', $workflow->id)
            ->whereNotIn('id', $incomingStepIds)
            ->get();
        foreach ($stepsToDelete as $step) {
            HtappWorkflowStepApprover::where('workflow_step_id', $step->id)->delete();
            $step->delete();
        }
        $order = 1;
        foreach ($data['steps'] as $stepData) {
            $step = null;
            if (!empty($stepData['step_id'])) {
                $step = HtappWorkflowStep::where('id', $stepData['step_id'])
                    ->where('workflow_id', $workflow->id)
                    ->first();
            }
            if (!$step) {
                $step = new HtappWorkflowStep();
                $step->workflow_id = $workflow->id;
            }
            $step->step_order    = $order++;
            $step->title         = $stepData['title'];
            $step->approval_type = $stepData['approval_type'];
            $step->message       = $stepData['message'] ?? null;
            $step->notification  = $stepData['notification'] ?? null;
            $step->confirmationMessage = $stepData['confirmationMessage'] ?? null;
            $step->permissions   = json_encode($stepData['permissions'] ?? []);
            $step->save();
            HtappWorkflowStepApprover::where('workflow_step_id', $step->id)->delete();
            $approvers = [];
            if (!empty($stepData['user_ids'])) {
                foreach ($stepData['user_ids'] as $userId) {
                    $approvers[] = [
                        'workflow_step_id' => $step->id,
                        'user_id'          => $userId,
                        'role_id'          => null,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ];
                }
            }
            if (!empty($stepData['role_ids'])) {
                foreach ($stepData['role_ids'] as $roleId) {
                    $approvers[] = [
                        'workflow_step_id' => $step->id,
                        'user_id'          => null,
                        'role_id'          => $roleId,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ];
                }
            }
            if (!empty($approvers)) {
                HtappWorkflowStepApprover::insert($approvers);
            }
        }
        // Log::info('Workflow updated', [
        //     'workflow_id' => $workflow->id,
        //     'updated_by'  => auth()->id()
        // ]);
        return [
            'message'     => 'Workflow updated successfully',
            'workflow_id' => $workflow->id
        ];
    });
}


    public function toggleWorkflow($data)
    {
        $workflow = HtappWorkflow::find($data['workflow_id']);
        $workflow->is_active = $data['is_active'];
        $workflow->save();
        return [
            'workflow_id' => $workflow->id,
            'is_active'   => $workflow->is_active
        ];
    }
    public function getWorkflowByUuid($uuid)
    {
        $workflow = HtappWorkflow::where('uuid', $uuid)->first();

        if (!$workflow) {
            return ['message' => 'Workflow not found'];
        }

        $steps = HtappWorkflowStep::where('workflow_id', $workflow->id)
            ->orderBy('step_order')
            ->get();

        $stepDetails = [];
        foreach ($steps as $step) {
            $approvers = HtappWorkflowStepApprover::where('workflow_step_id', $step->id)->get();

            $formattedApprovers = [];

            foreach ($approvers as $appr) {

                if ($appr->user_id) {
                    $user = \App\Models\User::find($appr->user_id);

                    $formattedApprovers[] = [
                        'type'     => 'USER',
                        'user_id'  => $appr->user_id,
                        'name'     => $user ? $user->name : null,
                        'uuid'     => $appr->uuid,
                    ];
                }

                if ($appr->role_id) {
                    $role = \Spatie\Permission\Models\Role::find($appr->role_id);

                    $formattedApprovers[] = [
                        'type'     => 'ROLE',
                        'role_id'  => $appr->role_id,
                        'name'     => $role ? $role->name : null,
                        'uuid'     => $appr->uuid,
                    ];
                }
            }

            $stepDetails[] = [
                'uuid'          => $step->uuid,
                'step_id'       => $step->id,
                'step_order'    => $step->step_order,
                'title'         => $step->title,
                'approval_type' => $step->approval_type,
                'message'       => $step->message,
                'notification'  => $step->notification,
                'confirmationMessage'  => $step->confirmationMessage,
                'permissions'   => json_decode($step->permissions, true),
                'approvers'     => $formattedApprovers
            ];
        }

        return [
            'workflow_uuid' => $workflow->uuid,
            'workflow_id'   => $workflow->id,
            'name'          => $workflow->name,
            'description'   => $workflow->description,
            'is_active'     => $workflow->is_active,
            'steps'         => $stepDetails
        ];
    }

    public function getAssignedList()
    {
        $workflows = HtappWorkflow::orderBy('id', 'DESC')->get();
        $result = [];
        foreach ($workflows as $wf) {
            $assignments = HtappWorkflowRequest::where('workflow_id', $wf->id)
                ->orderBy('id', 'DESC')
                ->get();
            $assignmentData = [];
            foreach ($assignments as $a) {
                $modelKey = $a->process_key;
                $displayName = \DB::table('htapp_workflow_models')
                    ->where('model_key', $modelKey)
                    ->value('display_name');
                $assignmentData[] = [
                    'workflow_request_id'   => $a->id,
                    'workflow_request_uuid' => $a->uuid,
                    'process_type'           => $modelKey,
                    'model_name'            => $displayName,
                    'process_id'            => $a->process_id,
                    'status'                => $a->status,
                    'created_at'            => $a->created_at
                ];
            }
            $result[] = [
                'workflow_id'   => $wf->id,
                'workflow_uuid' => $wf->uuid,
                'name'          => $wf->name,
                'description'   => $wf->description,
                'is_active'     => $wf->is_active,
                'permissions'   => json_decode($wf->permissions, true),
                'assigned_to'   => $assignmentData
            ];
        }
        return $result;
    }

    public function getMyApprovals($userId)
    {
        $user = User::find($userId);
        $roleId = $user ? $user->role : null;
        $approvalAssignments = HtappWorkflowRequestStepApprover::where(function ($q) use ($userId, $roleId) {
            $q->where('user_id', $userId);
            if (!is_null($roleId)) {
                $q->orWhere('role', $roleId);
            }
        })
            ->with(['requestStep.workflowRequest.workflow'])
            ->orderBy('id', 'DESC')
            ->get();
        $result = [];
        foreach ($approvalAssignments as $appr) {
            $req  = $appr->requestStep->workflowRequest;
            $step = $appr->requestStep;
            $result[] = [
                'workflow_request_uuid' => $req->uuid,
                'workflow_name'         => $req->workflow->name,
                'process_type'          => class_basename($req->process_type),
                'process_id'            => $req->process_id,
                'step_uuid'             => $step->uuid,
                'step_title'            => $step->title,
                'step_order'            => $step->step_order,
                'step_status'           => $step->status,
                'permissions'           => json_decode($step->permissions, true),
                'has_approved'          => (bool)$appr->has_approved,
                'workflow_status'       => $req->status,
                'initiated_at'          => $req->created_at,
                'last_updated'          => $step->updated_at
            ];
        }

        return $result;
    }


    // public function getMyApprovalsByModel($userId, $modelKey)
    // {
    //     $user = User::find($userId);
    //     // dd($user);
    //     $roleId = $user ? $user->role : null;
    //     $approvalAssignments =HtappWorkflowRequestStepApprover::where(function($q) use ($userId, $roleId) {
    //         $q->where('user_id', $userId);
    //         if (!is_null($roleId)) {$q->orWhere('role', $roleId);}})
    //     ->whereHas('requestStep.workflowRequest', function($q) use ($modelKey) {
    //         $q->where('process_type', $modelKey);
    //     })->with(['requestStep.workflowRequest.workflow'])->orderBy('id', 'DESC')->get();
    //     // HtappWorkflowRequestStepApprover::with('requestStep.workflowRequest')
    //     // ->where('user_id', $userId)
    //     // ->get()
    //     // ->pluck('requestStep.workflowRequest.process_type');
    //     $result = [];
    //     foreach ($approvalAssignments as $appr) {
    //         $req = $appr->requestStep->workflowRequest;
    //         $step = $appr->requestStep;
    //         $modelClass = config('workflow_models.' . $req->process_key);
    //         $record = $modelClass ? $modelClass::find($req->process_id) : null;
    //         $displayName = \DB::table('htapp_workflow_models')
    //                         ->where('model_key', $req->process_key)
    //                         ->value('display_name');
    //         $result[] = [
    //             'workflow_request_uuid' => $req->uuid,
    //             'workflow_name'         => $req->workflow->name,
    //             'model_key'             => $req->process_key,
    //             'model_name'            => $displayName,
    //             'request_id'            => $req->process_id,
    //             'step_uuid'             => $step->uuid,
    //             'step_title'            => $step->title,
    //             'step_order'            => $step->step_order,
    //             'step_status'           => $step->status,
    //             'permissions'           => json_decode($step->permissions, true),
    //             'has_approved'          => (bool)$appr->has_approved,
    //             'workflow_status'       => $req->status,
    //             'record_data'           => $record ? $record->only(['id']) : null,
    //             'initiated_at'          => $req->created_at,
    //             'last_updated'          => $step->updated_at
    //         ];
    //     }
    //     return $result;
    // }

    // This is update one that are used for filter with request_step_id
    public function getMyApprovalsByModel($userId, $modelKey, $requestStepId = null)
    {
        $user   = User::find($userId);
        $roleId = $user ? $user->role : null;

        $query = HtappWorkflowRequestStepApprover::query();

        $query->where(function ($q) use ($userId, $roleId) {
            $q->where('user_id', $userId);

            if (!is_null($roleId)) {
                $q->orWhere('role', $roleId);
            }
        });

        if (!empty($requestStepId)) {
            $query->where('request_step_id', $requestStepId);
        }


        $query->whereHas('requestStep.workflowRequest', function ($q) use ($modelKey) {
            $q->where('process_type', $modelKey);
        });

        $approvalAssignments = $query
            ->with(['requestStep.workflowRequest.workflow'])
            ->orderByDesc('id')
            ->get();

        /**
         * 🔹 RESPONSE
         */
        $result = [];

        foreach ($approvalAssignments as $appr) {

            $step = $appr->requestStep;
            $req  = $step->workflowRequest;

            $modelKeyValue = $req->process_key ?? $req->process_type;

            $modelClass = DB::table('htapp_workflow_models')
                ->where('model_key', $modelKeyValue)
                ->value('model_key');

            $record = null;
            if (!empty($modelClass) && class_exists($modelClass)) {
                $record = $modelClass::find($req->process_id);
            }

            $displayName = DB::table('htapp_workflow_models')
                ->where('model_key', $modelKeyValue)
                ->value('display_name');

            $previousStepApproved = true;

            if ($step->step_order > 1) {
                $previousStep = HtappWorkflowRequestStep::where('workflow_request_id', $req->id)
                    ->where('step_order', $step->step_order - 1)
                    ->first();

                if ($previousStep && $previousStep->status !== 'APPROVED') {
                    $previousStepApproved = false;
                }
            }

            $permissions = $previousStepApproved
                ? json_decode($step->permissions, true) ?? []
                : [];


            $result[] = [
                'workflow_request_uuid' => $req->uuid,
                'workflow_name'         => $req->workflow->name,
                'model_key'             => $modelKeyValue,
                'model_name'            => $displayName,
                'request_id'            => $req->process_id,

                'step_id'               => $step->id,
                'step_uuid'             => $step->uuid,
                'step_title'            => $step->title,
                'step_order'            => $step->step_order,
                'step_status'           => $step->status,

                'permissions'           => $permissions,
                'has_approved'          => (bool) $appr->has_approved,
                'workflow_status'       => $req->status,
                'message'               => $step->message,
                'confirmationMessage'   => $step->confirmationMessage,
                'notification'          => $step->notification,
                'record_data'           => $record ? $record->only(['id']) : null,
                'initiated_at'          => $req->created_at,
                'last_updated'          => $step->updated_at
            ];
        }

        return $result;
    }

    // Old code that are work proper ✅
    // public function getMyApprovalsByModel($userId, $modelKey)
    // {
    //     $user = User::find($userId);
    //     $roleId = $user ? $user->role : null;
    //     $approvalAssignments = HtappWorkflowRequestStepApprover::where(function($q) use ($userId, $roleId) {
    //             $q->where('user_id', $userId);
    //             if (!is_null($roleId)) {
    //                 $q->orWhere('role', $roleId);
    //             }
    //         })
    //         ->whereHas('requestStep.workflowRequest', function($q) use ($modelKey) {
    //             $q->where('process_type', $modelKey);
    //         })
    //         ->with(['requestStep.workflowRequest.workflow'])
    //         ->orderBy('id', 'DESC')
    //         ->get();

    //     // dd($approvalAssignments);
    //     $result = [];
    //     foreach ($approvalAssignments as $appr) {
    //         $req  = $appr->requestStep->workflowRequest;
    //         $step = $appr->requestStep;
    //         $modelClass = config('workflow_models.' . $req->process_key);
    //         $record = $modelClass ? $modelClass::find($req->process_id) : null;

    //         $displayName = DB::table('htapp_workflow_models')
    //         ->where('model_key', $req->process_key)
    //         ->value('display_name');
    //         $previousStepApproved = true;
    //         if ($step->step_order > 1) {
    //             $previousStep = HtappWorkflowRequestStep::where('workflow_request_id', $req->id)
    //                 ->where('step_order', $step->step_order - 1)
    //                 ->first();

    //             if ($previousStep && $previousStep->status !== 'APPROVED') {
    //                 $previousStepApproved = false;
    //             }
    //         }
    //         $permissions = [];

    //         if ($previousStepApproved) {
    //             $permissions = json_decode($step->permissions, true) ?? [];
    //         }

    //         $result[] = [
    //             'workflow_request_uuid' => $req->uuid,
    //             'workflow_name'         => $req->workflow->name,
    //             'model_key'             => $req->process_key,
    //             'model_name'            => $displayName,
    //             'request_id'            => $req->process_id,
    //             'step_id'               => $step->id,
    //             'step_uuid'             => $step->uuid,
    //             'step_title'            => $step->title,
    //             'step_order'            => $step->step_order,
    //             'step_status'           => $step->status,
    //             'permissions'           => $permissions,
    //             'has_approved'          => (bool)$appr->has_approved,
    //             'workflow_status'       => $req->status,
    //             'message'      => $step->message,
    //             'confirmationMessage'      => $step->confirmationMessage,
    //             'notification'          => $step->notification,
    //             'record_data'           => $record ? $record->only(['id']) : null,
    //             'initiated_at'          => $req->created_at,
    //             'last_updated'          => $step->updated_at
    //         ];
    //     }
    //     // dd($result);
    //     return $result;
    // }

    public function assign(array $data)
    {
        $existing = DB::table('htapp_workflow_assignments')->where('process_type', $data['process_type'])->first();
        if ($existing) {
            DB::table('htapp_workflow_assignments')
                ->where('id', $existing->id)
                ->update([
                    'workflow_id' => $data['workflow_id'],
                    'is_active'   => $data['is_active'] ?? true,
                    'updated_at'  => now(),
                ]);
        } else {
            DB::table('htapp_workflow_assignments')->insert([
                'process_type' => $data['process_type'],
                'workflow_id'  => $data['workflow_id'],
                'is_active'    => $data['is_active'] ?? true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
        return DB::table('htapp_workflow_assignments')->where('process_type', $data['process_type'])->first();
    }

    public function assignmentlist()
    {
        return DB::table('htapp_workflow_assignments')
            ->join('htapp_workflows', 'htapp_workflows.id', '=', 'htapp_workflow_assignments.workflow_id')
            ->join('htapp_workflow_models', 'htapp_workflow_models.model_key', '=', 'htapp_workflow_assignments.process_type')
            ->select(
                'htapp_workflow_assignments.id',
                'htapp_workflow_assignments.process_type',
                'htapp_workflow_models.display_name as display_name', // Display Name ⭐
                'htapp_workflow_assignments.workflow_id',
                'htapp_workflows.uuid as workflow_uuid',
                'htapp_workflows.name as workflow_name',
                'htapp_workflow_assignments.is_active',
                'htapp_workflow_assignments.created_at',
                'htapp_workflow_assignments.updated_at'
            )
            ->orderBy('htapp_workflow_assignments.id', 'DESC')
            ->get();
    }
    public function updateWorkflowAssignment(array $data)
    {
        $existing = DB::table('htapp_workflow_assignments')
            ->where('process_type', $data['process_type'])
            ->first();

        if (!$existing) {
            return [
                'success' => false,
                'message' => 'No workflow previously assigned for this process type.'
            ];
        }

        DB::table('htapp_workflow_assignments')
            ->where('process_type', $data['process_type'])
            ->update([
                'workflow_id' => $data['workflow_id'],
                'is_active'   => $data['is_active'] ?? true,
                'updated_at'  => now(),
            ]);

        return [
            'success' => true,
            'message' => 'Workflow assignment updated successfully',
            'data'    => DB::table('htapp_workflow_assignments')
                ->where('process_type', $data['process_type'])
                ->first()
        ];
    }
    public function toggleAssignment(array $data)
    {
        return DB::transaction(function () use ($data) {

            $processType = $data['process_type'];
            $workflowId  = $data['workflow_id'];
            $isActive    = $data['is_active'];
            if ($isActive) {
                DB::table('htapp_workflow_assignments')
                    ->where('process_type', $processType)
                    ->update(['is_active' => false]);
            }
            $assignment = DB::table('htapp_workflow_assignments')
                ->where('process_type', $processType)
                ->where('workflow_id', $workflowId)
                ->first();

            if ($assignment) {
                DB::table('htapp_workflow_assignments')
                    ->where('id', $assignment->id)
                    ->update([
                        'is_active'  => $isActive,
                        'updated_at' => now()
                    ]);
            } else {
                DB::table('htapp_workflow_assignments')
                    ->insert([
                        'process_type' => $processType,
                        'workflow_id'  => $workflowId,
                        'is_active'    => $isActive,
                        'created_at'   => now(),
                        'updated_at'   => now()
                    ]);
            }

            return [
                'process_type' => $processType,
                'workflow_id'  => $workflowId,
                'is_active'    => $isActive
            ];
        });
    }
}
