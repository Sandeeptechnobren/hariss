<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Settings\Web\SubMenuResource;
use App\Services\V1\Settings\Web\SubMenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

/**
 * @OA\Schema(
 *     schema="SubMenu",
 *     type="object",
 *     title="SubMenu",
 *     description="Schema for SubMenu object",
 *     @OA\Property(property="name", type="string", example="Reports"),
 *     @OA\Property(property="menu_id", type="integer", example=1),
 *     @OA\Property(property="parent_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="url", type="string", example="/reports"),
 *     @OA\Property(property="display_order", type="integer", example=1),
 *     @OA\Property(property="action_type", type="integer", example=1),
 *     @OA\Property(property="is_visible", type="integer", enum={0,1}, example=1),
 *     @OA\Property(property="status", type="integer", enum={0,1}, example=1),
 * )
 */
class SubMenuController extends Controller
{
    use ApiResponse;

    protected SubMenuService $service;

    public function __construct(SubMenuService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/submenu/list",
     *     tags={"SubMenu"},
     *     summary="Get all submenus with pagination and optional filters",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="sub_menu_name", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="List of submenus",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="SubMenus fetched successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/SubMenu")),
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
        $perPage = $request->get('limit', 150);
        $filters = $request->only(['name', 'url']);
        $submenus = $this->service->getAll($filters, $perPage);

        // dd(SubMenuResource::collection($submenus->items()));
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Service types fetched successfully',
            'data' => SubMenuResource::collection($submenus->items()),
            'pagination' => [
                'page' => $submenus->currentPage(),
                'limit' => $submenus->perPage(),
                'totalPages' => $submenus->lastPage(),
                'totalRecords' => $submenus->total(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/settings/submenu/{uuid}",
     *     tags={"SubMenu"},
     *     summary="Get a single submenu by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="SubMenu details", @OA\JsonContent(ref="#/components/schemas/SubMenu")),
     *     @OA\Response(response=404, description="SubMenu not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        $submenu = $this->service->findByUuid($uuid);
        if (!$submenu) {
            return $this->fail('SubMenu not found', 404);
        }
        return $this->success(new SubMenuResource($submenu), 'SubMenu fetched successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/settings/submenu/generate-code",
     *     tags={"SubMenu"},
     *     summary="Generate unique SubMenu code",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Unique SubMenu code generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Unique SubMenu code generated successfully"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="osa_code", type="string", example="SBM001"))
     *         )
     *     )
     * )
     */
    public function generateCode(): JsonResponse
    {
        try {
            $osa_code = $this->service->generateCode();
            return $this->success(['osa_code' => $osa_code], 'Unique SubMenu code generated successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/settings/submenu/add",
     *     tags={"SubMenu"},
     *     summary="Create a new submenu",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/SubMenu")),
     *     @OA\Response(response=201, description="SubMenu created successfully")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $submenu = $this->service->create($request->all());
        return $this->success(new SubMenuResource($submenu), 'SubMenu created successfully', 201);
    }

    /**
     * @OA\Put(
     *     path="/api/settings/submenu/{uuid}",
     *     tags={"SubMenu"},
     *     summary="Update a submenu by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/SubMenu")),
     *     @OA\Response(response=200, description="SubMenu updated successfully"),
     *     @OA\Response(response=404, description="SubMenu not found")
     * )
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        try {
            $updated = $this->service->updateByUuid($uuid, $request->all());
            return $this->success(new SubMenuResource($updated), 'SubMenu updated successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/settings/submenu/{uuid}",
     *     tags={"SubMenu"},
     *     summary="Delete a submenu by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="SubMenu deleted successfully"),
     *     @OA\Response(response=404, description="SubMenu not found")
     * )
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->service->deleteByUuid($uuid);
            return $this->success(null, 'SubMenu deleted successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/settings/submenu/global_search",
     *     tags={"SubMenu"},
     *     summary="Global search submenu with pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer"),
     *         description="Number of records per page (default: 10)"
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         description="Search keyword for areas"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="SubMenu fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="SubMenu fetched successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items()),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=5),
     *                 @OA\Property(property="totalRecords", type="integer", example=50),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to search areas"
     *     )
     * )
     */
    public function global_search(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);
            $searchTerm = $request->get('search');
            $subMenu = $this->service->globalSearch($perPage, $searchTerm);

            return response()->json([
                "status" => "success",
                "code" => 200,
                "message" => "SubMenu fetched successfully",
                "data" => $subMenu->items(),
                "pagination" => [
                    "page" => $subMenu->currentPage(),
                    "limit" => $subMenu->perPage(),
                    "totalPages" => $subMenu->lastPage(),
                    "totalRecords" => $subMenu->total(),
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
