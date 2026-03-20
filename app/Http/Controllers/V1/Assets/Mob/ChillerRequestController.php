<?php

namespace App\Http\Controllers\V1\Assets\Mob;

use App\Http\Controllers\Controller;
use App\Models\ChillerRequest;
use App\Models\AsModelNumber;
use App\Http\Requests\V1\Assets\Mob\ChillerRequestRequest;
use App\Http\Requests\V1\Assets\Mob\UpdateChillerRequestRequest;
use App\Http\Resources\V1\Assets\Mob\ChillerRequestResource;
// use App\Http\Resources\V1\Assets\Web\GetCRFData;
use App\Services\V1\Assets\Mob\ChillerRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Storage;
use PDF;

/**
 * @OA\Schema(
 *     schema="ChillerRequest Mob",
 *     type="object",
 *     title="ChillerRequest",
 *     description="Schema for creating or updating a Chiller record",
 *     @OA\Property(property="owner_name", type="string", example="Ramesh Kumar"),
 *     @OA\Property(property="status", type="integer", enum={0,1}, example=1),
 *     @OA\Property(property="fridge_status", type="integer", enum={0,1}, example=0),
 *     @OA\Property(property="customer_id", type="integer", example=12),
 *     @OA\Property(property="warehouse_id", type="integer", example=3),
 *     @OA\Property(property="salesman_id", type="integer", example=9),
 *     @OA\Property(property="outlet_id", type="integer", example=5),
 *     @OA\Property(property="stock_share_with_competitor", type="integer", example=50),
 *     @OA\Property(property="contact_number", type="string", example="+91-9876543210"),
 *     @OA\Property(property="landmark", type="string", example="Near City Mall"),
 *     @OA\Property(property="existing_coolers", type="string", example="2"),
 *     @OA\Property(property="outlet_weekly_sale_volume", type="string", example="1500"),
 *     @OA\Property(property="display_location", type="string", example="Front Display Area"),
 *     @OA\Property(property="chiller_safty_grill", type="string", example="Installed"),
 *     @OA\Property(property="manager_sales_marketing", type="integer", example=15),
 *     @OA\Property(property="national_id", type="string", example="AB1234567"),
 *     @OA\Property(property="outlet_stamp", type="string", example="Outlet Stamp Verified"),
 *     @OA\Property(property="model", type="string", example="LG-CHL-2024"),
 *     @OA\Property(property="hil", type="string", example="HIL-009"),
 *     @OA\Property(property="ir_reference_no", type="string", example="IR-REF-789"),
 *     @OA\Property(property="installation_done_by", type="string", example="CoolCare Pvt Ltd"),
 *     @OA\Property(property="date_lnitial", type="string", example="2025-10-01"),
 *     @OA\Property(property="date_lnitial2", type="string", example="2025-10-05"),
 *     @OA\Property(property="contract_attached", type="string", example="Yes"),
 *     @OA\Property(property="machine_number", type="string", example="MCH-2456"),
 *     @OA\Property(property="brand", type="string", example="Samsung"),
 *     @OA\Property(property="lc_letter", type="string", example="LC-Document-123"),
 *     @OA\Property(property="trading_licence", type="string", example="LIC-998877"),
 *     @OA\Property(property="password_photo", type="string", example="PASS-IMG-001"),
 *     @OA\Property(property="outlet_address_proof", type="string", example="Proof Document"),
 *     @OA\Property(property="chiller_asset_care_manager", type="integer", example=22),
 *     @OA\Property(property="chiller_manager_id", type="integer", example=33),
 *     @OA\Property(property="is_merchandiser", type="integer", enum={0,1}, example=1),
 *     @OA\Property(property="iro_id", type="integer", example=44),
 *     @OA\Property(property="remark", type="string", example="All documents verified and approved."),
 *     @OA\Property(property="password_photo_file", type="string", format="binary", description="Upload password photo file"),
 *     @OA\Property(property="lc_letter_file", type="string", format="binary", description="Upload LC letter file"),
 *     @OA\Property(property="trading_licence_file", type="string", format="binary", description="Upload trading licence file"),
 *     @OA\Property(property="outlet_stamp_file", type="string", format="binary", description="Upload outlet stamp file"),
 *     @OA\Property(property="outlet_address_proof_file", type="string", format="binary", description="Upload outlet address proof file"),
 *     @OA\Property(property="sign__customer_file", type="string", format="binary", description="Upload customer signature file"),
 *     @OA\Property(property="national_id_file", type="string", format="binary", description="Upload national ID file")
 * )
 */
