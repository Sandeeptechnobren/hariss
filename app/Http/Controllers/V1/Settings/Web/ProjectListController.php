<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Settings\Web\ProjectListResource;
use App\Services\V1\Settings\Web\ProjectListService;
use Illuminate\Http\JsonResponse;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use App\Http\Requests\V1\Settings\Web\ProjectListRequest;

/**
 * @OA\Schema(
 *     schema="ProjectList",
 *     type="object",
 *     required={"name", "salesman_type_id", "status", "created_user"},
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="uuid", type="string", format="uuid", example="b3e3c102-4f5e-4dcd-b7d9-437a8d5463c4"),
 *     @OA\Property(property="osa_code", type="string", example="OSA00001"),
 *     @OA\Property(property="name", type="string", example="Project Alpha"),
 *     @OA\Property(property="salesman_type_id", type="integer", example=2),
 *     @OA\Property(property="status", type="boolean", example=true),
 *     @OA\Property(property="created_user", type="string", example="Amit Pathak"),
 *     @OA\Property(property="updated_user", type="string", nullable=true, example="Admin"),
 *     @OA\Property(property="deleted_user", type="string", nullable=true, example=null),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-11-07 10:30:00"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-11-07 11:15:00"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
 * )
 */
class ProjectListController extends Controller
{
    protected ProjectListService $service;

    public function __construct(ProjectListService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/projects-list",
     *     summary="Get all projects with optional filters",
     *     description="Fetch all projects with pagination and optional filtering by salesman_type_id and status.",
     *     tags={"Project List"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="salesman_type_id",
     *         in="query",
     *         required=false,
     *         description="Filter projects by salesman_type_id",
     *         @OA\Schema(type="integer", example=36)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of projects fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/ProjectList")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing token"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $filters  = request()->only(['salesman_type_id', 'status']);
        $perPage  = request()->get('per_page', 50);
        $dropdown = request()->boolean('dropdown', false);

        $projects = $this->service->getAll(
            $filters,
            $perPage,
            $dropdown
        );

        // 🔹 DROPDOWN RESPONSE
        if ($dropdown) {
            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Salesman Project List fetched successfully',
                'data'    => $projects
            ]);
        }

        // 🔹 NORMAL PAGINATED RESPONSE (EXISTING)
        return ResponseHelper::paginatedResponse(
            'Salesman Project List fetch successfully',
            ProjectListResource::class,
            $projects
        );
    }

    // public function index(): JsonResponse
    // {
    //     $filters = request()->only(['salesman_type_id', 'status']);
    //     $perPage = request()->get('per_page', 50);

    //     $projects = $this->service->getAll($filters, $perPage);
    //     return ResponseHelper::paginatedResponse(
    //         'Salesman Project List fetch successfully',
    //         ProjectListResource::class,
    //         $projects
    //     );
    //     // return response()->json(['status' => 'success', 'data' => $projects]);
    // }


    /**
     * @OA\Post(
     *     path="/api/settings/projects-list/create",
     *     summary="Create a new project",
     *     tags={"Project List"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","salesman_type_id","status","created_user"},
     *             @OA\Property(property="name", type="string", example="Demo Project"),
     *             @OA\Property(property="salesman_type_id", type="integer", example=2),
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="created_user", type="string", example="Amit Pathak")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Project created successfully", @OA\JsonContent(ref="#/components/schemas/ProjectList")),
     *     @OA\Response(response=400, description="Invalid input")
     * )
     */
    public function store(ProjectListRequest $request): JsonResponse
    {
        $project = $this->service->create($request->validated());
        return response()->json([
            'status' => 'success',
            'message' => 'Project created successfully',
            'data' => $project
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/settings/projects-list/{uuid}",
     *     summary="Get project by UUID",
     *     tags={"Project List"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, description="UUID of the project", @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Project details fetched successfully", @OA\JsonContent(ref="#/components/schemas/ProjectList")),
     *     @OA\Response(response=404, description="Project not found")
     * )
     */
    public function show($uuid): JsonResponse
    {
        $project = $this->service->getByUuid($uuid);
        return response()->json(['status' => 'success', 'data' => $project]);
    }

    /**
     * @OA\Put(
     *     path="/api/settings/projects-list/{uuid}",
     *     summary="Update project details",
     *     tags={"Project List"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, description="UUID of the project", @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Project"),
     *             @OA\Property(property="salesman_type_id", type="integer", example=3),
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="updated_user", type="string", example="Admin")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Project updated successfully", @OA\JsonContent(ref="#/components/schemas/ProjectList")),
     *     @OA\Response(response=404, description="Project not found")
     * )
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        $data = $request->validate([
            // write validation rules here
            'name' => 'required|string',
            'salesman_type_id' => 'nullable|integer|exists:salesman_type,id',
            'status' => 'required|integer',
        ]);

        $project = $this->service->update($uuid, $data);

        return response()->json([
            'status' => 'success',
            'message' => 'Project updated successfully',
            'data' => $project
        ]);
    }

    // /**
    //  * @OA\Delete(
    //  *     path="/api/settings/projects-list/{uuid}/delete",
    //  *     summary="Delete a project",
    //  *     tags={"Project List"},
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(name="uuid", in="path", required=true, description="UUID of the project", @OA\Schema(type="string", format="uuid")),
    //  *     @OA\Response(response=200, description="Project deleted successfully"),
    //  *     @OA\Response(response=404, description="Project not found")
    //  * )
    //  */
    // public function destroy($uuid): JsonResponse
    // {
    //     $this->service->delete($uuid);
    //     return response()->json(['status' => 'success', 'message' => 'Project deleted successfully']);
    // }
}
