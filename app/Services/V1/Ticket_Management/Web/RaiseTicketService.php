<?php

namespace App\Services\V1\Ticket_Management\Web;

use App\Models\Ticket_Management\RaiseTicket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use App\Models\AgentCustomer;
use App\Models\Salesman;
use App\Models\CompanyCustomer;

class RaiseTicketService
{
    /**
     * Get all tickets with filters + pagination
     */
    public function getAll(array $filters = [], int $perPage = 10)
    {
        try {
            $query = RaiseTicket::orderByDesc('id');

            if (!empty($filters['title'])) {
                $query->where('title', 'ILIKE', '%' . $filters['title'] . '%');
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['priority'])) {
                $query->where('priority', $filters['priority']);
            }

            if (isset($filters['severity'])) {
                $query->where('severity', $filters['severity']);
            }

            if (isset($filters['issue_type'])) {
                $query->where('issue_type', $filters['issue_type']);
            }

            if (!empty($filters['from_date'])) {
                $query->whereDate('created_at', '>=', $filters['from_date']);
            }

            if (!empty($filters['to_date'])) {
                $query->whereDate('created_at', '<=', $filters['to_date']);
            }

            // Dropdown mode (no pagination)
            if (isset($filters['dropdown']) && filter_var($filters['dropdown'], FILTER_VALIDATE_BOOLEAN)) {
                return $query->where('status', 1)->get();
            }

            return $query->paginate($perPage);
        } catch (Exception $e) {
            throw new Exception("Failed to fetch tickets: " . $e->getMessage());
        }
    }

    /**
     * Get ticket by ID
     */
    public function getById(string $uuid)
    {
        try {
            return RaiseTicket::where('uuid', $uuid)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new Exception("Ticket not found with UUID {$uuid}");
        } catch (Exception $e) {
            throw new Exception("Failed to fetch ticket: " . $e->getMessage());
        }
    }

    /**
     * Create ticket
     */
    // public function create(array $data)
    // {
    //     DB::beginTransaction();
    //     try {
    //         if (request()->hasFile('attachment')) {
    //             $data['attachment'] = request()->file('attachment')->store('tickets', 'public');
    //         }

    //         if (empty($data['ticket_code'])) {
    //             $maxId = RaiseTicket::withTrashed()->max('id'); // works even if soft delete later
    //             $nextNumber = $maxId ? $maxId + 1 : 1;

    //             $data['ticket_code'] = 'HAR' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    //         }

    //         $data['uuid'] = \Str::uuid();
    //         // $data['user_id'] = Auth::id();
    //         // $data['role_id'] = Auth::user()->role_id ?? null;
    //         $data['created_user'] = Auth::id();
    //         $data['updated_user'] = Auth::id();

    //         $ticket = RaiseTicket::create($data);

    //         DB::commit();
    //         return $ticket;
    //     } catch (Exception $e) {
    //         // dd($e);
    //         DB::rollBack();
    //         throw new Exception("Failed to create ticket: " . $e->getMessage());
    //     }
    // }
    public function create(array $data)
    {
        DB::beginTransaction();

        try {

            // 🔹 Generate Ticket Code
            if (empty($data['ticket_code'])) {
                $maxId = RaiseTicket::withTrashed()->max('id');
                $nextNumber = $maxId ? $maxId + 1 : 1;
                $data['ticket_code'] = 'HAR' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            }

            // 🔹 UUID + Audit Fields
            $data['uuid'] = \Str::uuid();
            $data['created_user'] = Auth::id();
            $data['updated_user'] = Auth::id();

            // ❌ Remove attachment from data (important)
            unset($data['attachment']);

            // 🔹 Create Ticket
            $ticket = RaiseTicket::create($data);

            // ✅ Handle Multiple Attachments
            if (request()->hasFile('attachment')) {

                foreach (request()->file('attachment') as $file) {

                    $path = $file->store('tickets', 'public');

                    $ticket->attachments()->create([
                        'file_path' => $path
                    ]);
                }
            }

            DB::commit();

            return $ticket->load('attachments');
        } catch (\Exception $e) {

            DB::rollBack();
            throw new \Exception("Failed to create ticket: " . $e->getMessage());
        }
    }


    // public function update(string $uuid, array $data)
    // {
    //     DB::beginTransaction();

    //     try {
    //         $ticket = RaiseTicket::where('uuid', $uuid)->firstOrFail();

    //         // 🔍 OLD DATA (before update)
    //         $oldData = $ticket->getOriginal();

    //         // 📎 Attachment replace
    //         if (request()->hasFile('attachment')) {
    //             if ($ticket->attachment) {
    //                 Storage::disk('public')->delete($ticket->attachment);
    //             }

    //             $data['attachment'] = request()
    //                 ->file('attachment')
    //                 ->store('tickets', 'public');
    //         }

    //         // 🔐 Audit user
    //         $data['updated_user'] = Auth::id();

    //         // 🔥 Update ticket
    //         $ticket->fill($data);
    //         $ticket->save();

    //         // 🔄 Only changed fields
    //         $changed = $ticket->getChanges();

    //         // 🛑 IMPORTANT: No actual change → no history
    //         if (empty($changed)) {
    //             DB::commit();
    //             return $ticket;
    //         }

    //         /* --------------------------------
    //        🧾 BUILD updated_data (OLD + NEW)
    //     ---------------------------------*/
    //         $updated_data = [];

    //         foreach ($changed as $field => $newValue) {
    //             // skip system fields
    //             if (in_array($field, ['updated_at', 'updated_user'])) {
    //                 continue;
    //             }

    //             $updated_data[$field] = [
    //                 'old' => $oldData[$field] ?? null,
    //                 'new' => $newValue,
    //             ];
    //         }

    //         // 🛑 After filtering, still nothing? skip history
    //         if (empty($updated_data)) {
    //             DB::commit();
    //             return $ticket;
    //         }

    //         /* --------------------------------
    //        🧾 BUILD DESCRIPTION
    //     ---------------------------------*/
    //         $descriptionParts = [];

    //         if (isset($updated_data['status'])) {
    //             $descriptionParts[] =
    //                 "Status changed from {$updated_data['status']['old']} to {$updated_data['status']['new']}";
    //         }

    //         if (isset($updated_data['user_id'])) {
    //             $descriptionParts[] = "User reassigned";
    //         }

    //         if (isset($updated_data['priority'])) {
    //             $descriptionParts[] =
    //                 "Priority changed from {$updated_data['priority']['old']} to {$updated_data['priority']['new']}";
    //         }

    //         if (!empty($data['description'])) {
    //             $descriptionParts[] = $data['description'];
    //         }

    //         $description = !empty($descriptionParts)
    //             ? implode(', ', $descriptionParts)
    //             : 'Ticket updated';

    //         /* --------------------------------
    //        🧾 INSERT HISTORY (ONLY WHEN CHANGE EXISTS)
    //     ---------------------------------*/
    //         DB::table('tbl_ticket_update_history')->insert([
    //             'ticket_id'    => $ticket->id,
    //             'action_by'    => Auth::id(),
    //             'action_type'  => 'updated',
    //             'assign_user'  => $changed['user_id'] ?? null,
    //             'description'  => $description,
    //             'updated_data' => json_encode($updated_data),
    //             'action_at'    => now(),
    //         ]);

    //         DB::commit();
    //         return $ticket;
    //     } catch (ModelNotFoundException $e) {
    //         DB::rollBack();
    //         throw new Exception("Ticket not found with UUID {$uuid}");
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         throw new Exception("Failed to update ticket: " . $e->getMessage());
    //     }
    // }

    public function update(string $uuid, array $data)
    {
        DB::beginTransaction();

        try {

            $ticket = RaiseTicket::where('uuid', $uuid)->firstOrFail();

            $oldData = $ticket->getOriginal();

            $data['updated_user'] = Auth::id();

            unset($data['attachment']);

            /* -----------------------------
           📝 COMMENT SECTION
        ------------------------------*/
            if (!empty($data['comment'])) {

                $ticket->comments()->create([
                    'comment'      => $data['comment'],
                    'created_user' => Auth::id(),
                ]);

                unset($data['comment']); // prevent ticket column conflict
            }

            /* -----------------------------
           🔥 UPDATE TICKET
        ------------------------------*/
            $ticket->fill($data);
            $ticket->save();

            $changed = $ticket->getChanges();

            /* -----------------------------
           📎 MULTIPLE ATTACHMENT ADD
        ------------------------------*/
            if (request()->hasFile('attachment')) {

                foreach (request()->file('attachment') as $file) {

                    $path = $file->store('tickets', 'public');

                    $ticket->attachments()->create([
                        'file_path' => $path
                    ]);
                }
            }

            /* -----------------------------
           🧾 HISTORY (Only If Fields Changed)
        ------------------------------*/
            if (!empty($changed)) {

                $updated_data = [];

                foreach ($changed as $field => $newValue) {

                    if (in_array($field, ['updated_at', 'updated_user'])) {
                        continue;
                    }

                    $updated_data[$field] = [
                        'old' => $oldData[$field] ?? null,
                        'new' => $newValue,
                    ];
                }

                if (!empty($updated_data)) {

                    DB::table('tbl_ticket_update_history')->insert([
                        'ticket_id'    => $ticket->id,
                        'action_by'    => Auth::id(),
                        'action_type'  => 'updated',
                        'assign_user'  => $changed['user_id'] ?? null,
                        'description'  => 'Ticket updated',
                        'updated_data' => json_encode($updated_data),
                        'action_at'    => now(),
                    ]);
                }
            }

            DB::commit();

            return $ticket->load(['attachments', 'comments.createdUser']);
        } catch (\Exception $e) {

            DB::rollBack();
            throw new \Exception("Failed to update ticket: " . $e->getMessage());
        }
    }





    /**
     * Delete ticket
     */
    public function delete(int $id)
    {
        DB::beginTransaction();
        try {
            $ticket = RaiseTicket::findOrFail($id);

            if ($ticket->attachment) {
                Storage::disk('public')->delete($ticket->attachment);
            }

            $ticket->delete();

            DB::commit();
            return true;
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw new Exception("Ticket not found with ID {$id}");
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to delete ticket: " . $e->getMessage());
        }
    }

    /**
     * Global search
     */
    public function search(
        int $perPage = 10,
        ?string $keyword = null,
        bool $dropdown = false,
        array $filters = []
    ) {
        try {
            $query = RaiseTicket::query();

            $STATUS_LABEL_MAP = [
                1 => "Open",
                2 => "In Progress",
                3 => "Not Fixed",
                4 => "Ready For Retesting",
                5 => "Need Discussion",
                6 => "Partially Done",
                7 => "Reopen",
                8 => "Cancelled",
                9 => "Closed",
            ];

            $PRIORITY_LABEL_MAP = [
                1 => "P1",
                2 => "P2",
                3 => "P3",
                4 => "P4",
            ];

            $SEVERITY_LABEL_MAP = [
                1 => "Low",
                2 => "Medium",
                3 => "High",
                4 => "Critical",
            ];

            $statusMap   = array_change_key_case(array_flip($STATUS_LABEL_MAP), CASE_LOWER);
            $priorityMap = array_change_key_case(array_flip($PRIORITY_LABEL_MAP), CASE_LOWER);
            $severityMap = array_change_key_case(array_flip($SEVERITY_LABEL_MAP), CASE_LOWER);

            if (!empty($keyword)) {

                $keywordLower = strtolower($keyword);

                $query->where(function ($q) use (
                    $keyword,
                    $keywordLower,
                    $statusMap,
                    $priorityMap,
                    $severityMap
                ) {

                    $q->where('title', 'ILIKE', "%{$keyword}%")
                        ->orWhere('ticket_code', 'ILIKE', "%{$keyword}%");

                    if (isset($statusMap[$keywordLower])) {
                        $q->orWhere('status', $statusMap[$keywordLower]);
                    }

                    if (isset($priorityMap[$keywordLower])) {
                        $q->orWhere('priority', $priorityMap[$keywordLower]);
                    }

                    if (isset($severityMap[$keywordLower])) {
                        $q->orWhere('severity', $severityMap[$keywordLower]);
                    }
                });
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['priority'])) {
                $query->where('priority', $filters['priority']);
            }

            if (!empty($filters['severity'])) {
                $query->where('severity', $filters['severity']);
            }

            $query->orderBy('id', 'desc');

            if ($dropdown) {
                return $query->limit(50)->get();
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            throw new \Exception("Failed to search tickets: " . $e->getMessage());
        }
    }

    public function getTicketsByFlagAndId($flag, $id)
    {

        $map = [
            'customer' => 'customer',
            'salesman' => 'salesman',
            'companyCustomer' => 'companyCustomer'
        ];

        if (!isset($map[$flag])) {
            throw new \Exception('Invalid flag provided');
        }

        $column = $map[$flag];

        $tickets = RaiseTicket::where($column, $id)
            ->latest()
            ->get();

        return $tickets;
    }
}

