<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\StoreRolePermissionRequest;
use App\Http\Requests\V1\Settings\Web\StoreRoleRequest;
use App\Http\Requests\V1\Settings\Web\UpdateRoleRequest;
use App\Http\Resources\V1\Settings\Web\RoleResource;
use App\Services\V1\Settings\Web\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Throwable;

/**
 * @OA\Schema(
 *     schema="Role",
 *     type="object",
 *     title="Role",
 *     description="Schema for Role",
 *     @OA\Property(property="name", type="string", example="Admin"),
 *     @OA\Property(property="guard_name", type="string", example="api")
 * )
 */
class RoleController extends Controller
{
    use ApiResponse;

    protected RoleService $service;

    public function __construct(RoleService $service)
    {
        $this->service = $service;
    }


    /**
     * @OA\Get(
     *     path="/api/settings/roles/list",
     *     tags={"Role"},
     *     summary="Get all roles",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of roles",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Records fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Role")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 50);
        $filters = $request->query('filters', []);
        $roles = $this->service->listRoles($perPage, $filters);
        return ResponseHelper::paginatedResponse(
            'Roles fetched successfully',
            RoleResource::class,
            $roles
        );
    }


    public function getDropdownRole(Request $request): JsonResponse
    {
        $roles = $this->service->getDropdownRole();

        return response()->json([
            'status' => true,
            'message' => 'Roles fetched successfully',
            'data' => $roles
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/settings/roles/add",
     *     tags={"Role"},
     *     summary="Create a new role",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name", "guard_name"},
     *             @OA\Property(property="name", type="string", example="Admin"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(
     *                 property="labels",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1),
     *                 description="Array of label IDs for this role, stored as comma-separated in DB"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Role created successfully"),
     *     @OA\Response(response=400, description="Invalid input")
     * )
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->service->createRole($request->validated());
        return $this->success(new RoleResource($role), 'Role created successfully', 200);
    }



    /**
     * @OA\Post(
     *     path="/api/settings/roles/assign-permissions/{id}",
     *     operationId="assignPermissionsWithMenu",
     *     tags={"Role"},
     *     summary="Assign multiple permissions to a role with menus and submenus",
     *     description="Assigns permissions to a role and maps each permission to specific menu and submenu IDs.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID of the role"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"permissions"},
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 description="List of permissions and their menus/submenus for the role",
     *                 @OA\Items(
     *                     @OA\Property(property="permission_id", type="integer", example=1, description="Permission ID"),
     *                     @OA\Property(
     *                         property="menus",
     *                         type="array",
     *                         nullable=true,
     *                         @OA\Items(
     *                             @OA\Property(property="menu_id", type="integer", example=1, description="Menu ID"),
     *                             @OA\Property(property="submenu_id", type="integer", example=5, description="Submenu ID")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permissions assigned successfully with menus/submenus.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation or processing error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to assign permissions")
     *         )
     *     )
     * )
     */
    public function assignPermissionsWithMenu(StoreRolePermissionRequest $request, int $id): JsonResponse
    {
        $response = $this->service->assignPermissionsWithMenu($id, $request->validated());

        return response()->json($response, $response['status'] ? 200 : 400);
    }

    /**
     * @OA\Put(
     *     path="/api/settings/roles/permissions/{id}",
     *     operationId="updateRolePermissions",
     *     tags={"Role"},
     *     summary="Update permissions for a particular role",
     *     description="Updates permissions for a role, including menu and submenu assignments.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=39),
     *         description="ID of the role to update"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"permissions"},
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 description="List of permissions and their menus/submenus for the role",
     *                 @OA\Items(
     *                     @OA\Property(property="permission_id", type="integer", example=1, description="Permission ID"),
     *                     @OA\Property(
     *                         property="menus",
     *                         type="array",
     *                         nullable=true,
     *                         @OA\Items(
     *                             @OA\Property(property="menu_id", type="integer", example=1, description="Menu ID"),
     *                             @OA\Property(property="submenu_id", type="integer", example=5, description="Submenu ID")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role permissions updated successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation or processing error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to update role permissions.")
     *         )
     *     )
     * )
     */
    public function updateRolePermissions(StoreRolePermissionRequest $request, int $role_id): JsonResponse
    {
        $response = $this->service->updatePermissionsForRole($role_id, $request->validated());

        return response()->json($response, $response['status'] ? 200 : 400);
    }


