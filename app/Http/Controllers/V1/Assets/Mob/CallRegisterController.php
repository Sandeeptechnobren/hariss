<?php
namespace App\Http\Controllers\V1\Assets\Mob;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Assets\Mob\CallRegisterResource;
use App\Services\V1\Assets\Mob\CallRegisterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CallRegisterController extends Controller
{
        protected CallRegisterService $service;

    public function __construct(CallRegisterService $service)
    {
        $this->service = $service;
    }

/**
 * @OA\Get(
 *     path="/mob/technician_mob/call-registers/pending-bd",
 *     tags={"Pending BD"},
 *     summary="Get Pending BD list by Technician",
 *     description="Returns all pending BD call registers for a specific technician",
 *
 *     @OA\Parameter(
 *         name="technician_id",
 *         in="query",
 *         required=true,
 *         description="Technician ID to fetch pending BD records",
 *         @OA\Schema(
 *             type="integer",
 *             example=5
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Data fetched successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Pending BD fetched successfully"),
 *             @OA\Property(property="technician_id", type="integer", example=5),
 *             @OA\Property(property="total_records", type="integer", example=12),
 *             @OA\Property(
 *                 property="file_path",
 *                 type="string",
 *                 example="storage/pending_BD/pending_bd_tech_5_20260122_120000.txt"
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=422,
 *         description="Validation error (technician_id missing or invalid)"
 *     )
 * )
 */
public function index(Request $request): JsonResponse
{
        $request->validate([
        'technician_id' => 'required|integer|exists:tbl_call_register,technician_id'
    ]);
    $technicianId = $request->technician_id;
    $records = $this->service->getAll($technicianId);
    $dataArray = CallRegisterResource::collection($records)->resolve();
    $textContent = json_encode($dataArray, JSON_PRETTY_PRINT);
    $fileName = 'pending_bd_' . now()->format('Ymd_His') . '.txt';
    $filePath = "pending_BD/{$fileName}";
    Storage::disk('public')->put($filePath, $textContent);
    return response()->json([
        'status' => true,
        'message' => 'Pending BD fetched and saved successfully',
        'file_path' =>  "storage/{$filePath}",
    ], 200);
}
}