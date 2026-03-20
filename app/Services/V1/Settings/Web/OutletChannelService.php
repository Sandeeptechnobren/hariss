<?php

namespace App\Services\V1\Settings\Web;

use App\Models\OutletChannel;
use App\Models\CustomerCategory;
use App\Models\CustomerSubCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;

class OutletChannelService
{
    use ApiResponse;

    private function generateCode(): string
    {
        $lastRecord = OutletChannel::withTrashed()->orderByDesc('id')->first();
        $nextId = $lastRecord ? $lastRecord->id + 1 : 1;
        return 'OC' . str_pad($nextId, 2, '0', STR_PAD_LEFT);
    }

    // public function getAll($perPage = 10)
    // {
    //     try {
    //         $channels = OutletChannel::paginate($perPage);

    //         $pagination = [
    //             'page'         => $channels->currentPage(),
    //             'limit'        => $channels->perPage(),
    //             'totalPages'   => $channels->lastPage(),
    //             'totalRecords' => $channels->total(),
    //         ];

    //         return $this->success(
    //             $channels->items(),
    //             'Outlet Channels fetched successfully',
    //             200,
    //             $pagination
    //         );
    //     } catch (Exception $e) {
    //         return $this->fail('Failed to fetch outlet channels', 500, [$e->getMessage()]);
    //     }
    // }

public function getAll(int $perPage = 10, bool $dropdown = false): array
{
    try {

        // Get the status filter from request
        $statusFilter = request()->query('status');

        // 🔹 DROPDOWN MODE
        if ($dropdown) {
            $query = OutletChannel::select(
                'id',
                'outlet_channel_code',
                'outlet_channel',
                'status'
            )->orderBy('outlet_channel', 'asc');

            // Apply status filter if provided
            if ($statusFilter !== null) {
                $query->where('status', $statusFilter);
            } else {
                $query->where('status', 1); // Default for dropdown
            }

            $channels = $query->get();

            return [
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Outlet Channels fetched successfully',
                'data'    => $channels,
            ];
        }

        // 🔹 PAGINATED MODE
        $query = OutletChannel::select(
            'id',
            'outlet_channel_code',
            'outlet_channel',
            'status',
            'created_user',
            'updated_user'
        )
        ->with([
            'createdBy:id,name,username',
            'updatedBy:id,name,username',
        ])
        ->orderBy('id', 'desc');

        // Apply status filter if provided
        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }

        $channels = $query->paginate($perPage);

        return [
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Outlet Channels fetched successfully',
            'data'    => $channels->items(),
            'pagination' => [
                'current_page'   => $channels->currentPage(),
                'per_page'       => $channels->perPage(),
                'total_pages'    => $channels->lastPage(),
                'total_records'  => $channels->total(),
            ],
        ];
    } catch (\Exception $e) {
        return [
            'status'  => 'error',
            'code'    => 500,
            'message' => 'Failed to fetch outlet channels',
            'error'   => $e->getMessage(),
        ];
    }
}


    // public function getAll($perPage = 10)
    // {
    //     try {
    //         $channels = OutletChannel::select(
    //             'id',
    //             'outlet_channel_code',
    //             'outlet_channel',
    //             'status',
    //             'created_user',
    //             'updated_user',
    //         )->with([
    //             'createdBy' => function ($q) {
    //                 $q->select('id', 'name', 'username');
    //             },
    //             'updatedBy' => function ($q) {
    //                 $q->select('id', 'name', 'username');
    //             }
    //         ])->orderBy('id', 'desc')
    //             ->paginate($perPage);

    //         return [
    //             'status'  => 'success',
    //             'code'    => 200,
    //             'message' => 'Outlet Channels fetched successfully',
    //             'data'    => $channels->items(),
    //             'pagination' => [
    //                 'current_page' => $channels->currentPage(),
    //                 'per_page'     => $channels->perPage(),
    //                 'total_pages'  => $channels->lastPage(),
    //                 'total_records' => $channels->total(),
    //             ],
    //         ];
    //     } catch (\Exception $e) {
    //         return [
    //             'status'  => 'error',
    //             'code'    => 500,
    //             'message' => 'Failed to fetch outlet channels',
    //             'error'   => $e->getMessage(),
    //         ];
    //     }
    // }

    public function getById($id)
    {
        try {
            $channel = OutletChannel::findOrFail($id);
            return $this->success($channel, 'Outlet Channel fetched successfully');
        } catch (Exception $e) {
            return $this->fail('Outlet Channel not found', 404, [$e->getMessage()]);
        }
    }
    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            if (empty($data['outlet_channel_code'])) {
                do {
                    $data['outlet_channel_code'] = $this->generateCode();
                } while (OutletChannel::withTrashed()->where('outlet_channel_code', $data['outlet_channel_code'])->exists());
            }

            $data['created_user'] = Auth::id();
            $data['updated_user'] = Auth::id();

            $outletChannel = OutletChannel::create($data);

            DB::commit();

            return [
                'status'  => 'success',
                'code'    => 201,
                'message' => 'Outlet Channel created successfully',
                'data'    => $outletChannel
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to create outlet channel',
                'error'   => $e->getMessage()
            ];
        }
    }

    public function update($id, array $data)
    {
        DB::beginTransaction();

        try {
            $outletChannel = OutletChannel::findOrFail($id);

            $data['updated_user'] = Auth::id();

            $outletChannel->update($data);

            DB::commit();
            return $outletChannel;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Outlet Channel update failed: ' . $e->getMessage(), [
                'id' => $id,
                'data' => $data
            ]);

            return null;
        }
    }


    public function delete(int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $deleted = OutletChannel::where('id', $id)->delete();

            if ($deleted === 0) {
                DB::rollBack();
                return $this->fail('Outlet Channel does not exist', 404);
            }

            DB::commit();
            return $this->success(null, 'Outlet Channel deleted successfully', 200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->fail('Failed to delete outlet channel', 500, [$e->getMessage()]);
        }
    }
    // public function delete($id)
    //     {
    //         DB::beginTransaction();
    //         try {
    //             $outletChannel = OutletChannel::find($id);

    //             if (!$outletChannel) {
    //                 DB::rollBack();
    //                 return $this->fail('Outlet Channel not found', 404);
    //             }

    //             $outletChannel->delete();

    //             DB::commit();
    //             return $this->success(null, 'Outlet Channel deleted successfully', 200);
    //         } catch (Exception $e) {
    //             DB::rollBack();
    //             return $this->fail('Failed to delete outlet channel', 500, [$e->getMessage()]);
    //         }
    //     }


    public function getHierarchy(?int $outletChannelId = null): array
    {
        $channelQuery = OutletChannel::select(
            'id',
            'outlet_channel',
            'outlet_channel_code'
        )
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->orderByDesc('id');

        // 🔹 Apply filter if outlet_channel_id is provided
        if (!empty($outletChannelId)) {
            $channelQuery->where('id', $outletChannelId);
        }

        $channels = $channelQuery->get();

        return $channels->map(function ($channel) {

            // 🔹 Categories
            $categories = CustomerCategory::select(
                'id',
                'outlet_channel_id',
                'customer_category_code',
                'customer_category_name'
            )
                ->where('outlet_channel_id', $channel->id)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->get();

            return [
                'id'   => $channel->id,
                'name' => $channel->outlet_channel,
                'code' => $channel->outlet_channel_code,

                'category_data' => $categories->map(function ($category) {

                    // 🔹 Sub Categories
                    $subCategories = CustomerSubCategory::select(
                        'id',
                        'customer_category_id',
                        'customer_sub_category_code',
                        'customer_sub_category_name'
                    )
                        ->where('customer_category_id', $category->id)
                        ->where('status', 1)
                        ->get();

                    return [
                        'id'   => $category->id,
                        'code' => $category->customer_category_code,
                        'name' => $category->customer_category_name,

                        'sub_category_data' => $subCategories->map(function ($subCategory) {
                            return [
                                'id'   => $subCategory->id,
                                'code' => $subCategory->customer_sub_category_code,
                                'name' => $subCategory->customer_sub_category_name
                            ];
                        })->values()->toArray()

                    ];
                })->values()->toArray()

            ];
        })->values()->toArray();
    }

        public function updateChannelStatus(array $vehicleIds, $status)
    {
        $updated = OutletChannel::whereIn('id', $vehicleIds)->update(['status' => $status]);
        return $updated > 0;
    }
}
