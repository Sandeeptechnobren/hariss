<?php

namespace App\Services\V1\Agent_Transaction;

use App\Models\Agent_Transaction\NewCustomer;
use App\Models\AgentCustomer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Error;
use Exception;
use App\Helpers\DataAccessHelper;
use App\Helpers\CommonLocationFilter;
use Illuminate\Pagination\Paginator;

class NewCustomerService
{
    // public function getAll(int $perPage = 10, array $filters = [])
    // {
    //     try {
    //         $query = NewCustomer::with(['route', 'outlet_channel', 'category', 'subcategory'])
    //             ->orderByDesc('id');
    //         if (!empty($filters['approval_status'])) {
    //             $query->where('approval_status', $filters['approval_status']);
    //         }
    //         if (!empty($filters['search'])) {
    //             $query->where(function ($q) use ($filters) {
    //                 $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($filters['search']) . '%'])
    //                     ->orWhereRaw('LOWER(owner_name) LIKE ?', ['%' . strtolower($filters['search']) . '%']);
    //             });
    //         }

    //         return $query->paginate($perPage);
    //     } catch (Throwable $e) {
    //         Log::error('Failed to fetch customers', [
    //             'error' => $e->getMessage(),
    //             'filters' => $filters,
    //             'trace' => $e->getTraceAsString(),
    //         ]);
    //         throw new Exception('Something went wrong while fetching customers. Please try again.');
    //     }
    // }
    public function getAll(int $perPage = 50, array $filters = [])
    {
        // dd($filters);
        try {
            $query = NewCustomer::with([
                'route',
                'outlet_channel',
                'category',
                'subcategory',
                'getWarehouse'
            ])
                ->whereNull('deleted_at')
                ->orderByDesc('id');

            // 🔹 Approval Status
            if (isset($filters['approval_status']) && $filters['approval_status'] !== '') {
                $query->where('approval_status', $filters['approval_status']);
            }

            // 🔹 Search (name / owner)
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(owner_name) LIKE ?', ["%{$search}%"]);
                });
            }

            // 🔹 Subcategory
            if (!empty($filters['subcategory_id'])) {
                $query->where('subcategory_id', $filters['subcategory_id']);
            }

            // 🔹 Warehouse (IMPORTANT: column is `warehouse`)
            if (!empty($filters['warehouse'])) {
                $query->where('warehouse', $filters['warehouse']);
            }

            // 🔹 Outlet Channel
            if (!empty($filters['outlet_channel_id'])) {
                $query->where('outlet_channel_id', $filters['outlet_channel_id']);
            }

            // 🔹 Route
            if (!empty($filters['route_id'])) {
                $query->where('route_id', $filters['route_id']);
            }

            return $query->paginate($perPage);
        } catch (\Throwable $e) {
            \Log::error('Failed to fetch new customers', [
                'filters' => $filters,
                'error'   => $e->getMessage(),
            ]);

            throw new \Exception(
                'Something went wrong while fetching customers. Please try again.'
            );
        }
    }

    // public function getAll(int $perPage = 10, array $filters = [])
    // {
    //     try {
    //         $query = NewCustomer::with([
    //                 'route',
    //                 'outlet_channel',
    //                 'category',
    //                 'subcategory'
    //             ])
    //             ->orderByDesc('id');

    //         if (!empty($filters['approval_status'])) {
    //             $query->where('approval_status', $filters['approval_status']);
    //         }

    //         if (!empty($filters['search'])) {
    //             $query->where(function ($q) use ($filters) {
    //                 $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($filters['search']) . '%'])
    //                 ->orWhereRaw('LOWER(owner_name) LIKE ?', ['%' . strtolower($filters['search']) . '%']);
    //             });
    //         }

    //         $customers = $query->paginate($perPage);

    //         /**
    //          * =======================================================
    //          * 🔥 Inject approval workflow status (SAVED PATTERN)
    //          * =======================================================
    //          */
    //         $customers->getCollection()->transform(function ($customer) {

    //             $workflowRequest = \App\Models\HtappWorkflowRequest::where('process_type', 'New_Customer')
    //                 ->where('process_id', $customer->id)
    //                 ->orderBy('id', 'DESC')
    //                 ->first();

    //             if ($workflowRequest) {

    //                 $currentStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
    //                     ->whereIn('status', ['PENDING', 'IN_PROGRESS'])
    //                     ->orderBy('step_order')
    //                     ->first();

    //                 $totalSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
    //                     ->count();

    //                 $completedSteps = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
    //                     ->where('status', 'APPROVED')
    //                     ->count();

    //                 $lastApprovedStep = \App\Models\HtappWorkflowRequestStep::where('workflow_request_id', $workflowRequest->id)
    //                     ->where('status', 'APPROVED')
    //                     ->orderBy('step_order', 'DESC')
    //                     ->first();

    //                 $customer->approval_status = $lastApprovedStep
    //                     ? $lastApprovedStep->message
    //                     : 'Initiated';

    //                 $customer->current_step    = $currentStep?->title;
    //                 $customer->request_step_id = $currentStep?->id;
    //                 $customer->progress        = $totalSteps > 0
    //                     ? ($completedSteps . '/' . $totalSteps)
    //                     : null;

    //             } else {
    //                 $customer->approval_status = null;
    //                 $customer->current_step    = null;
    //                 $customer->request_step_id = null;
    //                 $customer->progress        = null;
    //             }

    //             return $customer;
    //         });

    //         return $customers;

    //     } catch (Throwable $e) {
    //         Log::error('Failed to fetch customers', [
    //             'error' => $e->getMessage(),
    //             'filters' => $filters,
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         throw new Exception('Something went wrong while fetching customers. Please try again.');
    //     }
    // }


    /**
     * Generate unique OSA code for new customers.
     */
    public function generateCode(): string
    {
        // Get the last OSA code
        $last = AgentCustomer::withTrashed()
            ->whereNotNull('osa_code')
            ->latest('id')
            ->first();

        // Calculate next number
        $nextNumber = 1;
        if ($last && !empty($last->osa_code)) {
            $numericPart = (int) preg_replace('/\D/', '', $last->osa_code);
            $nextNumber = $numericPart + 1;
        }

        // Generate code
        $osa_code = 'AC' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // Optional: final check to ensure uniqueness
        while (AgentCustomer::withTrashed()->where('osa_code', $osa_code)->exists()) {
            $nextNumber++;
            $osa_code = 'AC' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        return $osa_code;
    }


    // public function generateCode(): string
    // {
    //     do {
    //         $last = AgentCustomer::withTrashed()->latest('id')->first();
    //         $nextNumber = $last ? ((int) preg_replace('/\D/', '', $last->osa_code)) + 1 : 1;
    //         $osa_code = 'AC' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    //         // dd($osa_code);
    //     } while (AgentCustomer::withTrashed()->where('osa_code', $osa_code)->exists());

    //     return $osa_code;
    // }


    /**
     * Create a new customer record.
     */
    // public function create(array $data)
    // {
    //     DB::beginTransaction();

    //     try {
    //         $data = array_merge($data, [
    //             'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
    //             'osa_code' => $data['osa_code'] ?? $this->generateCode(),
    //             'approval_status' => $data['approval_status'] ?? 2, // Default: Pending
    //             'reject_reason' => $data['reject_reason'] ?? null,
    //         ]);

    //         $customer = NewCustomer::create($data);

    //         DB::commit();
    //         return $customer;
    //     } catch (Throwable $e) {
    //         DB::rollBack();

    //         Log::error('New customer creation failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //             'data' => $data,
    //             'user' => Auth::id(),
    //         ]);

    //         throw new Exception('Failed to create customer. Please try again later.');
    //     }
    // }

    public function updateCustomer(array $data): array
    {
        DB::beginTransaction();
        try {
            $message = 'No action performed.';
            $customer = null;
            $flag = isset($data['approval_status']) ? (int)$data['approval_status'] : null;

            // if (empty($data['osa_code'])) {
            //     $data['osa_code'] = $this->generateCode();
            // }

            if (!isset($data['osa_code']) || empty($data['osa_code'])) {
                $data['osa_code'] = $this->generateCode();
            }

            if ($flag === 1) {
                $contactNo = $data['contact_no'] ?? null;

                if ($contactNo) {
                    $exists = AgentCustomer::where('contact_no', $contactNo)->exists();

                    if ($exists) {
                        DB::commit();
                        return [
                            'status' => 'exists',
                            'message' => 'Customer already exists in AgentCustomer table.',
                            'customer' => AgentCustomer::where('contact_no', $contactNo)->first(),
                        ];
                    }
                }
                // Create new customer if not exists
                $customer = AgentCustomer::create([
                    'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
                    'osa_code' => $data['osa_code'],
                    'name' => $data['name'] ?? null,
                    'owner_name' => $data['owner_name'] ?? null,
                    'customer_type' => $data['customer_type'] ?? null,
                    'warehouse' => $data['warehouse'] ?? null,
                    'route_id' => $data['route_id'] ?? null,
                    'contact_no' => $data['contact_no'] ?? null,
                    'contact_no2' => $data['contact_no2'] ?? null,
                    'buyertype' => $data['buyertype'] ?? 0,
                    'payment_type' => $data['payment_type'] ?? 1,
                    'is_cash' => $data['is_cash'] ?? 0,
                    'vat_no' => $data['vat_no'] ?? null,
                    'creditday' => $data['creditday'] ?? null,
                    'credit_limit' => $data['credit_limit'] ?? null,
                    'outlet_channel_id' => $data['outlet_channel_id'] ?? null,
                    'category_id' => $data['category_id'] ?? null,
                    'subcategory_id' => $data['subcategory_id'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'qr_code' => $data['qr_code'] ?? null,
                    'status' => $data['status'] ?? 1,
                    'enable_promotion' => $data['enable_promotion'] ?? 1,
                    'landmark' => $data['landmark'] ?? null,
                    'district' => $data['district'] ?? null,
                    'street' => $data['street'] ?? null,
                    'town' => $data['town'] ?? null,
                ]);

                NewCustomer::where('uuid', $data['uuid'] ?? $customer->uuid)
                    ->update([
                        'customer_id' => $customer->id,
                        'approval_status' => 1,
                        'updated_at' => now(),
                    ]);

                $message = 'Customer successfully created in AgentCustomer table.';
            } elseif ($flag === 2) {
                $customer = AgentCustomer::where('uuid', $data['uuid'])->first();

                if (!$customer) {
                    throw new Exception('Customer not found for update flag.');
                }

                $message = '⚠️ Customer already exists in AgentCustomer table.';
            } elseif ($flag === 3) {
                $id = $data['id'] ?? null;
                $uuid = $data['uuid'] ?? null;
                $customer = null;

                if ($id) {
                    $customer = NewCustomer::find($id);
                } elseif ($uuid) {
                    $customer = NewCustomer::where('uuid', $uuid)->first();
                }

                if (!$customer) {
                    throw new Exception('Customer not found in NewCustomer table.');
                }

                $customer->update([
                    'approval_status' => 3,
                    'reject_reason' => $data['reject_reason'] ?? 'N/A',
                ]);

                $message = 'Customer rejection status updated successfully.';
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => $message,
                'customer' => $customer,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


//     public function updateCustomer(array $data): array
//     {
//         DB::beginTransaction();

//         try {
//             $message = 'No action performed.';
//             $customer = null;
//             $flag = isset($data['approval_status']) ? (int)$data['approval_status'] : null;

//             if (empty($data['osa_code'])) {
//                 $data['osa_code'] = $this->generateCode();
//             }

//             if ($flag === 1) {
//                 $contactNo = $data['contact_no'] ?? null;

//                 // 🔹 Step 1: Check for existing contact number
//                 if (!$contactNo) {
//                     $existingCustomer = AgentCustomer::where('contact_no', $contactNo)->first();
//                     if ($existingCustomer) {
//                         DB::rollBack();
//                         return [
//                             'status' => 'warning',
//                             'message' => 'Customer already exists in AgentCustomer table.',
//                             'customer' => $existingCustomer,
//                         ];
//                     }
//                 }

//                 // 🔹 Step 2: Match detection setup
//                 $compareFields = [
//                     'osa_code',
//                     'name',
//                     'email',
//                     'owner_name',
//                     'contact_no',
//                     'contact_no2',
//                     'vat_no',
//                     'street',
//                     'town',
//                     'district'
//                 ];

//                 $existingColumns = Schema::getColumnListing('agent_customers');
//                 $compareFields = array_intersect($compareFields, $existingColumns);

//                 $matches = [];
//                 $totalMatches = 0;

//                 // 🔹 Step 3: Chunked processing to prevent memory overflow
//                 AgentCustomer::select($compareFields)->chunk(1000, function ($customers) use ($data, $compareFields, &$matches, &$totalMatches) {
//                     foreach ($customers as $existing) {
//                         $totalScore = 0;
//                         $fieldsCompared = 0;

//                         foreach ($compareFields as $field) {
//                             $newValue = strtolower(trim($data[$field] ?? ''));
//                             $existingValue = strtolower(trim($existing->$field ?? ''));

//                             if ($newValue && $existingValue) {
//                                 if ($newValue === $existingValue) {
//                                     $percent = 100;
//                                 } else {
//                                     similar_text($newValue, $existingValue, $percent);
//                                 }
//                                 $totalScore += $percent;
//                                 $fieldsCompared++;
//                             }
//                         }

//                         if ($fieldsCompared > 0) {
//                             $averageMatch = $totalScore / $fieldsCompared;
//                             if ($averageMatch >= 20) { // Only keep relevant matches
//                                 $matches[] = [
//                                     'customer' => (array) $existing,
//                                     'match_percentage' => round($averageMatch, 2),
//                                 ];
//                             }
//                         }
//                     }
//                 });

//                 // 🔹 Step 4: Handle matches
//                 if (!empty($matches)) {
//                     usort($matches, fn($a, $b) => $b['match_percentage'] <=> $a['match_percentage']);
//                     $bestMatch = $matches[0];

//                     // Debug output of all matching customers
//                     dd([
//                         'All Matching Customers (sorted by %)' => $matches,
//                         'Top Match' => $bestMatch,
//                         'Total Matches Found' => count($matches),
//                     ]);

//                     if ($bestMatch['match_percentage'] >= 90) {
//                         DB::rollBack();
//                         return [
//                             'status' => 'warning',
//                             'message' => 'High similarity found. Possible duplicate in AgentCustomer.',
//                             'match_percentage' => $bestMatch['match_percentage'],
//                             'matched_customer' => $bestMatch['customer'],
//                         ];
//                     }
//                 }
// dd($bestMatch);
//                 // 🔹 Step 5: Create new customer
//                 $customer = AgentCustomer::create([
//                     'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
//                     'osa_code' => $data['osa_code'],
//                     'name' => $data['name'] ?? null,
//                     'owner_name' => $data['owner_name'] ?? null,
//                     'customer_type' => $data['customer_type'] ?? null,
//                     'warehouse' => $data['warehouse'] ?? null,
//                     'route_id' => $data['route_id'] ?? null,
//                     'contact_no' => $data['contact_no'] ?? null,
//                     'contact_no2' => $data['contact_no2'] ?? null,
//                     'buyertype' => $data['buyertype'] ?? 0,
//                     'payment_type' => $data['payment_type'] ?? 1,
//                     'is_cash' => $data['is_cash'] ?? 0,
//                     'vat_no' => $data['vat_no'] ?? null,
//                     'creditday' => $data['creditday'] ?? null,
//                     'credit_limit' => $data['credit_limit'] ?? null,
//                     'outlet_channel_id' => $data['outlet_channel_id'] ?? null,
//                     'category_id' => $data['category_id'] ?? null,
//                     'subcategory_id' => $data['subcategory_id'] ?? null,
//                     'latitude' => $data['latitude'] ?? null,
//                     'longitude' => $data['longitude'] ?? null,
//                     'qr_code' => $data['qr_code'] ?? null,
//                     'status' => $data['status'] ?? 1,
//                     'enable_promotion' => $data['enable_promotion'] ?? 1,
//                     'landmark' => $data['landmark'] ?? null,
//                     'district' => $data['district'] ?? null,
//                     'street' => $data['street'] ?? null,
//                     'town' => $data['town'] ?? null,
//                 ]);

//                 // 🔹 Step 6: Update related new_customer
//                 if ($customer) {
//                     NewCustomer::where('uuid', $data['uuid'] ?? $customer->uuid)
//                         ->update([
//                             'customer_id' => $customer->id,
//                             'approval_status' => 1,
//                             'updated_at' => now(),
//                         ]);
//                 }

//                 $message = 'Customer successfully created in AgentCustomer table.';
//             } elseif ($flag === 2) {
//                 // Update existing customer
//                 $customer = AgentCustomer::where('uuid', $data['uuid'])->first();
//                 if (!$customer) {
//                     throw new Exception('Customer not found for update flag.');
//                 }
//                 $message = 'Customer already exists in AgentCustomer table.';
//             } elseif ($flag === 3) {
//                 // Reject new customer
//                 $customer = NewCustomer::where('uuid', $data['uuid'])->first();
//                 if (!$customer) {
//                     throw new Exception('Customer not found in NewCustomer table.');
//                 }

//                 $customer->update([
//                     'approval_status' => 3,
//                     'reject_reason' => $data['reject_reason'] ?? 'N/A',
//                 ]);

//                 $message = 'Customer rejection status updated successfully.';
//             }

//             DB::commit();

//             return [
//                 'status' => 'success',
//                 'message' => $message,
//                 'customer' => $customer,
//             ];
//         } catch (Exception $e) {
//             DB::rollBack();
//             return [
//                 'status' => 'error',
//                 'message' => $e->getMessage(),
//                 'trace' => $e->getTraceAsString(),
//             ];
//         }
//     }



    // public function updateCustomer(array $data): array
    // {
    //     DB::beginTransaction();

    //     try {
    //         $message = 'No action performed.';
    //         $customer = null;
    //         $flag = isset($data['approval_status']) ? (int)$data['approval_status'] : null;

    //         if (empty($data['osa_code'])) {
    //             $data['osa_code'] = $this->generateCode();
    //         }

    //         if ($flag === 1) {
    //             $customerId = $data['customer_id'] ?? null;

    //             if ($customerId) {
    //                 $exists = AgentCustomer::where('id', $customerId)->exists();

    //                 if ($exists) {
    //                     $message = 'Customer already exists in AgentCustomer table.';
    //                     $customer = AgentCustomer::where('id', $customerId)->first();
    //                 } else {
    //                     dd($customer);
    //                     // Create new customer since ID provided doesn't exist
    //                     $customer = AgentCustomer::create([
    //                         'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
    //                         'osa_code' => $data['osa_code'],
    //                         'name' => $data['name'] ?? null,
    //                         'owner_name' => $data['owner_name'] ?? null,
    //                         'customer_type' => $data['customer_type'] ?? null,
    //                         'warehouse' => $data['warehouse'] ?? null,
    //                         'route_id' => $data['route_id'] ?? null,
    //                         'contact_no' => $data['contact_no'] ?? null,
    //                         'contact_no2' => $data['contact_no2'] ?? null,
    //                         'buyertype' => $data['buyertype'] ?? 0,
    //                         'payment_type' => $data['payment_type'] ?? 1,
    //                         'is_cash' => $data['is_cash'] ?? 0,
    //                         'vat_no' => $data['vat_no'] ?? null,
    //                         'creditday' => $data['creditday'] ?? null,
    //                         'credit_limit' => $data['credit_limit'] ?? null,
    //                         'outlet_channel_id' => $data['outlet_channel_id'] ?? null,
    //                         'category_id' => $data['category_id'] ?? null,
    //                         'subcategory_id' => $data['subcategory_id'] ?? null,
    //                         'latitude' => $data['latitude'] ?? null,
    //                         'longitude' => $data['longitude'] ?? null,
    //                         'qr_code' => $data['qr_code'] ?? null,
    //                         'status' => $data['status'] ?? 1,
    //                         'enable_promotion' => $data['enable_promotion'] ?? 1,
    //                         'landmark' => $data['landmark'] ?? null,
    //                         'district' => $data['district'] ?? null,
    //                         'street' => $data['street'] ?? null,
    //                         'town' => $data['town'] ?? null,
    //                     ]);

    //                     $message = 'Customer successfully created in AgentCustomer table.';
    //                 }
    //             } else {
    //                 // Create new if no customer_id provided
    //                 $customer = AgentCustomer::create([
    //                     'uuid' => $data['uuid'] ?? Str::uuid()->toString(),
    //                     'osa_code' => $data['osa_code'],
    //                     'name' => $data['name'] ?? null,
    //                     'owner_name' => $data['owner_name'] ?? null,
    //                     'customer_type' => $data['customer_type'] ?? null,
    //                     'warehouse' => $data['warehouse'] ?? null,
    //                     'route_id' => $data['route_id'] ?? null,
    //                     'contact_no' => $data['contact_no'] ?? null,
    //                     'contact_no2' => $data['contact_no2'] ?? null,
    //                     'buyertype' => $data['buyertype'] ?? 0,
    //                     'payment_type' => $data['payment_type'] ?? 1,
    //                     'is_cash' => $data['is_cash'] ?? 0,
    //                     'vat_no' => $data['vat_no'] ?? null,
    //                     'creditday' => $data['creditday'] ?? null,
    //                     'credit_limit' => $data['credit_limit'] ?? null,
    //                     'outlet_channel_id' => $data['outlet_channel_id'] ?? null,
    //                     'category_id' => $data['category_id'] ?? null,
    //                     'subcategory_id' => $data['subcategory_id'] ?? null,
    //                     'latitude' => $data['latitude'] ?? null,
    //                     'longitude' => $data['longitude'] ?? null,
    //                     'qr_code' => $data['qr_code'] ?? null,
    //                     'status' => $data['status'] ?? 1,
    //                     'enable_promotion' => $data['enable_promotion'] ?? 1,
    //                     'landmark' => $data['landmark'] ?? null,
    //                     'district' => $data['district'] ?? null,
    //                     'street' => $data['street'] ?? null,
    //                     'town' => $data['town'] ?? null,
    //                 ]);

    //                 $message = 'Customer successfully created in AgentCustomer table.';
    //             }
    //         } elseif ($flag === 2) {
    //             $customer = AgentCustomer::where('uuid', $data['uuid'])->first();

    //             if (!$customer) {
    //                 throw new Exception('Customer not found for update flag.');
    //             }

    //             $message = '⚠️ Customer already exists in AgentCustomer table.';
    //         } elseif ($flag === 3) {
    //             $id = $data['id'] ?? null;
    //             $uuid = $data['uuid'] ?? null;

    //             $customer = $id
    //                 ? NewCustomer::find($id)
    //                 : ($uuid ? NewCustomer::where('uuid', $uuid)->first() : null);

    //             if (!$customer) {
    //                 throw new Exception('Customer not found in NewCustomer table.');
    //             }

    //             $customer->update([
    //                 'approval_status' => 3,
    //                 'reject_reason' => $data['reject_reason'] ?? 'N/A',
    //             ]);

    //             $message = 'Customer rejection status updated successfully.';
    //         }

    //         DB::commit();

    //         return [
    //             'status' => 'success',
    //             'message' => $message,
    //             'customer' => $customer,
    //         ];
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         throw $e;
    //     }
    // }


    /**
     * Find a customer by UUID with relationships.
     */
    public function findByUuid(string $uuid): ?NewCustomer
    {
        if (!Str::isUuid($uuid)) {
            return null;
        }

        return NewCustomer::with(['route', 'outlet_channel', 'category', 'subcategory'])
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Update customer record by UUID.
     */
    public function updateByUuid(string $uuid, array $validated)
    {
        $customer = $this->findByUuid($uuid);

        if (!$customer) {
            throw new Exception("Customer not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $data = array_merge($validated, [
                'updated_user' => Auth::id(),
            ]);

            // If approval status is reject, ensure reject_reason is filled
            if (isset($data['approval_status']) && $data['approval_status'] == 3 && empty($data['reject_reason'])) {
                throw new Exception('Reject reason is required when approval status is Rejected.');
            }

            $customer->update($data);
            DB::commit();

            return $customer->fresh();
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Customer update failed', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
                'payload' => $validated,
                'trace' => $e->getTraceAsString(),
            ]);

            throw new Exception('Failed to update customer. Please try again.');
        }
    }

    /**
     * Soft delete customer by UUID.
     */
    public function deleteByUuid(string $uuid): void
    {
        $customer = $this->findByUuid($uuid);

        if (!$customer) {
            throw new Exception("Customer not found or invalid UUID: {$uuid}");
        }

        DB::beginTransaction();

        try {
            $customer->delete();
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Customer deletion failed', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new Exception('Failed to delete customer. Please try again.');
        }
    }

    public function globalFilter(int $perPage = 50, array $filters = [])
    {
        $filter = $filters['filter'] ?? [];

        if (!empty($filters['current_page'])) {
            Paginator::currentPageResolver(function () use ($filters) {
                return (int) $filters['current_page'];
            });
        }

        $query = NewCustomer::latest('id');

        if (!empty($filter)) {

            $warehouseIds = CommonLocationFilter::resolveWarehouseIds([
                'company_id'   => CommonLocationFilter::normalizeIds($filter['company_id'] ?? null),
                'region_id'    => CommonLocationFilter::normalizeIds($filter['region_id'] ?? null),
                'area_id'      => CommonLocationFilter::normalizeIds($filter['area_id'] ?? null),
                'warehouse_id' => CommonLocationFilter::normalizeIds($filter['warehouse_id'] ?? null),
                'route_id'     => CommonLocationFilter::normalizeIds($filter['route_id'] ?? null),
            ]);

            if (!empty($warehouseIds)) {
                $query->whereIn('warehouse', $warehouseIds);
            }

            // ✅ Approval status filter
            if (isset($filter['approval_status'])) {

                $approvalStatuses = CommonLocationFilter::normalizeIds($filter['approval_status']);

                $query->whereIn('approval_status', $approvalStatuses);
            }
        }

        return $query->paginate($perPage);
    }
}
