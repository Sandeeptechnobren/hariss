<?php

namespace App\Http\Controllers\V1\Merchendisher\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Merchendisher\Mob\ComplaintFeedbackRequest;
use App\Http\Resources\V1\Merchendisher\Mob\ComplaintFeedbackResource;
use App\Services\V1\Merchendisher\Mob\ComplaintFeedbackService;
use App\Models\ComplaintFeedback;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class ComplaintFeedbackController extends Controller
{
    protected ComplaintFeedbackService $service;

    public function __construct(ComplaintFeedbackService $service)
    {
        $this->service = $service;
    }
    
 /**
 * @OA\Post(
 *     path="/mob/merchendisher_mob/complaint-feedback/create",
 *     summary="Create a new complaint (form-data)",
 *     tags={"ComplaintFeedback Mob"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 required={
 *                     "complaint_title",
 *                     "item_id",
 *                     "merchendiser_id",
 *                     "complaint",
 *                     "image[]"
 *                 },
 *                 @OA\Property(property="complaint_title", type="string", example="Damaged packaging"),
 *                 @OA\Property(property="complaint_code", type="string", example="CMP20231010"),
 *                 @OA\Property(property="item_id", type="integer", example=5, description="Must exist in items table"),
 *                 @OA\Property(property="merchendiser_id", type="integer", example=3, description="Must exist in salesmen table"),
 *                 @OA\Property(property="customer_id", type="integer", example=8, description="Must exist in tbl_company_customer table"),
 *                 @OA\Property(property="type", type="string", example="suggestion"),
 *                 @OA\Property(property="complaint", type="string", example="The product packaging was torn when received."),
 *                 
 *                 @OA\Property(
 *                     property="image[]",
 *                     type="array",
 *                     description="Exactly 2 image files",
 *                     minItems=2,
 *                     maxItems=2,
 *                     @OA\Items(
 *                         type="string",
 *                         format="binary"
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Complaint created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="id", type="integer", example=10),
 *             @OA\Property(property="complaint_title", type="string", example="Damaged packaging"),
 *             @OA\Property(property="item_id", type="integer", example=5),
 *             @OA\Property(property="merchendiser_id", type="integer", example=3),
 *             @OA\Property(property="type", type="string", example="Packaging"),
 *             @OA\Property(property="complaint", type="string", example="The product packaging was torn when received."),
 *             @OA\Property(property="uuid", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
 *             @OA\Property(property="complaint_code", type="string", example="CMP20231010"),
 *             @OA\Property(
 *                 property="image",
 *                 type="array",
 *                 @OA\Items(type="string", format="uri", example="/storage/planogram_images/n5VvOIsR3OXuCdthYd4dXqGDWFkHidWsYJ9zrU9i.jpg")
 *             ),
 *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-10T10:00:00Z"),
 *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-10-10T10:00:00Z")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Validation failed for field 'item_id'")
 *         )
 *     )
 * )
 */

public function store(ComplaintFeedbackRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $complaint = $this->service->createComplaint($request->validated());
            DB::commit();
            return response()->json([
                'status'  => true,
                'message' => 'Complaint created successfully',
                'data'    => new ComplaintFeedbackResource($complaint)
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Failed to create complaint',
                'error'   => $e->getMessage() // In production you can hide this
            ], 500);
        }
    }
}