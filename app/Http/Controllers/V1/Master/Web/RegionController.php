<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\RegionRequest;
use App\Models\Region;
use App\Services\V1\MasterServices\Web\RegionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use App\Traits\ApiResponse;
use App\Helpers\LogHelper;
/**
 * @OA\Tag(
 *     name="Region",
 *     description="API endpoints for managing regions"
 * )
 */
class RegionController extends Controller
{
    use ApiResponse;
    protected RegionService $regionService;

    public function __construct(RegionService $regionService)
    {
        $this->regionService = $regionService;
    }

    /**
     * @OA\Get(
     *     path="/api/master/region/list_region",
     *     tags={"Region"},
     *     summary="Get all regions with filters & pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="region_code",
     *         in="query",
     *         description="Filter by region code",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="region_name",
     *         in="query",
     *         required=false,
     *         description="Filter by region name",
     *         @OA\Schema(type="string", example="Central")
     *     ),
     *     @OA\Parameter(
     *         name="company_id",
     *         in="query",
     *         required=false,
     *         description="Filter by company id",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by status",
     *         @OA\Schema(type="integer", enum={0,1}, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Items per page (default 10)",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of regions",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Regions fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="region_code", type="string", example="RG01"),
     *                     @OA\Property(property="region_name", type="string", example="Central"),
     *                     @OA\Property(property="company_id", type="integer", example=1),
     *                     @OA\Property(property="status", type="integer", example=1),
     *                     @OA\Property(
     *                         property="company",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="company_name", type="string", example="Uganda"),
     *                         @OA\Property(property="company_code", type="string", example="UG")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=5),
     *                 @OA\Property(property="totalRecords", type="integer", example=50)
     *             )
     *         )
     *     )
     * )
     */

public function index(Request $request): JsonResponse
{
    $filters = $request->only(['region_name', 'region_code', 'status', 'company_id']);
    $perPage = $request->get('limit', 10);
    $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
    $regions = $this->regionService->getAll($perPage, $filters, $dropdown);
    if ($dropdown) {
        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Regions fetched successfully (dropdown mode)',
            'data'    => $regions,
        ]);
    }
    return response()->json([
        'status'  => true,
        'code'    => 200,
        'message' => 'Regions fetched successfully',
        'data'    => $regions->items(),
        'pagination' => [
            'page'         => $regions->currentPage(),
            'limit'        => $regions->perPage(),
            'totalPages'   => $regions->lastPage(),
            'totalRecords' => $regions->total(),
        ]
    ]);
}


    /**
     * @OA\Get(
     *     path="/api/master/region/region_dropdown",
     *     tags={"Region"},
     *     summary="Get active regions for dropdown with pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of regions for dropdown",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Regions fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="region_code", type="string", example="RG01"),
     *                     @OA\Property(property="region_name", type="string", example="Central")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=5),
     *                 @OA\Property(property="totalRecords", type="integer", example=50)
     *             )
     *         )
     *     )
     * )
     */
    public function regionDropdown(Request $request): JsonResponse
    {
        $perPage = $request->get('limit', 10);
        $regions = $this->regionService->regionDropdown($perPage);

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Regions fetched successfully',
            'data'    => $regions->items(),
            'pagination' => [
                'page'         => $regions->currentPage(),
                'limit'        => $regions->perPage(),
                'totalPages'   => $regions->lastPage(),
                'totalRecords' => $regions->total(),
            ]
        ]);
    }



    /**
     * @OA\Post(
     *     path="/api/master/region/add_region",
     *     tags={"Region"},
     *     summary="Create a new region",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"region_name","company_id"},
     *             @OA\Property(property="region_name", type="string", maxLength=200, example="Central"),
     *             @OA\Property(property="company_id", type="integer", example=1),
     *             @OA\Property(property="status", type="integer", enum={0,1}, nullable=true, example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Region created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Region created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function store(RegionRequest $request): JsonResponse
    {
        try {
            $region = $this->regionService->create($request->validated());

             if ($region) {
            LogHelper::store(
                'settings',                  
                'region',                 
                'add',                         
                null,                      
                $region->getAttributes(),       
                auth()->id()               
            );
        }
            return response()->json([
                'status'  => true,
                'code'    => 201,
                'message' => 'Region created successfully',
                'data'    => $region
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'code'    => 500,
                'message' => $e->getMessage(),
                'errors'  => null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/master/region/{id}",
     *     tags={"Region"},
     *     summary="Get a single region",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Region details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="region_name", type="string", example="Central"),
     *                 @OA\Property(property="company_id", type="integer", example=1),
     *                 @OA\Property(property="status", type="integer", example=1),
     *                 @OA\Property(
     *                     property="company",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="company_name", type="string", example="Uganda")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Region not found")
     * )
     */
    public function show(Region $id): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Success',
            'data'    => $id->load([
                'company:id,company_code'
            ])
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/master/region/update_region/{id}",
     *     tags={"Region"},
     *     summary="Update a region",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"region_name","company_id"},
     *             @OA\Property(property="region_name", type="string", maxLength=200, example="Central"),
     *             @OA\Property(property="company_id", type="integer", example=1),
     *             @OA\Property(property="status", type="integer", enum={0,1}, nullable=true, example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Region updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Region updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Region not found")
     * )
     */
