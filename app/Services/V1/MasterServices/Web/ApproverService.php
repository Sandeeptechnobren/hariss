<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\ApprovalFlow;
// use App\Models\Approvalflow;
use App\Models\Approver;
use Illuminate\Support\Facades\DB;

class ApproverService
{
    public function list($filters = [])
    {
        $query = Approver::query();

        if (isset($filters['step_id'])) {
            $query->where('step_id', $filters['step_id']);
        }
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        return $query->get();
    }

    public function get($id)
    {
        return Approver::findOrFail($id);
    }

    public function create($data)
    {
        return Approver::create([
            'step_id'   => $data['step_id'],
            'user_id'   => $data['user_id'],
            'assigned_at' => now(),
        ]);
    }

public function update($uuid, $data)
    {
        $approver = Approver::where('uuid', $uuid)->firstOrFail();
        $approver->update([
            'user_id' => $data['user_id'],
        ]);
        return $approver;
    }


    // public function delete($id)
    // {
    //     $approver = Approver::findOrFail($id);
    //     return $approver->delete();
    // }
}
