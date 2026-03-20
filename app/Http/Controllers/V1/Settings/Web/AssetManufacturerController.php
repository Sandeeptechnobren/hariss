<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\AssetManufacturerRequest;
use App\Http\Resources\V1\Settings\Web\AssetManufacturerResource;
use App\Services\V1\Settings\Web\AssetManufacturerService;
use App\Helpers\LogHelper;

/**
 * @OA\Tag(
 *     name="Asset Manufacturer",
 *     description="API for managing manufacturers"
 * )
 */
class AssetManufacturerController extends Controller
{
    protected $service;

    public function __construct(AssetManufacturerService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Post(
     *     path="/api/settings/asset-manufacturer/add",
     *     tags={"Asset Manufacturer"},
     *     summary="Create new manufacturer",
     *     operationId="createManufacturer",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string", example="MF001"),
     *             @OA\Property(property="name", type="string", example="Pepsi"),
     *             @OA\Property(property="status", type="integer", example=1)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Manufacturer created successfully",
     *         @OA\JsonContent(
     *             example={
     *                 "status":"success",
     *                 "code":201,
     *                 "message":"Manufacturer created successfully",
     *                 "data":{
     *                     "id":1,
     *                     "code":"MF001",
     *                     "name":"Pepsi",
     *                     "status":1
     *                 }
     *             }
     *         )
     *     )
     * )
     */
    public function store(AssetManufacturerRequest $request)
    {
        $response = $this->service->create($request->validated());
        if (
        isset($response['status']) &&
        $response['status'] === 'success' &&
        isset($response['data'])
    ) {
        LogHelper::store(
            'settings',                              
            'assetmanufacturer',                    
            'add',                                   
            null,                                   
            $response['data']->getAttributes(),
            auth()->id()                         
        );
    }

        if ($response['status'] === 'error') {
            return response()->json($response, 500);
        }

        return response()->json([
            'status'  => 'success',
            'code'    => 201,
            'message' => $response['message'],
            'data'    => new AssetManufacturerResource($response['data'])
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/settings/asset-manufacturer/list",
     *     tags={"Asset Manufacturer"},
     *     summary="List all manufacturers",
     *     operationId="listManufacturer",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Manufacturers list",
     *         @OA\JsonContent(
     *             example={
     *                 "status":"success",
     *                 "code":200,
     *                 "message":"Manufacturers fetched successfully",
     *                 "data":{
     *                     {
     *                         "id":1,
     *                         "code":"MF001",
     *                         "name":"Pepsi",
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

        return response()->json([
            'status'     => 'success',
            'code'       => 200,
            'message'    => $response['message'],
            'data'       => AssetManufacturerResource::collection($response['data']),
            'pagination' => $response['pagination']
        ]);
    }
}
