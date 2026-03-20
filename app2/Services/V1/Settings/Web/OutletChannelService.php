<?php

namespace App\Services\V1\Settings\Web;

use App\Models\OutletChannel;
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
    public function getAll($perPage = 10)
    {
        try {
            $channels = OutletChannel::select(
                'id',
                'outlet_channel_code',
                'outlet_channel',
                'status',
                'created_user',
                'updated_user',
            )->with([
                'createdBy' => function ($q) {
                    $q->select('id', 'firstname', 'lastname', 'username');
                },
                'updatedBy' => function ($q) {
                    $q->select('id', 'firstname', 'lastname', 'username');
                }
            ])->paginate($perPage);

            return [
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Outlet Channels fetched successfully',
                'data'    => $channels->items(),
                'pagination' => [
                    'current_page' => $channels->currentPage(),
                    'per_page'     => $channels->perPage(),
                    'total_pages'  => $channels->lastPage(),
                    'total_records' => $channels->total(),
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
}
