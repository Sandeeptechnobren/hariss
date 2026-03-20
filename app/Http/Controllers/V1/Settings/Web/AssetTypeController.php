<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\AssetTypeRequest;
use App\Http\Resources\V1\Settings\Web\AssetTypeResource;
use App\Services\V1\Settings\Web\AssetTypeService;
use App\Helpers\LogHelper;

/**
 * @OA\Tag(
 *     name="Asset Types",
 *     description="API for managing asset types"
 * )
 */
class AssetTypeController extends Controller
{
    protected $service;

    public function __construct(AssetTypeService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Post(
     *     path="/api/settings/asset-types/add",
     *     tags={"Asset Types"},
     *     summary="Create new asset type",
     *     operationId="createAssetType",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="osa_code", type="string", example="AT001"),
     *             @OA\Property(property="name", type="string", example="Computer"),
     *             @OA\Property(property="status", type="integer", example=1)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Asset type created successfully",
     *         @OA\JsonContent(
     *             example={
     *                 "status":"success",
     *                 "code":201,
     *                 "message":"Asset type created successfully",
     *                 "data":{
     *                     "id":1,
     *                     "osa_code":"AT001",
     *                     "name":"Computer",
     *                     "status":1
     *                 }
     *             }
     *         )
     *     )
     * )
     */
public function store(AssetTypeRequest $request)
{
    $response = $this->service->create($request->validated());
    if (
        isset($response['status']) &&
        $response['status'] === 'success' &&
        isset($response['data'])
    ) {
        LogHelper::store(
            'settings',                     
            'assetcategory',                
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
        'data'    => new AssetTypeResource($response['data']),
    ], 201);
}

    /**
     * @OA\Get(
     *     path="/api/settings/asset-types/list",
     *     tags={"Asset Types"},
     *     summary="Get list of asset types",
     *     operationId="listAssetTypes",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Asset type list",
     *         @OA\JsonContent(
     *             example={
     *                 "status":"success",
     *                 "code":200,
     *                 "message":"Asset types fetched successfully",
     *                 "data":{
     *                     {
     *                         "id":1,
     *                         "osa_code":"AT001",
     *                         "name":"Computer",
     *                         "status":1
     *                     }
     *                 },
     *                 "pagination":{
     *                     "page":1,
     *                     "limit":50,
     *                     "totalPages":1,
     *                     "totalRecords":1
     *                 }
     *             }
     *         )
     *     )
     * )
     */
    public function index()
    {
        $response = $this->service->list();

        if ($response['status'] === 'error') {
            return response()->json($response, $response['code']);
        }

        return response()->json([
            'status'     => 'success',
            'code'       => 200,
            'message'    => $response['message'],
            'data'       => AssetTypeResource::collection($response['data']),
            'pagination' => $response['pagination'],
        ], 200);
    }
}
