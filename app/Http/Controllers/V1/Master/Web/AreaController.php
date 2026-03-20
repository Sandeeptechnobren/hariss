<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Http\Requests\V1\MasterRequests\Web\AreaRequest;
use App\Services\V1\MasterServices\Web\AreaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LogHelper; 

class AreaController extends Controller
{
    protected $areaService;

    public function __construct(AreaService $areaService)
    {
        $this->areaService = $areaService;
    }

    /**
     * @OA\Get(
     *     path="/api/master/area/list_area",
     *     summary="Get all areas with filter & pagination",
     *     tags={"Area"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="area_name", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="area_code", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="region_id", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="integer", enum={0,1})),
     *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="List of areas retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'area_name',
            'area_code',
            'region_id',
            'status'
        ]);

        $perPage  = $request->get('limit', 10);
        $dropdown = $request->boolean('dropdown', false);

        $areas = $this->areaService->getAll(
            $perPage,
            $filters,
            $dropdown
        );

        // 🔹 DROPDOWN RESPONSE (NO PAGINATION)
        if ($dropdown) {
            return response()->json([
                'status'  => true,
                'code'    => 200,
                'message' => 'Areas fetched successfully',
                'data'    => $areas
            ]);
        }

        // 🔹 NORMAL PAGINATED RESPONSE
        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Areas fetched successfully',
            'data'    => $areas->items(),
            'pagination' => [
                'page'         => $areas->currentPage(),
                'limit'        => $areas->perPage(),
                'totalPages'   => $areas->lastPage(),
                'totalRecords' => $areas->total(),
            ]
        ]);
    }

    // public function index(Request $request)
    // {
    //     $filters = $request->only(['area_name', 'area_code', 'region_id', 'status']);
    //     $perPage = $request->get('limit', 10);

    //     $areas = $this->areaService->getAll($perPage, $filters);

    //     return response()->json([
    //         'status'  => true,
    //         'code'    => 200,
    //         'message' => 'Areas fetched successfully',
    //         'data'    => $areas->items(),
    //         'pagination' => [
    //             'page'         => $areas->currentPage(),
    //             'limit'        => $areas->perPage(),
    //             'totalPages'   => $areas->lastPage(),
    //             'totalRecords' => $areas->total(),
    //         ]
    //     ]);
    // }

    /**
     * @OA\Post(
     *     path="/api/master/area/add_area",
     *     summary="Create a new area",
     *     tags={"Area"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="area_name", type="string", example="Central Area"),
     *             @OA\Property(property="region_id", type="integer", example=1),
     *             @OA\Property(property="status", type="integer", enum={0,1}, example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Area created successfully"
     *     )
     * )
     */
    public function store(AreaRequest $request)
    {
        $validated = $request->validated();
        $area = $this->areaService->create($validated);

         if ($area) {
        LogHelper::store(
            'settings',                
            'area',                     
            'add',            
            null,                      
            $area->getAttributes(),  
            auth()->id()                
        );
    }

        return response()->json([
            'status'  => true,
            'code'    => 201,
            'message' => 'Area created successfully',
            'data'    => $area
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/master/area/area/{id}",
     *     summary="Get area by ID",
     *     tags={"Area"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Area retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Area not found"
     *     )
     * )
     */
    public function show($id)
    {
        $area = $this->areaService->find($id);

        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Area retrieved successfully',
            'data'    => $area
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/master/area/area/{id}",
     *     summary="Update an existing area",
     *     tags={"Area"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="area_code", type="string", example="AR001"),
     *             @OA\Property(property="area_name", type="string", example="Updated Area Name"),
     *             @OA\Property(property="region_id", type="integer", example=1),
     *             @OA\Property(property="status", type="integer", enum={0,1}, example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Area updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Area not found"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $oldArea = Area::find($id);
        $previousData = $oldArea ? $oldArea->getOriginal() : null;
        $validated = $request->validate([
            'area_code'   => 'nullable|string|max:200|unique:tbl_areas,area_code,' . $id,
            'area_name'   => 'nullable|string|max:200',
            'region_id'   => 'nullable|exists:tbl_region,id',
            'status'      => 'nullable|in:0,1',
        ]);

        $area = $this->areaService->update($id, $validated);

        if ($area && $previousData) {
        LogHelper::store(
            'settings',               
            'area',                    
            'update',                
            $previousData,            
            $area->getAttributes(),    
            auth()->id()            
        );
    }

        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Area updated successfully',
            'data'    => $area
        ]);
    }


    /**
     * @OA\Delete(
     *     path="/api/master/area/area/{id}",
     *     summary="Delete an area by ID",
     *     tags={"Area"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Area deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Area not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $this->areaService->delete($id);

        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Area deleted successfully'
        ]);
    }
    /**
     * @OA\Get(
     *     path="/api/master/area/areadropdown",
     *     summary="Get Area Dropdown values",
     *     tags={"Areas Dropdown"},
     *     security={{"bearerAuth":{}}},
     * 
     *     @OA\Response(
     *         response=200,
     *         description="Area retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Central Region")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Areas not found"
     *     )
     * )
     */
    public function areaDropdown(Request $request)
    {
        $areas = $this->areaService->areaDropdown();
        return response()->json($areas);
    }
    /**
     * @OA\Get(
     *     path="/api/master/area/global_search",
     *     tags={"Area"},
     *     summary="Global search areas with pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer"),
     *         description="Number of records per page (default: 10)"
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         description="Search keyword for areas"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Areas fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Areas fetched successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items()),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=10),
     *                 @OA\Property(property="totalPages", type="integer", example=5),
     *                 @OA\Property(property="totalRecords", type="integer", example=50),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to search areas"
     *     )
     * )
     */
    public function global_search(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);
            $searchTerm = $request->get('search');

            $areas = $this->areaService->globalSearch($perPage, $searchTerm);

            return response()->json([
                "status" => "success",
                "code" => 200,
                "message" => "Areas fetched successfully",
                "data" => $areas->items(),
                "pagination" => [
                    "page" => $areas->currentPage(),
                    "limit" => $areas->perPage(),
                    "totalPages" => $areas->lastPage(),
                    "totalRecords" => $areas->total(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "code" => 500,
                "message" => $e->getMessage(),
                "data" => null
            ], 500);
        }
    }
}
