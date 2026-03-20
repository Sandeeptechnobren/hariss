<?php

namespace App\Http\Controllers\V1\Master\Mob;

use App\Http\Controllers\Controller;
use App\Services\V1\MasterServices\Mob\SettingService;
use Illuminate\Http\Request;
use Exception;

class SettingController extends Controller
{
    protected $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * @OA\Post( 
     *     path="/mob/master_mob/salesman/setting",
     *     tags={"Salesman Authentication"},
     *     summary="Fetch and save all master data",
     *  
     *     description="This API fetches master data from database tables like items, customer categories, outlet channels, pricing headers, etc., and saves them as text files in storage. Returns the file paths.",
     *     @OA\Response(
     *         response=200,
     *         description="Data saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data saved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="files",
     *                     type="object",
     *                     @OA\Property(property="item_file", type="string", example="storage/static_files/user_admin.txt"),
     *                     @OA\Property(property="customer_category_file", type="string", example="storage/static_files/user_category_admin.txt"),
     *                     @OA\Property(property="customer_subcategory_file", type="string", example="storage/static_files/user_sub_category_admin.txt"),
     *                     @OA\Property(property="outlet_channel_file", type="string", example="storage/static_files/user_channel_admin.txt"),
     *                     @OA\Property(property="pricing_headers_file", type="string", example="storage/static_files/user_headers_admin.txt")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Something went wrong while saving data"
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            // Default username
            $username = 'admin';

            $files = $this->settingService->saveAllData($username);

            return response()->json([
                'status' => true,
                'message' => 'Data saved successfully',
                'data' => [
                    'files' => $files
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
        
    }
    /**
     * @OA\Get(
     *      path="/mob/master_mob/salesman/warehouses",
     *     tags={"Salesman Authentication"},
     *     summary="Get all warehouses",
     *     description="Returns all warehouses with id, warehouse_code and warehouse_name.",
     *     @OA\Response(
     *         response=200,
     *         description="List of warehouses",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="warehouse_code", type="string", example="WH0001"),
     *                 @OA\Property(property="warehouse_name", type="string", example="new")
     *             )
     *         )
     *     )
     * )
     */
    public function show()
    {
        $warehouses = $this->settingService->getWarehouses();

        return response()->json($warehouses);
    }
}