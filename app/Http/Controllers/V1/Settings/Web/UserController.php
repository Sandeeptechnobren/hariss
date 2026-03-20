<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Services\V1\Settings\Web\UserService;
use App\Http\Resources\V1\Settings\Web\UserResource;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
/**
 * @OA\Get(
 *     path="/api/settings/user/global-search",
 *     summary="Global search for users",
 *     description="Search users by name, username, email, contact number, role, or status with pagination. If query is empty, all users are returned.",
 *     tags={"User"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(
 *         name="query",
 *         in="query",
 *         required=false,
 *         description="Search keyword for filtering users by name, username, email, contact number, role, or status. Leave empty to get all records.",
 *         @OA\Schema(type="string", example="john")
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         required=false,
 *         description="Number of records per page (default 50)",
 *         @OA\Schema(type="integer", example=10)
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Users fetched successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Users fetched successfully"),
 *             @OA\Property(property="data", type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="John Doe"),
 *                     @OA\Property(property="username", type="string", example="johndoe"),
 *                 )
 *             ),
 *             @OA\Property(property="pagination", type="object",
 *                 @OA\Property(property="page", type="integer", example=1),
 *                 @OA\Property(property="limit", type="integer", example=10),
 *                 @OA\Property(property="totalPages", type="integer", example=3),
 *                 @OA\Property(property="totalRecords", type="integer", example=25)
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Failed to fetch users",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="message", type="string", example="Failed to fetch users. Please try again.")
 *         )
 *     )
 * )
 */

    public function globalSearch(): JsonResponse
{
    $search = request()->get('query', '');
    $perPage = (int) request()->get('per_page', 50);

    $users = $this->userService->globalSearch($search, $perPage);

    return response()->json([
        'status' => 'success',
        'code' => 200,
        'message' => 'Users fetched successfully.',
        'data' => UserResource::collection($users),
        'pagination' => [
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
        ],
    ]);
}
}
