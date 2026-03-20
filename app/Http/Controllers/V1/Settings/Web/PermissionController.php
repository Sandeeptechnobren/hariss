<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\StorePermissionRequest;
use App\Http\Requests\V1\Settings\Web\UpdatePermissionRequest;
use App\Http\Resources\V1\Settings\Web\PermissionResource;
use App\Services\V1\Settings\Web\PermissionService;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * @OA\Schema(
 *     schema="Permission",
 *     type="object",
 *     title="Permission",
 *     description="Schema for Permission",
 *     @OA\Property(property="name", type="string", example="create post"),
 *     @OA\Property(property="guard_name", type="string", example="api")
 * )
 *
 * @OA\Tag(
 *     name="Permissions",
 *     description="API Endpoints for managing permissions"
 * )
 */
class PermissionController extends Controller
{
    use ApiResponse;

    protected PermissionService $service;

    public function __construct(PermissionService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/permissions/list",
     *     tags={"Permissions"},
     *     summary="List all permissions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Permissions fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Records fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Permission")
     *             )
     *         )
     *     )
     * )
     */
public function index(): JsonResponse
    {
        try {
            $permissions = $this->service->listPermissions();
            return ResponseHelper::paginatedResponse(
                'Role fetched successfully',
                PermissionResource::class,
                $permissions
            );
        } catch (Throwable $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/settings/permissions/add",
     *     tags={"Permissions"},
     *     summary="Create a new permission",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Permission")),
     *     @OA\Response(response=200, description="Permission created successfully")
     * )
     */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        try {
            $permission = $this->service->createPermission($request->validated());
            return $this->success(new PermissionResource($permission), 'Permission created successfully', 200);
        } catch (Throwable $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/settings/permissions/{id}",
     *     tags={"Permissions"},
     *     summary="Get a specific permission by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the permission to retrieve",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Permission")
     *     ),
     *     @OA\Response(response=404, description="Permission not found")
     * )
     */
    public function show(int $permission): JsonResponse
    {
        try {
            $permission = $this->service->findPermission($permission);
            return $this->success(new PermissionResource($permission), 'Permission fetched successfully', 200);
        } catch (Throwable $e) {
            return $this->fail($e->getMessage(), 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/settings/permissions/{id}",
     *     tags={"Permissions"},
     *     summary="Update a permission by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the permission to update",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "guard_name"},
     *             @OA\Property(property="name", type="string", example="Edit Posts"),
     *             @OA\Property(property="guard_name", type="string", example="api")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Permission updated successfully"),
     *     @OA\Response(response=404, description="Permission not found")
     * )
     */
    public function update(UpdatePermissionRequest $request, int $permission): JsonResponse
    {
        try {
            $permission = $this->service->findPermission($permission);
            $permission = $this->service->updatePermission($permission, $request->validated());
            return $this->success(new PermissionResource($permission), 'Permission updated successfully', 200);
        } catch (Throwable $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/settings/permissions/{id}",
     *     tags={"Permissions"},
     *     summary="Delete a permission by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the permission to delete",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Response(response=200, description="Permission deleted successfully"),
     *     @OA\Response(response=404, description="Permission not found")
     * )
     */
    public function destroy(int $permission): JsonResponse
    {
        try {
            $permission = $this->service->findPermission($permission);
            $this->service->deletePermission($permission);
            return $this->success(null, 'Permission deleted successfully', 200);
        } catch (Throwable $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }
    public function getBySubmenu(Request $request)
    {
        $request->validate([
            'submenu_id' => 'required|integer'
        ]);

        // 1. Get logged-in user from token
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'code'    => 401,
                'message' => 'Unauthorized',
                'data'    => null
            ], 401);
        }

        $submenuId = $request->submenu_id;
        $roleId   = $user->role; // users.role = role_has_permissions.role_id

        // 2. Fetch permissions based on role + submenu
        $permissions = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('role_has_permissions.role_id', $roleId)
            ->where('role_has_permissions.submenu_id', $submenuId)
            ->select(
                'permissions.id',
                'permissions.name'
            )
            ->distinct()
            ->get();

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Permissions fetched successfully',
            'data'    => $permissions
        ], 200);
    }
    }

