<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\LoginRequest;
use App\Http\Requests\V1\MasterRequests\Web\RegisterRequest;
use App\Http\Resources\V1\Master\Web\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\LoginSession;
use App\Services\V1\MasterServices\Web\AuthService;
use App\Services\V1\MasterServices\Web\SessionService;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
     *                 "firstname",
     *                 "lastname",
     *                 "username",
     *                 "email",
     *                 "password",
     *                 "password_confirmation",
     *                 "profile",
     *                 "role",
     *                 "status",
     *                 "region_id",
     *                 "subregion_id",
     *                 "salesman_id",
     *                 "subdepot_id",
     *                 "Modifier_Id",
     *                 "Modifier_Name",
     *                 "Modifier_Date",
     *                 "Login_Date",
     *                 "is_list",
     *                 "created_user",
     *                 "updated_user",
     *                 "Created_Date"
     *             },
     *             @OA\Property(property="firstname", type="string", maxLength=255, example="Amit"),
     *             @OA\Property(property="lastname", type="string", maxLength=255, example="Pathak"),
     *             @OA\Property(property="username", type="string", maxLength=255, example="amit007"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255, example="amit@example.com"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8, example="secret123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="secret123"),
     *             @OA\Property(property="profile", type="string", maxLength=255, example="profile.jpg"),
     *             @OA\Property(property="role", type="integer", example=2, description="0=Super-Admin,1=Admin,2=User"),
     *             @OA\Property(property="status", type="integer", example=1, description="1=Active,0=Inactive"),
     *             @OA\Property(property="region_id", type="integer", example=5),
     *             @OA\Property(property="subregion_id", type="integer", example=12),
     *             @OA\Property(property="salesman_id", type="integer", example=45),
     *             @OA\Property(property="subdepot_id", type="integer", example=7),
     *             @OA\Property(property="Modifier_Id", type="integer", example=101),
     *             @OA\Property(property="Modifier_Name", type="string", maxLength=150, example="AdminUser"),
     *             @OA\Property(property="Modifier_Date", type="string", format="date-time", example="2025-09-26 10:00:00"),
     *             @OA\Property(property="Login_Date", type="string", format="date-time", example="2025-09-26 09:30:00"),
     *             @OA\Property(property="is_list", type="boolean", example=true),
     *             @OA\Property(property="created_user", type="integer", example=1),
     *             @OA\Property(property="updated_user", type="integer", example=1),
     *             @OA\Property(property="Created_Date", type="string", format="date-time", example="2025-09-26 09:00:00"),
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
}
