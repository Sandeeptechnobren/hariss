<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\LoginRequest;
use App\Http\Requests\V1\MasterRequests\Web\RegisterRequest;
use App\Http\Resources\V1\Master\Web\UserResource;
use App\Models\User;
use App\Models\RoleHasPermission;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\LoginSession;
use App\Services\V1\MasterServices\Web\AuthService;
use App\Services\V1\MasterServices\Web\SessionService;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ResponseHelper;


/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for Authentication and Session Management"
 * )
 */
class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthService $authService,
        private readonly SessionService $sessionService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/master/auth/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={
     *                 "name",
     *                 "email",
     *                 "username",
     *                 "contact_number",
     *                 "password",
     *                 "password_confirmation",
     *                 "role",
     *                 "company",
     *                 "warehouse",
     *                 "route",
     *                 "salesman",
     *                 "region",
     *                 "area",
     *                 "outlet_channel",
     *                 "created_by"
     *             },
     *             @OA\Property(property="name", type="string", maxLength=255, example="sandeep"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255, example="sandeep@g23.com"),
     *             @OA\Property(property="username", type="string", maxLength=255, example="test@sadeep"),
     *             @OA\Property(property="contact_number", type="string", maxLength=20, example="324314254321"),
     *             @OA\Property(property="password", type="string", format="password", example="Sandeep@123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="Sandeep@123"),
     *             @OA\Property(property="role", type="integer", example=1),
     *             @OA\Property(property="company", type="array", @OA\Items(type="integer", example=110), example={110, 72}),
     *             @OA\Property(property="warehouse", type="array", @OA\Items(type="integer", example=113), example={113}),
     *             @OA\Property(property="route", type="array", @OA\Items(type="integer", example=54), example={54}),
     *             @OA\Property(property="salesman", type="array", @OA\Items(type="integer", example=133), example={133}),
     *             @OA\Property(property="region", type="array", @OA\Items(type="integer", example=1), example={1}),
     *             @OA\Property(property="area", type="array", @OA\Items(type="integer", example=1), example={1}),
     *             @OA\Property(property="outlet_channel", type="array", @OA\Items(type="integer", example=18), example={18}),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseSuccess")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     )
     * )
     */


    public function register(RegisterRequest $request)
    {
        $data = $this->authService->register($request->validated());
        return $this->success($data, 'Registered successfully', 201);
    }

    /**
     * @OA\Post(
     *     path="/api/master/auth/login",
     *     tags={"Authentication"},
     *     summary="Login user and get token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="amit@test.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logged in successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseSuccess")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized access"),
     *             @OA\Property(property="errors", type="object", nullable=true, example=null)
     *         )
     *     )
     * )
     */
    public function login(LoginRequest $request)
    {
        try {
            $data = $this->authService->login($request->validated());
            $this->sessionService->createSession(
                $data['user'],
                $data['tokenResult'],
                $request
            );
            $responseData = [
                'user'         => new UserResource($data['user']),
                'token_type'   => $data['token_type'] ?? 'Bearer',
                'access_token' => $data['access_token'],
                'expires'      => $data['expires'],
            ];
            return $this->success($responseData, 'Logged in successfully', 200);
        } catch (HttpException $e) {
            return $this->fail($e->getMessage(), $e->getStatusCode());
        } catch (ValidationException $e) {
            return $this->fail('Invalid credentials', 422, $e->errors());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/master/auth/me",
     *     tags={"Authentication"},
     *     summary="Get logged-in user profile",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized access"),
     *             @OA\Property(property="errors", type="object", nullable=true, example=null)
     *         )
     *     )
     * )
     */
    public function me(Request $request)
    {
        return $this->success(
            new UserResource($this->authService->me($request->user())),
            'User profile fetched'
        );
    }

    /**
     * @OA\Post(
     *     path="/api/master/auth/logout",
     *     tags={"Authentication"},
     *     summary="Logout current user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized access"),
     *             @OA\Property(property="errors", type="object", nullable=true, example=null)
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $token = $request->user()->token();
        $token->revoke();
        $this->sessionService->deleteSession($token->id);
        return $this->success(null, 'Logged out successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/logout-all",
     *     tags={"Authentication"},
     *     summary="Logout user from all devices",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized access"),
     *             @OA\Property(property="errors", type="object", nullable=true, example=null)
     *         )
     *     )
     * )
     */
    public function logoutAll(Request $request)
    {
        $user = $request->user();
        $user->tokens->each(fn($token) => $token->revoke());
        $this->sessionService->deleteAllSessions($user->id);
        return $this->success(null, 'Logged out from all devices');
    }

    /**
     * @OA\Get(
     *     path="/api/sessions",
     *     tags={"Authentication"},
     *     summary="Get active user sessions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized access"),
     *             @OA\Property(property="errors", type="object", nullable=true, example=null)
     *         )
     *     )
     * )
     */
    public function activeSessions(Request $request)
    {
        $sessions = $this->sessionService->getUserSessions($request->user()->id);
        return $this->success([
            'count'    => $sessions->count(),
            'sessions' => $sessions,
        ], 'Active sessions fetched successfully');
    }
    public function tokenCheck(Request $request)
    {
        $isValid = $this->authService->checkToken();
        if ($isValid) {
            return $this->success([], 'Token is valid');
        }
        return $this->fail('Invalid or expired token', 401);
    }


    /**
     * @OA\Get(
     *     path="/api/master/auth/getUserList",
     *     tags={"Authentication"},
     *     summary="Registered Users",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthorized access"),
     *             @OA\Property(property="errors", type="object", nullable=true, example=null)
     *         )
     *     )
     * )
     */
    // public function getUserList(Request $request)
    // {
    //     $perPage = $request->get('per_page', 50);
    //     $paginator = $this->authService->getUserList($perPage);
    //     return ResponseHelper::paginatedResponse(
    //         'Users retrieved successfully',
    //         \App\Http\Resources\V1\Master\Web\UserResource::class,
    //         $paginator
    //     );
    // }