    /**
     * @OA\Get(
     *     path="/api/settings/roles/{id}",
     *     tags={"Role"},
     *     summary="Get a single role by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",              
     *         in="path",
     *         required=true,
     *         description="ID of the role to fetch",
     *         @OA\Schema(type="integer", example=2) 
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role details",
     *         @OA\JsonContent(ref="#/components/schemas/Role")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found"
     *     )
     * )
     */
    public function show($id): JsonResponse
    {
        try {
            // Convert ID to integer to avoid type errors
            $roleId = (int) $id;

            // Fetch role with nested relationships
            $role = $this->service->findRole($roleId);

            return $this->success(
                new RoleResource($role),
                'Role fetched successfully',
                200
            );
        } catch (Throwable $e) {
            return $this->fail($e->getMessage(), 404);
        }
    }

    public function getAllData(): JsonResponse
    {
        try {
            $role = $this->service->getAllData();

            return $this->success(
                new RoleResource($role),
                'Role fetched successfully',
                200
            );
        } catch (Throwable $e) {
            // dd($e);
            return $this->fail($e->getMessage(), 404);
        }
    }


    /**
     * @OA\Put(
     *     path="/api/settings/roles/{id}",
     *     tags={"Role"},
     *     summary="Update an existing role",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID to update",
     *         required=true,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="Manager"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(
     *                 property="labels",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1),
     *                 description="Array of label IDs for this role, stored as comma-separated in DB"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Role updated successfully"),
     *     @OA\Response(response=404, description="Role not found"),
     *     @OA\Response(response=400, description="Invalid input")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $role = $this->service->updateRole($id, $request->all());
            return $this->success(new RoleResource($role), 'Role updated successfully', 200);
        } catch (Throwable $e) {
            return $this->fail($e->getMessage(), 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/settings/roles/{id}",
     *     tags={"Role"},
     *     summary="Delete a role by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="role", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Role deleted successfully"),
     *     @OA\Response(response=404, description="Role not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $role = $this->service->findRole($id);
            $this->service->deleteRole($role);
            return $this->success(null, 'Role deleted successfully', 200);
        } catch (Throwable $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    // /**
    //  * @OA\Get(
    //  *     path="/web/setting/roles/generate-role-code",
    //  *     tags={"Role"},
    //  *     summary="Generate a unique role code",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Unique role code generated successfully",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status", type="string", example="success"),
    //  *             @OA\Property(property="code", type="integer", example=200),
    //  *             @OA\Property(property="message", type="string", example="Unique role code generated successfully"),
    //  *             @OA\Property(property="data", type="object",
    //  *                 @OA\Property(property="role_code", type="string", example="ROLE001")
    //  *             )
    //  *         )
    //  *     )
    //  * )
    //  */
    // public function generateRoleCode(): JsonResponse
    // {
    //     try {
    //         $roleCode = $this->service->generateRoleCode();
    //         return $this->success(['role_code' => $roleCode], 'Unique role code generated successfully', 200);
    //     } catch (Throwable $e) {
    //         return $this->fail($e->getMessage(), 500);

    // public function destroy(int $role): JsonResponse
    // {
    //     dd($role);
    //     try {
    //         $this->service->deleteRole($role);
    //         return $this->success(null, 'Role deleted successfully', 200);
    //     } catch (Throwable $e) {
    //         return $this->fail($e->getMessage(), 404);

    //     }
    // }
}
