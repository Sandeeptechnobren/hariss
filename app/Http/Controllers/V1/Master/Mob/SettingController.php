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
     *                     @OA\Property(property="item_file", type="string", example="storage/salesman_files/user_admin.txt"),
     *                     @OA\Property(property="customer_category_file", type="string", example="storage/salesman_files/user_category_admin.txt"),
     *                     @OA\Property(property="customer_subcategory_file", type="string", example="storage/salesman_files/user_sub_category_admin.txt"),
     *                     @OA\Property(property="outlet_channel_file", type="string", example="storage/salesman_files/user_channel_admin.txt"),
     *                     @OA\Property(property="pricing_headers_file", type="string", example="storage/salesman_files/user_headers_admin.txt")
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
 *     path="/mob/master_mob/salesman/warehouses",
 *     tags={"Salesman Authentication"},
 *     summary="Get warehouse(s) by salesman ID",
 *     description="Returns the warehouse(s) assigned to a specific salesman. Pass salesman_id as query parameter.",
 *
 *     @OA\Parameter(
 *         name="salesman_id",
 *         in="query",
 *         description="ID of the salesman",
 *         required=true,
 *         @OA\Schema(type="integer", example=7)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Warehouse(s) retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="salesman_id", type="integer", example=7),
 *             @OA\Property(
 *                 property="warehouse",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=3),
 *                 @OA\Property(property="warehouse_code", type="string", example="WH003"),
 *                 @OA\Property(property="warehouse_name", type="string", example="North Zone Warehouse"),
 *                 @OA\Property(property="location", type="string", example="Delhi")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=404,
 *         description="No warehouse assigned to this salesman",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="No warehouse assigned to this salesman")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="The salesman_id field is required."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 @OA\Property(
 *                     property="salesman_id",
 *                     type="array",
 *                     @OA\Items(type="string", example="The selected salesman_id is invalid.")
 *                 )
 *             )
 *         )
 *     )
 * )
 */
   public function show(Request $request)
    {
        $validated = $request->validate([
            'salesman_id' => 'required|integer|exists:salesman,id',
        ]);

        $warehouses = $this->settingService->getWarehousesBySalesman($validated['salesman_id']);

        if ($warehouses->isEmpty()) {
            return response()->json([
                'message' => 'No warehouse assigned to this salesman'
            ], 404);
        }
        $warehouses = $warehouses->map(function ($wh) {
        return [
            'id' => $wh->id,
            'code' => $wh->warehouse_code,
            'name' => $wh->warehouse_name,
            'location' => $wh->locationRelation?->name, // ← ONLY name
            'tin_no' => $wh->tin_no,
            'is_efris'=> $wh->is_efris,
            'owner_number' => $wh->owner_number,
            'warehouse_manager_contact' => $wh->warehouse_manager_contact,
        ];
    });

        return response()->json([
            'salesman_id' => $validated['salesman_id'],
            'warehouses' => $warehouses,
        ]);
    }
	

      /**
     * @OA\Get(
     *     path="/mob/master_mob/salesman/salesman_list",
     *     summary="Get list of salesmen by warehouse ID",
     *     description="Returns a list of salesmen for a specific warehouse",
     *     operationId="getSalesmenByWarehouse",
     *     tags={"Salesman Authentication"},
     *
     *     @OA\Parameter(
     *         name="warehouse_id",
     *         in="query",
     *         description="The ID of the warehouse to filter salesmen",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of salesmen retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="warehouse_id", type="integer", example=1),
     *             @OA\Property(
     *                 property="salesmen",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="phone", type="string", example="9876543210"),
     *                     @OA\Property(property="email", type="string", example="john@example.com")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Missing or invalid warehouse_id",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="warehouse_id is required")
     *         )
     *     )
     * )
     */

     public function index(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|integer|exists:tbl_warehouse,id',
        ]);

        $salesmen = $this->settingService->getSalesmenByWarehouse($validated['warehouse_id']);

        return response()->json([
            'warehouse_id' => $validated['warehouse_id'],
            'salesmen' => $salesmen,
        ]);
    }
}