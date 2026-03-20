<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\MenuRequest;
use App\Http\Resources\V1\Settings\Web\MenuResource;
use App\Services\V1\Settings\Web\MenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="Menu",
 *     type="object",
 *     required={"name"},
 *     @OA\Property(property="name", type="string", maxLength=255, example="Dashboard"),
 *     @OA\Property(property="icon", type="string", maxLength=100, nullable=true, example="dashboard.png"),
 *     @OA\Property(property="url", type="string", maxLength=255, nullable=true, example="/dashboard"),
 *     @OA\Property(property="display_order", type="integer", nullable=true, example=1),
 *     @OA\Property(property="is_visible", type="integer", enum={0,1}, nullable=true, example=1, description="0 = Hidden, 1 = Visible"),
 *     @OA\Property(property="status", type="integer", enum={0,1}, nullable=true, example=1, description="0 = Inactive, 1 = Active"),
 * )
 */
class MenuController extends Controller
{
    private MenuService $service;

    public function __construct(MenuService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/menus/list",
     *     tags={"Menu"},
     *     summary="List all menus with pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of menus",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Menu")),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="page", type="integer"),
     *                 @OA\Property(property="limit", type="integer"),
     *                 @OA\Property(property="totalPages", type="integer"),
     *                 @OA\Property(property="totalRecords", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('limit', 10);
        $menus = $this->service->all($perPage, $request->all());

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => MenuResource::collection($menus),
            'pagination' => [
                'page' => $menus->currentPage(),
                'limit' => $menus->perPage(),
                'totalPages' => $menus->lastPage(),
                'totalRecords' => $menus->total()
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/settings/menus/add",
     *     tags={"Menu"},
     *     summary="Create a new menu",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Menu")),
     *     @OA\Response(response=201, description="Menu created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(MenuRequest $request): JsonResponse
    {
        $menu = $this->service->create($request->validated());

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => new MenuResource($menu)
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/settings/menus/{uuid}",
     *     tags={"Menu"},
     *     summary="Get menu details by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Menu details", @OA\JsonContent(ref="#/components/schemas/Menu")),
     *     @OA\Response(response=404, description="Menu not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        $menu = $this->service->findByUuid($uuid);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => new MenuResource($menu)
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/settings/menus/update/{uuid}",
     *     tags={"Menu"},
     *     summary="Update menu by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Menu")),
     *     @OA\Response(response=200, description="Menu updated successfully"),
     *     @OA\Response(response=404, description="Menu not found")
     * )
     */
    public function update(MenuRequest $request, string $uuid): JsonResponse
    {
        $updated = $this->service->updateByUuid($uuid, $request->validated());

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => new MenuResource($updated)
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/settings/menus/{uuid}",
     *     tags={"Menu"},
     *     summary="Soft delete a menu by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Menu deleted successfully"),
     *     @OA\Response(response=404, description="Menu not found")
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        $this->service->deleteByUuid($uuid);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Menu deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/settings/menus/generate-code",
     *     tags={"Menu"},
     *     summary="Generate a unique menu code",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Generated menu code",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="code", type="string", example="M001")
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
     *     path="/api/settings/menus/global-search",
     *     tags={"Menu"},
     *     summary="Global search for menus",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of records per page",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term (name, osa_code, url)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of menus",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Menus fetched successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="uuid", type="string"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="osa_code", type="string"),
     *                 @OA\Property(property="icon", type="string"),
     *                 @OA\Property(property="url", type="string"),
     *                 @OA\Property(property="display_order", type="integer"),
     *                 @OA\Property(property="is_visible", type="integer"),
     *                 @OA\Property(property="status", type="integer")
     *             )),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="page", type="integer"),
     *                 @OA\Property(property="limit", type="integer"),
     *                 @OA\Property(property="totalPages", type="integer"),
     *                 @OA\Property(property="totalRecords", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function globalSearch(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $searchTerm = $request->get('search');

            $menus = $this->service->globalSearch($perPage, $searchTerm);

            return response()->json([
                "status" => "success",
                "code" => 200,
                "message" => "Menus fetched successfully",
                "data" => $menus->items(),
                "pagination" => [
                    "page" => $menus->currentPage(),
                    "limit" => $menus->perPage(),
                    "totalPages" => $menus->lastPage(),
                    "totalRecords" => $menus->total(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "code" => 500,
                "message" => $e->getMessage(),
                "data" => null
            ], 500);
        }
    }
}