//     public function getUserList(Request $request)
// {
//     $perPage = $request->get('per_page', 50);

//     $paginator = $this->authService->getUserList($perPage);

//     return response()->json([
//         'status'  => 'success',
//         'code'    => 200,
//         'message' => 'Users retrieved successfully',
//         'data'    => $paginator->items(),
//         'pagination' => [
//             'page' => $paginator->currentPage(),
//             'last_page'    => $paginator->lastPage(),
//             'per_page'     => $paginator->perPage(),
//             'total'        => $paginator->total(),
//         ]
//     ], 200);
// }
public function getUserList(Request $request)
{
    $perPage = $request->get('per_page', 50);

    $paginator = $this->authService->getUserList($perPage);

    $items = collect($paginator->items())->map(function ($user) {
        return [
            'id'             => $user->id,
            'name'           => $user->name,
            'user_code'      => $user->user_code,
            'email'          => $user->email,
            'contact_number' => $user->contact_number,
            'status'         => $user->status,
            'uuid'          => $user->uuid,
            'role'           => optional($user->roleDetails)->name, // ✅ FIX
        ];
    });

    return response()->json([
        'status'  => 'success',
        'code'    => 200,
        'message' => 'Users retrieved successfully',
        'data'    => $items,
        'pagination' => [
            'page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
        ]
    ], 200);
}
    // public function getUserList(Request $request)
    // {
    //     $users = $this->authService->getUserList();
    //     return $this->success($users, 'Users retrieved successfully', 200);
    // }
    /**
     * @OA\Put(
     *     path="/api/master/auth/updateUser/{uuid}",
     *     tags={"Authentication"},
     *     summary="Update an existing user",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="User UUID",
     *         @OA\Schema(type="string", example="4466881c-93f9-4bb8-83f3-0d480b135410")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", maxLength=255, example="sandeep"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255, example="sandeep@g23.com"),
     *             @OA\Property(property="username", type="string", maxLength=255, example="test@sadeep"),
     *             @OA\Property(property="contact_number", type="string", maxLength=20, example="324314254321"),
     *             @OA\Property(property="password", type="string", format="password", example="Sandeep@123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="Sandeep@123"),
     *             @OA\Property(property="role", type="integer", example=1),
     *             @OA\Property(property="company", type="array", @OA\Items(type="integer", example=110), example={110, 72}),
     *             @OA\Property(property="warehouse", type="array", @OA\Items(type="integer", example=113), example={113}),
     *             @OA\Property(property="route", type="array", @OA\Items(type="integer", example=54), example={54}),
     *             @OA\Property(property="salesman", type="array", @OA\Items(type="integer", example=133), example={133}),
     *             @OA\Property(property="region", type="array", @OA\Items(type="integer", example=1), example={1}),
     *             @OA\Property(property="area", type="array", @OA\Items(type="integer", example=1), example={1}),
     *             @OA\Property(property="outlet_channel", type="array", @OA\Items(type="integer", example=18), example={18}),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseSuccess")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     )
     * )
     */
    public function updateUser(Request $request, $uuid)
    {
        $user = User::where('uuid', $uuid)->firstOrFail();
        $user_id = Auth::user();
        $rules = [
            'name'             => ['sometimes', 'string'],
            // 'username'             => ['sometimes', 'string'],
            'email'            => ['sometimes', 'string', 'email', 'unique:users,email,' . $user->id],
            'contact_number'   => ['sometimes', 'string'],
            'password'         => ['nullable', 'string', 'min:12', 'confirmed'],
            'profile_picture'  => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'role'             => ['sometimes', 'integer', 'exists:roles,id'],
            'status'           => ['nullable', 'integer'],
            'street'           => ['nullable', 'string'],
            'city'             => ['nullable', 'string'],
            'zip'              => ['nullable', 'string'],
            'dob'              => ['nullable', 'date'],
            'country_id'       => ['nullable', 'integer'],
            'company'          => ['nullable', 'array'],
            'company.*'        => ['integer', 'exists:tbl_company,id'],
            'warehouse'        => ['nullable', 'array'],
            'warehouse.*'      => ['integer', 'exists:tbl_warehouse,id'],
            'route'            => ['nullable', 'array'],
            'route.*'          => ['integer', 'exists:tbl_route,id'],
            'salesman'         => ['nullable', 'array'],
            'salesman.*'       => ['integer', 'exists:salesman,id'],
            'region'           => ['nullable', 'array'],
            'region.*'         => ['integer', 'exists:tbl_region,id'],
            'area'             => ['nullable', 'array'],
            'area.*'           => ['integer', 'exists:tbl_areas,id'],
            'outlet_channel'   => ['nullable', 'array'],
            'outlet_channel.*' => ['integer', 'exists:outlet_channel,id'],
            'created_by'       => ['nullable', 'integer', 'exists:users,id'],
            'Created_Date'     => ['nullable', 'date'],
        ];
        $validated = $request->validate($rules);
        if ((int) $user_id->role != 1) {
            unset($validated['role']); // Non-admin cannot update role
        }
        unset($validated['username']);
        $validated['updated_user'] = $user_id->id;
        if ($request->hasFile('profile_picture') && $request->file('profile_picture')->isValid()) {
            $filename = Str::random(40) . '.' . $request->profile_picture->getClientOriginalExtension();
            $request->profile_picture->storeAs('profile_pictures', $filename, 'public');
            $validated['profile_picture'] = url('storage/profile_pictures/' . $filename);
        }
        $data = $this->authService->updateUser($uuid, $validated);
        return response()->json([
            'status'  => 'success',
            'message' => 'User updated successfully',
            'data'    => $data
        ], 200);
    }


    // public function updateUser(Request $request, $uuid)
    // {
    //     $user = User::where('uuid', $uuid)->firstOrFail();
    //     $user_id = Auth::user()->id;

    //     $rules = [
    //         'name'             => ['sometimes', 'string'],
    //         'email'            => ['sometimes', 'string', 'email', 'unique:users,email,' . $user->id],
    //         'contact_number'   => ['sometimes', 'string'],
    //         'password'         => ['nullable', 'string', 'min:12', 'confirmed'],
    //         'profile_picture'  => ['nullable', 'string', 'max:255'],
    //         'role'             => ['sometimes', 'integer', 'exists:roles,id'],
    //         'status'           => ['nullable', 'integer'],
    //         'street'           => ['nullable', 'string'],
    //         'city'             => ['nullable', 'string'],
    //         'zip'              => ['nullable', 'string'],
    //         'dob'              => ['nullable', 'date'],
    //         'country_id'       => ['nullable', 'integer'],
    //         'company'          => ['nullable', 'array'],
    //         'company.*'        => ['integer', 'exists:tbl_company,id'],
    //         'warehouse'        => ['nullable', 'array'],
    //         'warehouse.*'      => ['integer', 'exists:tbl_warehouse,id'],
    //         'route'            => ['nullable', 'array'],
    //         'route.*'          => ['integer', 'exists:tbl_route,id'],
    //         'salesman'         => ['nullable', 'array'],
    //         'salesman.*'       => ['integer', 'exists:salesman,id'],
    //         'region'           => ['nullable', 'array'],
    //         'region.*'         => ['integer', 'exists:tbl_region,id'],
    //         'area'             => ['nullable', 'array'],
    //         'area.*'           => ['integer', 'exists:tbl_areas,id'],
    //         'outlet_channel'   => ['nullable', 'array'],
    //         'outlet_channel.*' => ['integer', 'exists:outlet_channel,id'],
    //         'created_by'       => ['nullable', 'integer', 'exists:users,id'],
    //         'Created_Date'     => ['nullable', 'date'],
    //     ];

    //     $validated = $request->validate($rules);
    //     unset($validated['username']);
    //     $validated['updated_user'] = $user_id;
    // // dd($validated);
    //     $data = $this->authService->updateUser($uuid, $validated);
    //     return $this->success($data, 'User updated successfully');
    // }

    public function checkPermission(Request $request)
    {
        $user_id = Auth::user()->id;
        $validated = $request->validate([
            "role_id" => "required",
            "menu_id" => "required",
            "submenu_id" => "required"
        ]);
        $is_allowed = RoleHasPermission::where("role_id", $validated['role_id'])->where("menu_id", $validated['menu_id'])->where("submenu_id", $validated['submenu_id'])->exists();
        return response()->json([
            'status' => 'Success',
            'code' => 200,
            'has_permission' => $is_allowed
        ]);
    }


    public function getUserbyUuid(Request $request, $uuid)
    {
        $user = $this->authService->getUserbyUuid($uuid);
        return $this->success($user, 'Users retrieved successfully', 200);
    }
    public function checkUser(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:255',
        ]);
        $query = $request->query('query');
        $userExists = $this->authService->checkUser($query);
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'query' => $query,
            'exists' => $userExists,
            'message' => $userExists
                ? 'Match found in username or email.'
                : 'No match found for the given query.',
        ]);
    }


    public function changePassword(Request $request)
    {
        // dd($request);
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        return response()->json(
            $this->authService->changePassword(
                Auth::user(),
                $request->old_password,
                $request->new_password
            )
        );
    }

    public function loginalert(LoginRequest $request)
    {
        try {
            $data = $this->authService->loginalert($request->validated());

            $this->sessionService->createSession(
                $data['user'],
                $data['tokenResult'],
                $request
            );
            $responseData = [
                'user'         => new UserResource($data['user']),
                'token_type'   => $data['token_type'] ?? 'Bearer',
                'access_token' => $data['access_token'],
                'expires'      => $data['expires'],
                'reportToken'  => $data['reportToken'],
            ];
            return $this->success($responseData, 'Logged in successfully', 200);
        } catch (HttpException $e) {
            return $this->fail($e->getMessage(), $e->getStatusCode());
        } catch (ValidationException $e) {
            return $this->fail('Invalid credentials', 422, $e->errors());
        }
    }

    public function unblockUser($id)
    {
        try {

            $this->authService->unblockUser($id);

            return response()->json([
                'status' => true,
                'message' => 'User unblocked successfully'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