public function update(Request $request, $id)
{
    $oldRegion = Region::find($id);
    $previousData = $oldRegion ? $oldRegion->getOriginal() : null;

    try {
        $data = $request->all();
        $updated = $this->regionService->update($id, $data);
        if ($updated && $previousData) {
            LogHelper::store(
                'settings',                
                'region',             
                'update',            
                $previousData,             
                $updated->getAttributes(), 
                auth()->id()           
            );
        }
        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Region updated successfully',
            'data'    => $updated
        ]);
    } catch (Throwable $e) {
        return response()->json([
            'status'  => false,
            'code'    => 500,
            'message' => $e->getMessage(),
            'errors'  => null
        ], 500);
    }
}


    /**
     * @OA\Delete(
     *     path="/api/master/region/{id}",
     *     tags={"Region"},
     *     summary="Delete a region",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Region deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Region deleted successfully"),
     *             @OA\Property(property="data", type="object", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Region not found")
     * )
     */
public function destroy(Request $request, $id): JsonResponse
{
    try {
        $this->regionService->delete($id);

        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Region deleted successfully',
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status'  => false,
            'code'    => 500,
            'message' => $e->getMessage(),
        ], 500);
    }
}



/**
 * @OA\Get(
 *     path="/api/master/region/global_search",
 *     tags={"Region"},
 *     summary="Search regions globally across multiple fields",
 *     description="Search by region_name, region_code, status, company, created_user, updated_user, etc. Returns paginated results.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="query",
 *         in="query",
 *         required=false,
 *         @OA\Schema(type="string"),
 *         description="Search keyword to match across multiple fields"
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         required=false,
 *         @OA\Schema(type="integer", example=10),
 *         description="Number of records per page"
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         required=false,
 *         @OA\Schema(type="integer", example=1),
 *         description="Page number"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Search results retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Search results retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="region_name", type="string", example="North Region"),
 *                     @OA\Property(property="region_code", type="string", example="NR001"),
 *                     @OA\Property(property="company", type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="company_name", type="string", example="India"),
 *                         @OA\Property(property="company_code", type="string", example="IN")
 *                     ),
 *                     @OA\Property(property="status", type="integer", example=1),
 *                     @OA\Property(property="created_user", type="integer", example=1),
 *                     @OA\Property(property="updated_user", type="integer", example=2),
 *                     @OA\Property(property="created_date", type="string", example="2025-09-25 10:00:00"),
 *                     @OA\Property(property="updated_date", type="string", example="2025-09-25 12:00:00")
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="pagination",
 *                 type="object",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(property="last_page", type="integer", example=5),
 *                 @OA\Property(property="per_page", type="integer", example=10),
 *                 @OA\Property(property="total", type="integer", example=45)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="code", type="integer", example=401),
 *             @OA\Property(property="message", type="string", example="Unauthorized"),
 *             @OA\Property(property="data", type="object", nullable=true, example=null)
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server Error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="code", type="integer", example=500),
 *             @OA\Property(property="message", type="string", example="Server Error"),
 *             @OA\Property(property="data", type="object", nullable=true, example=null)
 *         )
 *     )
 * )
 */


public function global_search(Request $request)
{
    try {
        $perPage = $request->get('per_page', 10);
        $keyword = $request->get('query');

        $regions = $this->regionService->globalSearch($perPage, $keyword);

        return $this->success(
            $regions->items(),
            'Search results',
            200,
            [
                'pagination' => [
                    'current_page' => $regions->currentPage(),
                    'last_page'    => $regions->lastPage(),
                    'per_page'     => $regions->perPage(),
                    'total'        => $regions->total(),
                ]
            ]
        );

    } catch (\Exception $e) {
        return $this->fail($e->getMessage(), 500);
    }
}


}