class ChillerRequestController extends Controller
{
    use ApiResponse;

    protected ChillerRequestService $service;

    public function __construct(ChillerRequestService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/mob/master_mob/chiller-request/list",
     *     tags={"ChillerRequest Mob"},
     *     summary="Get all chiller requests with pagination and optional filters",
     *     @OA\Parameter(name="outlet_name", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="List of chiller requests",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Chiller Requests fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/ChillerRequest Mob")
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=5),
     *                 @OA\Property(property="totalRecords", type="integer", example=50)
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage  = $request->get('limit', 50);
        $dropdown = $request->boolean('dropdown', false);
        $filters = $request->only([
            'asset_code',
            'asset_name',
            'warehouse_id',
            'status'
        ]);
        if ($dropdown) {
            $data = $this->service->getAll($perPage, $filters, true);

            return $this->success(
                $data,
                'Assets (dropdown) fetched successfully'
            );
        }
        $assets = $this->service->getAll($perPage, $filters, false);
        return $this->success(
            ChillerRequestResource::collection($assets->items()),
            'Assets fetched successfully',
            200,
            [
                'page'         => $assets->currentPage(),
                'limit'        => $assets->perPage(),
                'totalPages'   => $assets->lastPage(),
                'totalRecords' => $assets->total(),
            ]
        );
    }
/**
 * @OA\Post(
 *     path="/mob/master_mob/chiller-request/add",
 *     tags={"ChillerRequest Mob"},
 *     summary="Create a new chiller request",
 *     description="Create chiller request with form-data including file uploads",
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *
 *                 @OA\Property(property="osa_code", type="string", maxLength=200),
 *                 @OA\Property(property="owner_name", type="string", maxLength=200),
 *                 @OA\Property(property="status", type="integer", enum={0,1}),
 *                 @OA\Property(property="fridge_status", type="integer"),
 *
 *                 @OA\Property(property="customer_id", type="integer"),
 *                 @OA\Property(property="warehouse_id", type="integer"),
 *                 @OA\Property(property="salesman_id", type="integer"),
 *                 @OA\Property(property="outlet_id", type="integer"),
 *
 *                 @OA\Property(property="outlet_name", type="string", maxLength=255),
 *                 @OA\Property(property="contact_number", type="string", maxLength=255),
 *                 @OA\Property(property="landmark", type="string", maxLength=255),
 *                 @OA\Property(property="existing_coolers", type="string", maxLength=255),
 *                 @OA\Property(property="outlet_weekly_sale_volume", type="string", maxLength=20),
 *                 @OA\Property(property="display_location", type="string", maxLength=255),
 *                 @OA\Property(property="chiller_safty_grill", type="string", maxLength=255),
 *
 *                 @OA\Property(property="manager_sales_marketing", type="integer"),
 *                 @OA\Property(property="outlet_stamp", type="string", maxLength=255),
 *                 @OA\Property(property="model", type="string", maxLength=255),
 *                 @OA\Property(property="hil", type="string", maxLength=255),
 *                 @OA\Property(property="ir_reference_no", type="string", maxLength=255),
 *                 @OA\Property(property="installation_done_by", type="string", maxLength=255),
 *
 *                 @OA\Property(property="date_lnitial", type="string"),
 *                 @OA\Property(property="date_lnitial2", type="string"),
 *                 @OA\Property(property="contract_attached", type="string"),
 *                 @OA\Property(property="machine_number", type="string", maxLength=255),
 *                 @OA\Property(property="brand", type="string", maxLength=255),
 *
 *                 @OA\Property(property="lc_letter", type="string", maxLength=255),
 *                 @OA\Property(property="chiller_asset_care_manager", type="integer"),
 *                 @OA\Property(property="stock_share_with_competitor", type="integer"),
 *                 @OA\Property(property="national_id", type="string", maxLength=255),
 *                 @OA\Property(property="trading_licence", type="string", maxLength=255),
 *                 @OA\Property(property="password_photo", type="string", maxLength=255),
 *                 @OA\Property(property="outlet_address_proof", type="string", maxLength=255),
 *                 @OA\Property(property="chiller_size_requested", type="string", maxLength=255),
 *
 *                 @OA\Property(property="chiller_manager_id", type="integer"),
 *                 @OA\Property(property="is_merchandiser", type="integer", enum={0,1}),
 *                 @OA\Property(property="iro_id", type="integer"),
 *                 @OA\Property(property="remark", type="string"),
 *
 *                 @OA\Property(property="password_photo_file", type="string", format="binary"),
 *                 @OA\Property(property="lc_letter_file", type="string", format="binary"),
 *                 @OA\Property(property="trading_licence_file", type="string", format="binary"),
 *                 @OA\Property(property="outlet_stamp_file", type="string", format="binary"),
 *                 @OA\Property(property="outlet_address_proof_file", type="string", format="binary"),
 *                 @OA\Property(property="sign__customer_file", type="string", format="binary"),
 *                 @OA\Property(property="national_id_file", type="string", format="binary"),
 *                 @OA\Property(property="national_id1_file", type="string", format="binary"),
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(response=200, description="Chiller created successfully"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */
    public function store(ChillerRequestRequest $request): JsonResponse
    {
        $chiller = $this->service->create($request->validated());
       
        return response()->json([
        'status' => true,
        'message' => 'Chiller created successfully',
        'data'    => new ChillerRequestResource($chiller),
    ], 201);
    }


