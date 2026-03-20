<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\AssetModelNumberRequest;
use App\Http\Resources\V1\Settings\Web\AssetModelNumberResource;
use App\Services\V1\Settings\Web\AssetModelNumberService;
use App\Helpers\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Asset Model Number",
 *     description="API for managing asset model numbers"
 * )
 */
class AssetModelNumberController extends Controller
{
    protected $service;

    public function __construct(AssetModelNumberService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Post(
     *     path="/api/settings/asset-model-number/add",
     *     tags={"Asset Model Number"},
     *     summary="Create new asset model number",
     *     operationId="createAssetModelNumber",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string", example="MN001"),
     *             @OA\Property(property="name", type="string", example="High Performance Model"),
     *             @OA\Property(property="asset_type", type="integer", example=1),
     *             @OA\Property(property="manu_type", type="integer", example=2),
     *             @OA\Property(property="status", type="integer", example=1)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Asset model number created successfully",
     *         @OA\JsonContent(
     *             example={
     *                 "status":"success",
     *                 "code":201,
     *                 "message":"Model number created successfully",
     *                 "data":{
     *                     "id":1,
     *                     "uuid":"ab12cd34-ef56-7890-gh12-ij345678kl90",
     *                     "code":"MN001",
     *                     "name":"High Performance Model",
     *                     "asset_type":1,
     *                     "manu_type":2,
     *                     "status":1,
     *                     "created_at":"2025-01-01 00:00:00",
     *                     "updated_at":"2025-01-01 00:00:00"
     *                 }
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function store(AssetModelNumberRequest $request)
    {
        $response = $this->service->create($request->validated());

        if (
            isset($response['status']) &&
            $response['status'] === 'success' &&
            isset($response['data'])
        ) {
            LogHelper::store(
                'settings',
                'assetmodel',
                'add',
                null,
                $response['data']->getAttributes(),
                auth()->id()
            );
        }
        if ($response['status'] === 'error') {
            return response()->json($response, $response['code']);
        }

        return response()->json([
            'status'  => 'success',
            'code'    => 201,
            'message' => $response['message'],
            'data'    => new AssetModelNumberResource($response['data'])
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/settings/asset-model-number/list",
     *     tags={"Asset Model Number"},
     *     summary="List all asset model numbers",
     *     operationId="listAssetModelNumbers",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Model number list",
     *         @OA\JsonContent(
     *             example={
     *                 "status":"success",
     *                 "code":200,
     *                 "message":"Model numbers fetched successfully",
     *                 "data":{
     *                     {
     *                         "id":1,
     *                         "uuid":"aa11bb22-cc33-dd44-ee55-ff6677889900",
     *                         "code":"MN001",
     *                         "name":"High Performance Model",
     *                         "asset_type":1,
     *                         "manu_type":2,
     *                         "status":1
     *                     },
     *                     {
     *                         "id":2,
     *                         "uuid":"bb22cc33-dd44-ee55-ff66-112233445566",
     *                         "code":"MN002",
     *                         "name":"Standard Model",
     *                         "asset_type":1,
     *                         "manu_type":3,
     *                         "status":1
     *                     }
     *                 },
     *                 "pagination":{
     *                     "page":1,
     *                     "limit":50,
     *                     "totalPages":1,
     *                     "totalRecords":2
     *                 }
     *             }
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $perPage    = (int) $request->input('per_page', 50);
        $isDropdown = $request->input('dropdown') === 'true';

        $response = $this->service->list($request->all(), $perPage);

        return response()->json(array_filter([
            'status'  => $response['status'],
            'code'    => $response['code'],
            'message' => $response['message'],

            // 🔹 Dropdown → raw list (id, name, code)
            // 🔹 Normal → resource collection
            'data' => $isDropdown
                ? $response['data']
                : AssetModelNumberResource::collection($response['data']),

            // 🔹 Pagination ONLY for normal list
            'pagination' => $isDropdown ? null : $response['pagination'],
        ]));
    }

    // public function index()
    // {
    //     $response = $this->service->list();

    //     return response()->json([
    //         'status'     => 'success',
    //         'code'       => 200,
    //         'message'    => $response['message'],
    //         'data'       => AssetModelNumberResource::collection($response['data']),
    //         'pagination' => $response['pagination']
    //     ]);
    // }
}
