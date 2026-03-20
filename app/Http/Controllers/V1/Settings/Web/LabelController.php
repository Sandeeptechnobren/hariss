<?php
// app/Http/Controllers/V1/Settings/Web/LabelController.php
namespace App\Http\Controllers\V1\Settings\Web;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\LabelRequest;
use App\Http\Resources\V1\Settings\Web\LabelResource;
use App\Services\V1\Settings\Web\LabelService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * @OA\Schema(
 *     schema="Label",
 *     type="object",
 *     title="Label",
 *     description="Schema for Label",
 *     @OA\Property(property="name", type="string", example="Company"),
 *     @OA\Property(property="status", type="integer", example=1)
 * )
 */
class LabelController extends Controller
{
    protected LabelService $service;

    public function __construct(LabelService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/labels/list",
     *     tags={"Label"},
     *     summary="Get all labels",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of labels",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Labels fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Label")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 50);
        $filters = $request->query('filters', []);
        $labels = $this->service->listLabels($perPage, $filters);

        return ResponseHelper::paginatedResponse(
            'Labels fetched successfully',
            LabelResource::class,
            $labels
        );
    }

    /**
     * @OA\Post(
     *     path="/api/settings/labels/add",
     *     tags={"Label"},
     *     summary="Create a new label",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Label")
     *     ),
     *     @OA\Response(response=200, description="Label created successfully"),
     *     @OA\Response(response=400, description="Invalid input")
     * )
     */
    public function store(LabelRequest $request): JsonResponse
    {
        try {
            $label = $this->service->create($request->validated());
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Label created successfully',
                'data' => new LabelResource($label)
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/settings/labels/generate-code",
     *     tags={"Label"},
     *     summary="Generate a unique menu code",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Generated menu code",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="code", type="string", example="LB001")
     *             )
     *         )
     *     )
     * )
     */
    public function generateCode(): JsonResponse
    {
        $code = $this->service->generateCode();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => [
                'code' => $code
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/settings/labels/{id}",
     *     tags={"Label"},
     *     summary="Get a single label by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Label details", @OA\JsonContent(ref="#/components/schemas/Label")),
     *     @OA\Response(response=404, description="Label not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $label = $this->service->getById($id);
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Label fetched successfully',
                'data' => new LabelResource($label)
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/settings/labels/{id}",
     *     tags={"Label"},
     *     summary="Update an existing label",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Label")),
     *     @OA\Response(response=200, description="Label updated successfully"),
     *     @OA\Response(response=400, description="Invalid input"),
     *     @OA\Response(response=404, description="Label not found")
     * )
     */
    public function update(LabelRequest $request, int $id): JsonResponse
    {
        try {
            $label = $this->service->getById($id);
            $label = $this->service->update($label, $request->validated());
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Label updated successfully',
                'data' => new LabelResource($label)
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // /**
    //  * @OA\Delete(
    //  *     path="/api/settings/labels/{id}",
    //  *     tags={"Label"},
    //  *     summary="Delete a label by ID",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
    //  *     @OA\Response(response=200, description="Label deleted successfully"),
    //  *     @OA\Response(response=404, description="Label not found")
    //  * )
    //  */
    // public function destroy(int $id): JsonResponse
    // {
    //     try {
    //         $label = $this->service->getById($id);
    //         $this->service->delete($label);
    //         return response()->json([
    //             'status' => 'success',
    //             'code' => 200,
    //             'message' => 'Label deleted successfully'
    //         ]);
    //     } catch (Throwable $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'code' => 400,
    //             'message' => $e->getMessage()
    //         ], 400);
    //     }
    // }
}