    /**
     * @OA\Get(
     *     path="/mob/master_mob/chiller-request/{uuid}",
     *     tags={"ChillerRequest Mob"},
     *     summary="Get a single chiller request by UUID",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Chiller Request details", @OA\JsonContent(ref="#/components/schemas/ChillerRequest Mob")),
     *     @OA\Response(response=404, description="Chiller Request not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        $chillerRequest = $this->service->findByUuid($uuid);
        if (!$chillerRequest) {
            return $this->fail('Chiller Request not found', 404);
        }
        return $this->success(new ChillerRequestResource($chillerRequest), 'Chiller Request fetched successfully');
    }


    /**
     * @OA\Put(
     *     path="/mob/master_mob/chiller-request/{uuid}",
     *     tags={"ChillerRequest Mob"},
     *     summary="Update a chiller by UUID",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ChillerRequest Mob")),
     *     @OA\Response(response=200, description="Chiller updated successfully"),
     *     @OA\Response(response=404, description="Chiller not found")
     * )
     */
    public function update(UpdateChillerRequestRequest $request, string $uuid): JsonResponse
    {
        // if ($resp = $this->authorizeRoleAccess(_FUNCTION_)) return $resp;
        // dd($uuid);
        try {
            $validatedData = $request->validated();
            $updated = $this->service->updateByUuid($uuid, $validatedData);
            return $this->success(new ChillerRequestResource($updated), 'Chiller updated successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/mob/master_mob/chiller-request/generate-code",
     *     tags={"ChillerRequest Mob"},
     *     summary="Generate unique ChillerRequest code",
     *     @OA\Response(
     *         response=200,
     *         description="Unique ChillerRequest code generated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Unique ChillerRequest code generated successfully"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="osa_code", type="string", example="CR001"))
     *         )
     *     )
     * )
     */
    public function generateCode(): JsonResponse
    {
        try {
            $osa_code = $this->service->generateCode();
            return $this->success(['osa_code' => $osa_code], 'Unique ChillerRequest code generated successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/mob/master_mob/chiller-request/model-numbers",
     *     tags={"ChillerRequest Mob"},
     *     summary="Get model numbers dropdown",
     *     description="Fetch model numbers, dump response into a text file, store it, and return file path",
     *     operationId="getModelNumbers",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Model numbers fetched and file created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="file_path",
     *                 type="string",
     *                 example="/var/www/project/storage/app/model_number/model_numbers_20260102_153000.txt"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Model numbers file generated successfully"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
public function modelNumbers()
    {
        $models = AsModelNumber::query()
            ->select('id', 'code', 'name')
            ->where('status', 1)
            ->orderBy('id', 'asc')
            ->get();
         $textData = json_encode([
        'status' => true,
        'data'   => $models
        ], JSON_PRETTY_PRINT);
        $fileName = 'model_numbers_' . now()->format('Ymd_His') . '.txt';
        Storage::disk('public')->put('model_number/' . $fileName, $textData);
        return response()->json([
            'status'    => true,
            'file_path' => 'storage/model_number/' . $fileName
        ], 200);
    }
   
}
