<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\CountryRequest;
use App\Models\Country;
use App\Services\V1\MasterServices\Web\CountryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Helpers\LogHelper;

/**
 * @OA\Tag(
 *     name="Country",
 *     description="API endpoints for managing countries"
 * )
 */
class CountryController extends Controller
{
    use ApiResponse;
    protected $countryService;

    public function __construct(CountryService $countryService)
    {
        $this->countryService = $countryService;
    }

    /**
     * @OA\Get(
     *     path="/api/master/country/list_country",
     *     tags={"Country"},
     *     summary="Get all countries with filters & pagination",
     *     security={{"bearerAuth":{}}},
     *     
     *     @OA\Parameter(
     *         name="country_name",
     *         in="query",
     *         description="Filter by country name",
     *         required=false,
     *         @OA\Schema(type="string", example="Uganda")
     *     ),
     *     @OA\Parameter(
     *         name="country_code",
     *         in="query",
     *         description="Filter by country code",
     *         required=false,
     *         @OA\Schema(type="string", example="UG")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status (1=Active, 0=Inactive)",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of records per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="List of countries",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Countries fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="country_code", type="string", example="UG"),
     *                     @OA\Property(property="country_name", type="string", example="Uganda"),
     *                     @OA\Property(property="currency", type="string", example="UGX"),
     *                     @OA\Property(property="status", type="integer", example=1)
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
    public function index(Request $request)
    {
        $filters = $request->only(['country_name', 'country_code', 'status']);
        $perPage = $request->get('limit', 10); // default 10

        // pass perPage first, then filters
        $countries = $this->countryService->getAll($perPage, $filters);

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Countries fetched successfully',
            'data'    => $countries->items(),
            'pagination' => [
                'page'         => $countries->currentPage(),
                'limit'        => $countries->perPage(),
                'totalPages'   => $countries->lastPage(),
                'totalRecords' => $countries->total(),
            ]
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/master/country/country/{id}",
     *     tags={"Country"},
     *     summary="Get a single country",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Country details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="country_code", type="string", example="UG"),
     *                 @OA\Property(property="country_name", type="string", example="Uganda"),
     *                 @OA\Property(property="currency", type="string", example="UGX"),
     *                 @OA\Property(property="status", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Country not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show($id): JsonResponse
    {
        $country = $this->countryService->getById($id);
        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Success',
            'data'    => $country
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/master/country/add_country",
     *     tags={"Country"},
     *     summary="Create a new country",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="country_code", type="string", example="UG"),
     *             @OA\Property(property="country_name", type="string", example="Uganda"),
     *             @OA\Property(property="currency", type="string", example="UGX"),
     *             @OA\Property(property="status", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Country created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Country created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(CountryRequest $request): JsonResponse
    {
        $country = $this->countryService->create($request->validated());

         if ($country) {
        LogHelper::store(
            'settings',                     
            'country',                   
            'add',                        
            null,                          
            $country->getAttributes(),      
            auth()->id()                    
        );
    }
        return response()->json([
            'status'  => true,
            'code'    => 201,
            'message' => 'Country created successfully',
            'data'    => $country
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/master/country/update_country/{id}",
     *     tags={"Country"},
     *     summary="Update a country",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="country_code", type="string", example="UG"),
     *             @OA\Property(property="country_name", type="string", example="Uganda"),
     *             @OA\Property(property="currency", type="string", example="UGX"),
     *             @OA\Property(property="status", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Country updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Country updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Country not found"),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
public function update(CountryRequest $request, $id): JsonResponse
{
    $country = $this->countryService->getById($id);
    $previousData = $country ? $country->getOriginal() : null;

    $updated = $this->countryService->update($country, $request->validated());

    if ($updated && $previousData) {
        LogHelper::store(
            'settings',                
            'country',                   
            'update',                 
            $previousData,             
            $updated->getAttributes(),     
            auth()->id()           
        );
    }

    return response()->json([
        'status'  => true,
        'code'    => 200,
        'message' => 'Country updated successfully',
        'data'    => $updated
    ]);
}

    /**
     * @OA\Delete(
     *     path="/api/master/country/{id}",
     *     tags={"Country"},
     *     summary="Delete a country",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Country deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Country deleted successfully"),
     *             @OA\Property(property="data", type="object", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Country not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
public function destroy($id): JsonResponse
{
    $country = $this->countryService->getById($id);
    $previousData = $country ? $country->getOriginal() : null;

    $this->countryService->delete($country);
    if ($previousData) {
        LogHelper::store(
            'settings',         
            'country',           
            'delete',          
            $previousData,  
            null,              
            auth()->id()         
        );
    }

    return response()->json([
        'status'  => true,
        'code'    => 200,
        'message' => 'Country deleted successfully',
        'data'    => null
    ]);
}

    /**
     * @OA\Get(
     *     path="/api/master/country/global_search",
     *     tags={"Country"},
     *     summary="Search countries globally across multiple fields",
     *     description="Search in country_code, country_name, currency, status, created_user, updated_user, etc. and return paginated results.",
     *     security={{"bearerAuth":{}}},
     *
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
     *
     *     @OA\Response(
     *         response=200,
     *         description="Search results with pagination",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseSuccess")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server Error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     )
     * )
     */

  public function global_search(Request $request)
{
    try {
        $perPage = $request->get('per_page', 10);
        $keyword = $request->get('query');

        $countries = $this->countryService->search($perPage, $keyword);

        return $this->success(
            $countries->items(), // ✅ only the data, not the paginator object
            'Search results',
            200,
            [
                'pagination' => [
                    'current_page' => $countries->currentPage(),
                    'last_page'    => $countries->lastPage(),
                    'per_page'     => $countries->perPage(),
                    'total'        => $countries->total(),
                ]
            ]
        );
    } catch (\Exception $e) {
        return $this->fail($e->getMessage(), 500);
    }
}





}
