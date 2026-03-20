<?php

namespace App\Http\Controllers\V1\Assets\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Assets\Mob\AddChillerRequest;
use App\Services\V1\Assets\Mob\ChillerAddService;
use Illuminate\Http\JsonResponse;

class ChillerAddController extends Controller
{
    protected ChillerAddService $service;

    public function __construct(ChillerAddService $service)
    {
        $this->service = $service;
    }

/**
 * @OA\Post(
 *     path="/mob/master_mob/add-chiller/create",
 *     tags={"Add Chiller Mob"},
 *     summary="Add Chiller Request",
 *     description="Create add chiller request with full details and documents",
 *
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *
 *                 @OA\Property(property="osa_code", type="string", example="CH001"),
 *                 @OA\Property(property="outlet_name", type="string", example="ABC Store"),
 *                 @OA\Property(property="owner_name", type="string", example="Ramesh Kumar"),
 *                 @OA\Property(property="contact_number", type="string", example="9876543210"),
 *                 @OA\Property(property="landmark", type="string", example="Near Bus Stand"),
 *                 @OA\Property(property="outlet_type", type="string", example="Retail"),
 *                 @OA\Property(property="existing_coolers", type="string", example="2"),
 *                 @OA\Property(property="outlet_weekly_sale_volume", type="string", example="5000"),
 *                 @OA\Property(property="display_location", type="string", example="Front"),
 *                 @OA\Property(property="chiller_safty_grill", type="boolean", example=true),
 *
 *                 @OA\Property(property="agent", type="string", example="Agent Name"),
 *                 @OA\Property(property="manager_sales_marketing", type="string", example="Manager Name"),
 *
 *                 @OA\Property(property="national_id", type="string", example="AADHAR"),
 *                 @OA\Property(property="outlet_stamp", type="string", example="Yes"),
 *                 @OA\Property(property="model", type="string", example="LG-500"),
 *                 @OA\Property(property="hil", type="string", example="HIL-REF"),
 *                 @OA\Property(property="ir_reference_no", type="string", example="IR-123"),
 *                 @OA\Property(property="installation_done_by", type="string", example="Company"),
 *
 *                 @OA\Property(property="date_lnitial", type="string", format="date", example="2025-01-10"),
 *                 @OA\Property(property="date_lnitial2", type="string", format="date", example="2025-01-15"),
 *
 *                 @OA\Property(property="contract_attached", type="boolean", example=true),
 *                 @OA\Property(property="machine_number", type="string", example="MC-001"),
 *                 @OA\Property(property="brand", type="string", example="LG"),
 *                 @OA\Property(property="asset_number", type="string", example="AST-7788"),
 *                 @OA\Property(property="lc_letter", type="string", example="Yes"),
 *                 @OA\Property(property="trading_licence", type="string", example="Yes"),
 *                 @OA\Property(property="password_photo", type="string", example="Yes"),
 *                 @OA\Property(property="outlet_address_proof", type="string", example="Yes"),
 *                 @OA\Property(property="chiller_asset_care_manager", type="string", example="Manager Name"),
 *
 *                 @OA\Property(property="national_id_file", type="string", format="binary"),
 *                 @OA\Property(property="password_photo_file", type="string", format="binary"),
 *                 @OA\Property(property="outlet_address_proof_file", type="string", format="binary"),
 *                 @OA\Property(property="trading_licence_file", type="string", format="binary"),
 *                 @OA\Property(property="lc_letter_file", type="string", format="binary"),
 *                 @OA\Property(property="outlet_stamp_file", type="string", format="binary"),
 *                 @OA\Property(property="sign__customer_file", type="string", format="binary"),
 *
 *                 @OA\Property(property="national_id1_file", type="string", format="binary"),
 *                 @OA\Property(property="password_photo1_file", type="string", format="binary"),
 *                 @OA\Property(property="outlet_address_proof1_file", type="string", format="binary"),
 *                 @OA\Property(property="trading_licence1_file", type="string", format="binary"),
 *                 @OA\Property(property="lc_letter1_file", type="string", format="binary"),
 *                 @OA\Property(property="outlet_stamp1_file", type="string", format="binary"),
 *
 *                 @OA\Property(property="sales_marketing_director", type="string", example="Director Name"),
 *                 @OA\Property(property="warehouse_id", type="integer", example=5),
 *                 @OA\Property(property="area_manager", type="string", example="Area Manager"),
 *                 @OA\Property(property="name_contact_of_the_customer", type="string", example="Contact Person"),
 *                 @OA\Property(property="chiller_size_requested", type="string", example="500L"),
 *                 @OA\Property(property="outlet_weekly_sales", type="string", example="8000"),
 *                 @OA\Property(property="stock_share_with_competitor", type="string", example="Pepsi"),
 *                 @OA\Property(property="specify_if_other_type", type="string", example="If any"),
 *                 @OA\Property(property="location", type="string", example="Mumbai"),
 *                 @OA\Property(property="postal_address", type="string", example="Mumbai, India"),
 *                 @OA\Property(property="customer_id", type="integer", example=5),
 *
 *                 @OA\Property(property="sales_excutive", type="string", example="Sales Exec"),
 *                 @OA\Property(property="salesman_id", type="integer", example=12),
 *                 @OA\Property(property="route_id", type="integer", example=3),
 *                 @OA\Property(property="sign_salesman_file", type="string", format="binary"),
 *                 @OA\Property(property="serial_no", type="string", example="SN-9988"),
 *                 @OA\Property(property="fridge_scan_img", type="string", format="binary"),
 *                 @OA\Property(property="fridge_office_id", type="integer", example=2),
 *                 @OA\Property(property="fridge_maanger_id", type="integer", example=4),
 *
 *                 @OA\Property(property="status", type="boolean", example=true),
 *                 @OA\Property(property="request_document_status", type="string", example="pending"),
 *                 @OA\Property(property="agreement_id", type="integer", example=10),
 *                 @OA\Property(property="fridge_status", type="string", example="new"),
 *
 *                 @OA\Property(property="remark", type="string", example="Urgent installation")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(response=200, description="Add chiller request created successfully")
 * )
 */
public function store(AddChillerRequest $request): JsonResponse
    {
        $asset = $this->service->create($request->validated());
        return response()->json([
            'status'  => true,
            'message' => 'Asset created successfully',
            'data'    => $asset
        ]);
    }
}
