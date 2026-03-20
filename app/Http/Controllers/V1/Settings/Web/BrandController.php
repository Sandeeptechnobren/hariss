<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\BrandRequest;
use App\Http\Resources\V1\Settings\Web\BrandResource;
use App\Services\V1\Settings\Web\BrandService;
use Illuminate\Http\Request;
use App\Helpers\LogHelper;
use App\Models\Brand;

/**
 * @OA\Schema(
 *     schema="BrandRequest",
 *     type="object",
 *     required={"name", "code"},
 *     @OA\Property(property="name", type="string", example="Pepsi"),
 *     @OA\Property(property="osa_code", type="string", example="PEP001"),
 *     @OA\Property(property="status", type="integer", example=1)
 * )
 *
 * @OA\Schema(
 *     schema="BrandResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="uuid", type="string", example="b0b9a940-8b7e-4a0d-a222-2a6012f6e458"),
 *     @OA\Property(property="name", type="string", example="Pepsi"),
 *     @OA\Property(property="code", type="string", example="PEP001"),
 *     @OA\Property(property="status", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class BrandController extends Controller
{
    protected $service;

    public function __construct(BrandService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/brands/list",
     *     summary="Get list of brands",
     *     description="Retrieve all brands with optional filters and pagination",
     *     tags={"Brand"},
     *     security={{"bearerAuth":{}}},

     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of records per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of brands",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/BrandResource")),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="page", type="integer"),
     *                 @OA\Property(property="limit", type="integer"),
     *                 @OA\Property(property="totalPages", type="integer"),
     *                 @OA\Property(property="totalRecords", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $isDropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
        $perPage = (int) $request->get('limit', 50);
        $filters = $request->except(['limit', 'page', 'dropdown']);

        $locations = $this->service->all($perPage, $filters, $isDropdown);

        if ($isDropdown) {
            return response()->json([
                'data' => BrandResource::collection($locations)
            ]);
        }

        return response()->json([
            'data' => BrandResource::collection($locations),
            'pagination' => [
                'page' => $locations->currentPage(),
                'limit' => $locations->perPage(),
                'totalPages' => $locations->lastPage(),
                'totalRecords' => $locations->total(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/settings/brands/add",
     *     summary="Create a new brand",
     *     description="Store a new brand in the database",
     *     tags={"Brand"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BrandRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Brand created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/BrandResource")
     *     )
     * )
     */
    public function store(BrandRequest $request)
    {
        $location = $this->service->create($request->validated());
        if ($location) {
        LogHelper::store(
            'settings',   
            'brand',                    
            'add',                     
            null,                      
            $location->getAttributes(),    
            auth()->id()             
        );
    }

        return response()->json([
            'data' => new BrandResource($location)
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/settings/brands/show/{uuid}",
     *     summary="Get brand by UUID",
     *     description="Retrieve a single brand by its UUID",
     *     tags={"Brand"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="UUID of the brand",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Brand data retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/BrandResource")
     *     )
     * )
     */
    public function show($uuid)
    {
        $location = $this->service->getByUuid($uuid);

        return response()->json([
            'data' => new BrandResource($location)
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/settings/brands/update/{uuid}",
     *     summary="Update brand by UUID",
     *     description="Update an existing brand",
     *     tags={"Brand"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         description="UUID of the brand to update",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BrandRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Brand updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/BrandResource")
     *     )
     * )
     */
    public function update(BrandRequest $request, $uuid)
    {
        $oldBrand = Brand::where('uuid', $uuid)->first();
        $previousData = $oldBrand ? $oldBrand->getOriginal() : null;
        $location = $this->service->getByUuid($uuid);
        $updated = $this->service->update($location, $request->validated());
        if ($updated && $previousData) {
        LogHelper::store(
            'settings',                   
            'brand',                   
            'update',                 
            $previousData,           
            $updated->getAttributes(),  
            auth()->id()                  
        );
    }

        return response()->json([
            'data' => new BrandResource($updated)
        ]);
    }
}
