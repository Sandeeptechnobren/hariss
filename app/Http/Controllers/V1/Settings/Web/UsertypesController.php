<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\UserTypesRequest;
use App\Services\V1\Settings\Web\UserTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

// /**
//  * @OA\Schema(
//  *     schema="UserType",
//  *     type="object",
//  *     @OA\Property(property="id", type="integer", example=1),
//  *     @OA\Property(property="code", type="string", example="A1"),
//  *     @OA\Property(property="name", type="string", example="Admin"),
//  *     @OA\Property(property="status", type="integer", example=1)
//  * )
//  */
class UsertypesController extends Controller
{
    protected $userTypeService;

    public function __construct(UserTypeService $service)
    {
        $this->userTypeService = $service;
    }

//     /**
//      * @OA\Get(
//      *     path="/api/settings/user-type/list",
//      *     summary="Get all user types",
//      *     tags={"User Types"},
//      *     security={{"bearerAuth":{}}},
//      *     @OA\Response(
//      *         response=200,
//      *         description="List of user types",
//      *         @OA\JsonContent(
//      *             @OA\Property(property="success", type="boolean", example=true),
//      *             @OA\Property(
//      *                 property="data",
//      *                 type="array",
//      *                 @OA\Items(ref="#/components/schemas/UserType")
//      *             )
//      *         )
//      *     ),
//      *     @OA\Response(response=401, description="Unauthorized")
//      * )
//      */
// public function index(): JsonResponse
//     {
//         $perPage = request()->get('per_page', 10);
//         return $this->userTypeService->getAll($perPage);
//     }

//     /**
//      * @OA\Get(
//      *     path="/api/settings/user-type/{id}",
//      *     summary="Get a user type by ID",
//      *     tags={"User Types"},
//      *     security={{"bearerAuth":{}}},
//      *     @OA\Parameter(
//      *         name="id",
//      *         in="path",
//      *         description="User type ID",
//      *         required=true,
//      *         @OA\Schema(type="integer")
//      *     ),
//      *     @OA\Response(
//      *         response=200,
//      *         description="User type details",
//      *         @OA\JsonContent(
//      *             @OA\Property(property="success", type="boolean", example=true),
//      *             @OA\Property(property="data", ref="#/components/schemas/UserType")
//      *         )
//      *     ),
//      *     @OA\Response(response=404, description="User type not found"),
//      *     @OA\Response(response=401, description="Unauthorized")
//      * )
//      */
//     public function show($id): JsonResponse
//     {
//         $data = $this->userTypeService->getById($id);
//         return response()->json(['success' => true, 'data' => $data]);
//     }

//     /**
//      * @OA\Post(
//      *     path="/api/settings/user-type/create",
//      *     summary="Create a new user type",
//      *     tags={"User Types"},
//      *     security={{"bearerAuth":{}}},
//      *     @OA\RequestBody(
//      *         required=true,
//      *         @OA\JsonContent(
//      *             required={"code","name","status"},
//      *             @OA\Property(property="code", type="string", example="A1"),
//      *             @OA\Property(property="name", type="string", example="Admin"),
//      *             @OA\Property(property="status", type="integer", example=1)
//      *         )
//      *     ),
//      *     @OA\Response(
//      *         response=201,
//      *         description="User Type created successfully",
//      *         @OA\JsonContent(
//      *             @OA\Property(property="success", type="boolean", example=true),
//      *             @OA\Property(property="message", type="string", example="User Type created successfully"),
//      *             @OA\Property(property="data", ref="#/components/schemas/UserType")
//      *         )
//      *     ),
//      *     @OA\Response(response=422, description="Validation Error"),
//      *     @OA\Response(response=401, description="Unauthorized")
//      * )
//      */
//     public function store(UserTypesRequest $request): JsonResponse
//     {
//         $userId = Auth::id();
//         $data = $this->userTypeService->create($request->validated(), $userId);
//         return response()->json([
//             'success' => true,
//             'message' => 'User Type created successfully',
//             'data'    => $data
//         ], 201);
//     }

//     /**
//      * @OA\Put(
//      *     path="/api/settings/user-type/{id}",
//      *     summary="Update a user type",
//      *     tags={"User Types"},
//      *     security={{"bearerAuth":{}}},
//      *     @OA\Parameter(
//      *         name="id",
//      *         in="path",
//      *         description="User type ID",
//      *         required=true,
//      *         @OA\Schema(type="integer")
//      *     ),
//      *     @OA\RequestBody(
//      *         required=true,
//      *         @OA\JsonContent(
//      *             required={"code","name","status"},
//      *             @OA\Property(property="code", type="string", example="A1"),
//      *             @OA\Property(property="name", type="string", example="Updated Name"),
//      *             @OA\Property(property="status", type="integer", example=1)
//      *         )
//      *     ),
//      *     @OA\Response(
//      *         response=200,
//      *         description="User Type updated successfully",
//      *         @OA\JsonContent(
//      *             @OA\Property(property="success", type="boolean", example=true),
//      *             @OA\Property(property="message", type="string", example="User Type updated successfully"),
//      *             @OA\Property(property="data", ref="#/components/schemas/UserType")
//      *         )
//      *     ),
//      *     @OA\Response(response=404, description="User type not found"),
//      *     @OA\Response(response=422, description="Validation Error"),
//      *     @OA\Response(response=401, description="Unauthorized")
//      * )
//      */
//     public function update(UserTypesRequest $request, $id): JsonResponse
//     {
//         $userId = Auth::id();
//         $data = $this->userTypeService->update($id, $request->validated(), $userId);
//         return response()->json([
//             'success' => true,
//             'message' => 'User Type updated successfully',
//             'data'    => $data
//         ]);
//     }

//     /**
//      * @OA\Delete(
//      *     path="/api/settings/user-type/{id}",
//      *     summary="Delete a user type",
//      *     tags={"User Types"},
//      *     security={{"bearerAuth":{}}},
//      *     @OA\Parameter(
//      *         name="id",
//      *         in="path",
//      *         description="User type ID",
//      *         required=true,
//      *         @OA\Schema(type="integer")
//      *     ),
//      *     @OA\Response(
//      *         response=200,
//      *         description="User Type deleted successfully",
//      *         @OA\JsonContent(
//      *             @OA\Property(property="success", type="boolean", example=true),
//      *             @OA\Property(property="message", type="string", example="User Type deleted successfully")
//      *         )
//      *     ),
//      *     @OA\Response(response=404, description="User type not found"),
//      *     @OA\Response(response=401, description="Unauthorized")
//      * )
//      */
//     public function destroy($id): JsonResponse
//     {
//         $this->userTypeService->delete($id);
//         return response()->json(['success' => true, 'message' => 'User Type deleted successfully']);
//     }


//     /**
//  * @OA\Get(
//  *     path="/api/settings/user/global-search",
//  *     summary="Global search for user types",
//  *     description="Search user types by code, name, or status with pagination. If query is empty, all user types are returned.",
//  *     tags={"User"},
//  *     security={{"bearerAuth":{}}},

//  *     @OA\Parameter(
//  *         name="query",
//  *         in="query",
//  *         required=false,
//  *         description="Search keyword for filtering user types by code, name, or status. Leave empty to get all records.",
//  *         @OA\Schema(type="string", example="admin")
//  *     ),
//  *     @OA\Parameter(
//  *         name="per_page",
//  *         in="query",
//  *         required=false,
//  *         description="Number of records per page (default 50)",
//  *         @OA\Schema(type="integer", example=10)
//  *     ),

//  *     @OA\Response(
//  *         response=200,
//  *         description="User types fetched successfully",
//  *         @OA\JsonContent(
//  *             @OA\Property(property="status", type="string", example="success"),
//  *             @OA\Property(property="message", type="string", example="User types fetched successfully"),
//  *             @OA\Property(property="data", type="array",
//  *                 @OA\Items(
//  *                     @OA\Property(property="created_by", type="object",
//  *                     ),
//  *                     @OA\Property(property="updated_by", type="object",
//  *                         
//  *                     )
//  *                 )
//  *             ),
//  *             @OA\Property(property="pagination", type="object",
//  *                 @OA\Property(property="page", type="integer", example=1),
//  *                 @OA\Property(property="limit", type="integer", example=10),
//  *                 @OA\Property(property="totalPages", type="integer", example=3),
//  *                 @OA\Property(property="totalRecords", type="integer", example=25)
//  *             )
//  *         )
//  *     ),

//  *     @OA\Response(
//  *         response=500,
//  *         description="Failed to fetch user types",
//  *         @OA\JsonContent(
//  *             @OA\Property(property="status", type="string", example="error"),
//  *             @OA\Property(property="message", type="string", example="Failed to fetch user types. Please try again.")
//  *         )
//  *     )
//  * )
//  */

// public function globalSearch(): JsonResponse
// {
//     $search = request()->get('query', '');
//     $perPage = request()->get('per_page', 50);
//     return $this->userTypeService->globalSearch($search, $perPage);
// }

}
