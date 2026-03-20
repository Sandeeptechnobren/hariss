<?php
namespace App\Http\Controllers\V1\Assets\Mob;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Assets\Mob\IRResource;
use App\Services\V1\Assets\Mob\IRService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IRController extends Controller
{
        protected IRService $service;

    public function __construct(IRService $service)
    {
        $this->service = $service;
    }
/**
 * @OA\Get(
 *     path="/mob/technician_mob/ir-details/ir-list",
 *     tags={"IR Mob"},
 *     summary="Get IR details by Technician",
 *     description="Returns all IR records for a given technician",
 *
 *     @OA\Parameter(
 *         name="technician_id",
 *         in="query",
 *         required=true,
 *         description="Technician ID (salesman_id)",
 *         @OA\Schema(type="integer", example=5)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="IR details fetched successfully",
 *         @OA\JsonContent(
 *             type="object",
 *
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="IR details fetched successfully"),
 *
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=15),
 *                     @OA\Property(property="uuid", type="string", example="a1b2c3d4"),
 *                     @OA\Property(property="osa_code", type="string", example="OSA123"),
 *                     @OA\Property(property="schedule_date", type="string", format="date", example="2026-02-17"),
 *                     @OA\Property(property="salesman_id", type="integer", example=5),
 *                     @OA\Property(property="status", type="integer", example=2)
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     )
 * )
 */
public function index(Request $request): JsonResponse
{
    $request->validate([
        'technician_id' => 'required|integer|exists:tbl_ir_headers,salesman_id'
    ]);
    $technicianId = $request->technician_id;
    $records = $this->service->getAll($technicianId);
    $data = IRResource::collection($records)->response()->getData(true);
    $fileName = 'ir_details_' . $technicianId . '_' . time() . '.txt';
    $filePath = 'ir_notification/' . $fileName;
    Storage::disk('public')->put($filePath, json_encode($data, JSON_PRETTY_PRINT));
    return response()->json([
        'status' => true,
        'message' => 'IR details fetched successfully',
        'file_path' => Storage::url($filePath),
    ], 200);
}
/**
 * @OA\Post(
 *      path="/mob/technician_mob/ir-details/update-status",
 *     tags={"IR Mob"},
 *     summary="Update IR and related IRO status",
 *     description="Updates IR status and its related IRO status. If status is 4 (Scheduled), schedule_date is required.",
 *     operationId="updateIRStatus",
 *     
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"ir_id","status"},
 *             
 *             @OA\Property(
 *                 property="ir_id",
 *                 type="integer",
 *                 example=5,
 *                 description="IR Header ID"
 *             ),
 *             
 *             @OA\Property(
 *                 property="status",
 *                 type="integer",
 *                 example=4,
 *                 description="Status value (4 = Scheduled)"
 *             ),
 *             
 *             @OA\Property(
 *                 property="schedule_date",
 *                 type="string",
 *                 format="date",
 *                 example="2026-02-15",
 *                 description="Required when status is 4"
 *             )
 *         )
 *     ),
 *     
 *     @OA\Response(
 *         response=200,
 *         description="Status updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="IR and related IRO status updated successfully")
 *         )
 *     ),
 *     
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     )
 * )
 */
  public function updateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ir_id' => 'required|exists:tbl_ir_headers,id',
            'status' => 'required|integer|in:2,3,4',
            'schedule_date' => 'required_if:status,4|nullable|date'
        ]);
        $this->service->updateStatusByIrId($validated);
        return response()->json([
            'status'  => true,
            'message' => 'IR request is updated successfully',
            'file_path' => $result['file_path'] ?? null
        ]);
    }
}