<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\AssetBrandingRequest;
use App\Http\Resources\V1\Settings\Web\AssetBrandingResource;
use App\Services\V1\Settings\Web\AssetBrandingService;
use App\Helpers\LogHelper;

class AssetBrandingController extends Controller
{
    protected $service;

    public function __construct(AssetBrandingService $service)
    {
        $this->service = $service;
    }

    /**
     * Create asset branding
     */
    public function store(AssetBrandingRequest $request)
    {
        $response = $this->service->create($request->validated());

        if (
        isset($response['status']) &&
        $response['status'] === 'success' &&
        isset($response['data'])
    ) {
        LogHelper::store(
            'settings',                         
            'assetbranding',                       
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
            'data'    => new AssetBrandingResource($response['data'])
        ], 201);
    }

    /**
     * List all asset branding
     */
    public function index()
    {
        $response = $this->service->list();

        return response()->json([
            'status'     => 'success',
            'code'       => 200,
            'message'    => $response['message'],
            'data'       => AssetBrandingResource::collection($response['data']),
            'pagination' => $response['pagination']
        ]);
    }
}



// <?php

// namespace App\Http\Controllers\V1\Settings\Web;

// use App\Http\Controllers\Controller;
// use App\Http\Requests\V1\Settings\Web\AssetBrandingRequest;
// use App\Services\V1\Settings\Web\AssetBrandingService;

// /**
//  * @OA\Tag(
//  *     name="Asset Branding",
//  *     description="API for managing asset branding"
//  * )
//  */
// class AssetBrandingController extends Controller
// {
//     protected AssetBrandingService $service;

//     public function __construct(AssetBrandingService $service)
//     {
//         $this->service = $service;
//     }

//     /**
//      * List asset branding
//      *
//      * @OA\Get(
//      *     path="/api/settings/assets-branding/list",
//      *     tags={"Asset Branding"},
//      *     summary="List all asset branding",
//      *     operationId="listAssetBranding",
//      *     @OA\Response(
//      *         response=200,
//      *         description="Branding list fetched successfully",
//      *         @OA\JsonContent(
//      *             type="object",
//      *             @OA\Property(property="status", type="string", example="success"),
//      *             @OA\Property(property="code", type="integer", example=200),
//      *             @OA\Property(property="message", type="string", example="Branding list fetched successfully"),
//      *             @OA\Property(
//      *                 property="data",
//      *                 type="array",
//      *                 @OA\Items(
//      *                     type="object",
//      *                     @OA\Property(property="id", type="integer", example=1),
//      *                     @OA\Property(property="osa_code", type="string", example="OC001"),
//      *                     @OA\Property(property="name", type="string", example="Premium Branding"),
//      *                     @OA\Property(property="status", type="integer", example=1)
//      *                 )
//      *             ),
//      *             @OA\Property(
//      *                 property="pagination",
//      *                 type="object",
//      *                 @OA\Property(property="page", type="integer", example=1),
//      *                 @OA\Property(property="limit", type="integer", example=50),
//      *                 @OA\Property(property="totalPages", type="integer", example=1),
//      *                 @OA\Property(property="totalRecords", type="integer", example=2)
//      *             )
//      *         )
//      *     )
//      * )
//      */
//     public function index()
//     {
//         $response = $this->service->list();

//         return response()->json([
//             'status'     => $response['status'] ?? 'success',
//             'code'       => $response['code'] ?? 200,
//             'message'    => $response['message'] ?? 'Branding list fetched successfully',
//             'data'       => $response['data'] ?? [],
//             'pagination' => $response['pagination'] ?? []
//         ]);
//     }

//     /**
//      * Create asset branding
//      *
//      * @OA\Post(
//      *     path="/api/settings/assets-branding/add",
//      *     tags={"Asset Branding"},
//      *     summary="Create new asset branding",
//      *     operationId="createAssetBranding",
//      *     @OA\RequestBody(
//      *         required=true,
//      *         @OA\JsonContent(
//      *             type="object",
//      *             @OA\Property(property="osa_code", type="string", example="OC001"),
//      *             @OA\Property(property="name", type="string", example="Premium Branding"),
//      *             @OA\Property(property="status", type="integer", example=1)
//      *         )
//      *     ),
//      *     @OA\Response(
//      *         response=201,
//      *         description="Asset branding created successfully",
//      *         @OA\JsonContent(
//      *             type="object",
//      *             @OA\Property(property="status", type="string", example="success"),
//      *             @OA\Property(property="code", type="integer", example=201),
//      *             @OA\Property(property="message", type="string", example="Branding created successfully"),
//      *             @OA\Property(property="data", type="object",
//      *                 @OA\Property(property="id", type="integer", example=1),
//      *                 @OA\Property(property="osa_code", type="string", example="OC001"),
//      *                 @OA\Property(property="name", type="string", example="Premium Branding"),
//      *                 @OA\Property(property="status", type="integer", example=1)
//      *             )
//      *         )
//      *     ),
//      *     @OA\Response(response=422, description="Validation error"),
//      *     @OA\Response(response=500, description="Server error")
//      * )
//      */
//     public function store(AssetBrandingRequest $request)
//     {
//         $response = $this->service->create($request->validated());

//         if (($response['status'] ?? '') === 'error') {
//             return response()->json($response, $response['code'] ?? 500);
//         }

//         return response()->json([
//             'status'  => 'success',
//             'code'    => 201,
//             'message' => $response['message'] ?? 'Branding created successfully',
//             'data'    => $response['data'] ?? null
//         ], 201);
//     }
// }
