<?php

namespace App\Services\V1\Settings\Web;

use App\Models\AssetManufacturer;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Str;

class AssetManufacturerService
{
    /**
     * Create Manufacturer
     */
    public function create(array $data)
    {
        DB::beginTransaction();

        try {
            $data['uuid'] = Str::uuid();

            $manufacturer = AssetManufacturer::create($data);

            DB::commit();

            return [
                'status'  => 'success',
                'code'    => 201,
                'message' => 'Manufacturer created successfully',
                'data'    => $manufacturer
            ];

        } catch (Exception $e) {

            DB::rollBack();

            return [
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * List Manufacturers
     */
    public function list(int $perPage = 50)
    {
        try {
            $result = AssetManufacturer::orderBy('id', 'desc')->paginate($perPage);

            return [
                'status' => 'success',
                'code'   => 200,
                'message' => 'Manufacturers fetched successfully',
                'data'   => $result->items(),
                'pagination' => [
                    'page'         => $result->currentPage(),
                    'limit'        => $result->perPage(),
                    'totalPages'   => $result->lastPage(),
                    'totalRecords' => $result->total(),
                ]
            ];

        } catch (Exception $e) {

            return [
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage()
            ];
        }
    }
}
