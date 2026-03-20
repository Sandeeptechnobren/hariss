<?php

namespace App\Http\Resources\V1\Ticket_Management\Web;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class RaiseTicketResource extends JsonResource
{
    public function toArray($request): array
    {
        $history = DB::table('tbl_ticket_update_history as h')
            ->leftJoin('users as action_user', 'action_user.id', '=', 'h.action_by')
            ->leftJoin('users as assign_user', 'assign_user.id', '=', 'h.assign_user')
            ->where('h.ticket_id', $this->id)
            ->orderBy('h.action_at', 'desc')
            ->select([
                'h.action_type',
                'h.description',
                'h.updated_data',
                'h.action_at',

                'action_user.id as action_by_id',
                'action_user.name as action_by_name',

                'assign_user.id as assign_user_id',
                'assign_user.name as assign_user_name',
            ])
            ->get()
            ->map(function ($item) {

                $changes = json_decode($item->updated_data, true) ?? [];

                $old = [];
                $new = [];
                $userIds = [];

                if (isset($changes['user_id'])) {
                    if (!empty($changes['user_id']['old'])) {
                        $userIds[] = $changes['user_id']['old'];
                    }
                    if (!empty($changes['user_id']['new'])) {
                        $userIds[] = $changes['user_id']['new'];
                    }
                }

                $userMap = [];
                if (!empty($userIds)) {
                    $userMap = DB::table('users')
                        ->whereIn('id', array_unique($userIds))
                        ->pluck('name', 'id');
                }

                foreach ($changes as $field => $values) {
                    if (!is_array($values)) {
                        continue;
                    }

                    if ($field === 'user_id') {
                        $old[$field] = !empty($values['old']) ? [
                            'id'   => (int) $values['old'],
                            'name' => $userMap[$values['old']] ?? null,
                        ] : null;

                        $new[$field] = !empty($values['new']) ? [
                            'id'   => (int) $values['new'],
                            'name' => $userMap[$values['new']] ?? null,
                        ] : null;

                        continue;
                    }

                    $old[$field] = is_numeric($values['old'])
                        ? (int) $values['old']
                        : $values['old'];

                    $new[$field] = is_numeric($values['new'])
                        ? (int) $values['new']
                        : $values['new'];
                }

                return [
                    'action_type' => $item->action_type,
                    'description' => $item->description,
                    'old' => $old,
                    'new' => $new,

                    'assign_user' => $item->assign_user_id ? [
                        'id'   => $item->assign_user_id,
                        'name' => $item->assign_user_name,
                    ] : null,

                    'action_by' => [
                        'id'   => $item->action_by_id,
                        'name' => $item->action_by_name,
                    ],

                    'action_at' => $item->action_at,
                ];
            });

        return [
            'id'          => $this->id,
            'uuid'        => $this->uuid,
            'ticket_code' => $this->ticket_code,
            'title'       => $this->title,
            'description' => $this->description,

            'attachments' => $this->attachments->map(function ($file) {
                return [
                    'id'   => $file->id,
                    'file_path' => $file->file_path,
                    // 'file_url'  => asset('storage/' . $file->file_path),
                ];
            }),

            'comments' => [
                'total' => $this->comments->count(),

                'data' => $this->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'comment' => $comment->comment,

                        'created_at' => $comment->created_at,
                        'updated_at' => $comment->updated_at,

                        'created_user' => $comment->createdUser ? [
                            'id' => $comment->createdUser->id,
                            'name' => $comment->createdUser->name,
                        ] : null,
                    ];
                })
            ],
            'device_detail' => $this->device_detail,
            'status'        => $this->status,
            'issue_type'    => $this->issue_type,
            'priority'      => $this->priority,
            'severity'      => $this->severity,

            'assign_user' => $this->assignUser ? [
                'id'   => $this->assignUser->id,
                'name' => $this->assignUser->name,
            ] : null,

            'created_user' => $this->createdUser ? [
                'id'   => $this->createdUser->id,
                'name' => $this->createdUser->name,
            ] : null,

            'time_to_resolve' => $this->time_to_resolve,
            'created_at'      => $this->created_at,

            'update_history' => $history,
        ];
    }
}
