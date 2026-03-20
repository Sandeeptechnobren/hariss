<?php
namespace App\Http\Controllers\V1\Merchendisher\Web;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Merchendisher\Web\StoreShelveRequest;
use App\Http\Requests\V1\Merchendisher\Web\UpdateShelveRequest;
use App\Http\Resources\V1\Merchendisher\Web\ShelveResource;
use App\Models\Shelve;
use App\Models\CompanyCustomer;
use App\Models\Salesman;
use App\Services\V1\Merchendisher\Web\ShelveService;
use Illuminate\Http\Request; 
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Excel as ExcelFormat;
use InvalidArgumentException;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Str;

class ShelveController extends Controller
{
    protected ShelveService $service;

    public function __construct(ShelveService $service)
    {
        $this->service = $service;
    }
// /**
//  * @OA\Get(
//  *     path="/api/merchendisher/shelves/dropdown",
//  *     tags={"Shelves"},
//  *     summary="Get a list of company customers for dropdown",
//  *     description="Fetches a list of customers assigned to the authenticated merchandiser. Returns an empty array if none found.",
//  *     security={{"bearerAuth":{}}},
//  *     @OA\Response(
//  *         response=200,
//  *         description="Company customer list fetched successfully",
//  *         @OA\JsonContent(
//  *             type="object",
//  *             @OA\Property(property="status", type="boolean", example=true),
//  *             @OA\Property(property="message", type="string", example="Company customer list fetched successfully."),
//  *             @OA\Property(
//  *                 property="data",
//  *                 type="array",
//  *                 @OA\Items(
//  *                     type="object",
//  *                     @OA\Property(property="id", type="integer", example=1),
//  *                     @OA\Property(property="display_name", type="string", example="CT001-Adarsh")
//  *                 )
//  *             )
//  *         )
//  *     ),
//  *     @OA\Response(
//  *         response=401,
//  *         description="Unauthorized - invalid or missing token",
//  *         @OA\JsonContent(
//  *             type="object",
//  *             @OA\Property(property="status", type="boolean", example=false),
//  *             @OA\Property(property="message", type="string", example="Unauthorized."),
//  *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
//  *         )
//  *     )
//  * )
//  */
// public function dropdown()
// {
//     $customers = $this->service->getDropdownList();

//     if ($customers->isEmpty()) {
//         return response()->json([
//             'status' => false,
//             'message' => 'No merchandiser found .',
//             'data' => []
//         ]);
//     }

//     return response()->json([
//         'status' => true,
//         'message' => 'Company customer list fetched successfully.',
//         'data' => $customers
//     ]);
// }
/**
 * @OA\Get(
 *     path="/api/merchendisher/shelves/dropdown",
 *     tags={"Shelves"},
 *     summary="Get a list of company customers for dropdown",
 *     description="Fetches a list of customers assigned to the given merchandiser IDs. Returns an empty array if none found.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="merchandiser_ids[]",
 *         in="query",
 *         required=false,
 *         description="Array of merchandiser IDs to filter customers by",
 *         @OA\Schema(
 *             type="array",
 *             @OA\Items(type="integer", example=1)
 *         )
 *     ),
 *     @OA\Parameter(
 *         name="merchandiser_ids[]",
 *         in="query",
 *         required=false,
 *         description="Array of merchandiser IDs to filter customers by",
 *         @OA\Schema(
 *             type="array",
 *             @OA\Items(type="integer", example=1)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Company customer list fetched successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Company customer list fetched successfully."),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 additionalProperties=@OA\Property(type="string", example="CT001-Adarsh")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized - invalid or missing token",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Unauthorized."),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     )
 * )
 */

  public function dropdown(Request $request)
{
    $merchandiserIds = $request->input('merchandiser_ids', []);

    $customers = $this->service->getDropdownList($merchandiserIds);

    if ($customers->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No customers found for the given merchandisers.',
            'data' => []
        ]);
    }
    return response()->json([
        'status' => true,
        'message' => 'Company customer list fetched successfully.',
        'data' => $customers
    ]);
}

/**
 * @OA\Post(
 *     path="/api/merchendisher/shelves/add",
 *     tags={"Shelves"},
 *     summary="Create a new shelf",
 *     description="Creates a new shelf and returns the created shelf details.",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             required={"shelf_name","height","width","depth","customer_ids"},
 *             @OA\Property(property="shelf_name", type="string", example="Vivek yadav"),
 *             @OA\Property(property="height", type="number", format="float", example=6.9),
 *             @OA\Property(property="width", type="number", format="float", example=7.8),
 *             @OA\Property(property="depth", type="number", format="float", example=7.0),
 *             @OA\Property(property="valid_from", type="string", format="date", example="2025-07-12"),
 *             @OA\Property(property="valid_to", type="string", format="date", example="2025-07-14"),
 *             @OA\Property(
 *                 property="customer_ids",
 *                 type="array",
 *                 @OA\Items(type="integer"),
 *                 example={1,2,3}
 *             ),
 *              @OA\Property(
 *                 property="merchendiser_ids",
 *                 type="array",
 *                 @OA\Items(type="integer"),
 *                 example={1,2,3}
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Shelf created successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Shelf created successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="shelf_name", type="string", example="Vivek yadav"),
 *                 @OA\Property(property="height", type="number", format="float", example=6.9),
 *                 @OA\Property(property="width", type="number", format="float", example=7.8),
 *                 @OA\Property(property="depth", type="number", format="float", example=7.0),
 *                 @OA\Property(property="valid_from", type="string", format="date", example="2025-07-12"),
 *                 @OA\Property(property="valid_to", type="string", format="date", example="2025-07-14"),
 *                 @OA\Property(
 *                     property="customer_ids",
 *                     type="array",
 *                     @OA\Items(type="integer"),
 *                     example={1,2,3}
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 additionalProperties=@OA\Property(type="array", @OA\Items(type="string"))
 *             )
 *         )
 *     )
 * )
 */
    public function store(StoreShelveRequest $request): JsonResponse
    {
        $shelve = $this->service->create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Shelf created successfully',
            'data'    => new ShelveResource($shelve),
        ]);
    }
/**
 * @OA\Get(
 *     path="/api/merchendisher/shelves/list",
 *     tags={"Shelves"},
 *     summary="Get all shelves",
 *     description="Fetches a list of all shelves with their customer details.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful response",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Shelves fetched successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="shelf_name", type="string", example="Vivek Yadav"),
 *                     @OA\Property(property="height", type="number", format="float", example=6.9),
 *                     @OA\Property(property="width", type="number", format="float", example=7.8),
 *                     @OA\Property(property="depth", type="number", format="float", example=7.0),
 *                     @OA\Property(property="valid_from", type="string", format="date", example="2025-07-12"),
 *                     @OA\Property(property="valid_to", type="string", format="date", example="2025-07-14"),
 *                     @OA\Property(property="customer_ids", type="string", example="86,72"),
 *                     @OA\Property(
 *                         property="customer_details",
 *                         type="array",
 *                         @OA\Items(
 *                             type="object",
 *                             @OA\Property(property="id", type="integer", example=86),
 *                             @OA\Property(property="customer_code", type="string", example="CUST-001"),
 *                             @OA\Property(property="owner_name", type="string", example="John Doe")
 *                         )
 *                     )
 *                 )
 *             )
 *         )
 *     )
 * )
 */
public function index()
    {
        $shelve = $this->service->getAll();
        return ResponseHelper::paginatedResponse(
        'Shelve fetched successfully',
        ShelveResource::class,
        $shelve
      );
    }
/**
 * @OA\Delete(
 *     path="/api/merchendisher/shelves/destroy/{uuid}",
 *     tags={"Shelves"},
 *     summary="Delete a shelf",
 *     description="Deletes a shelf by its UUID.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         description="UUID of the shelf to delete",
 *         required=true,
 *         @OA\Schema(
 *             type="string",
 *             format="uuid",
 *             example="a944271e-3ee9-4e9d-a755-398b6fa5bdab"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Shelf deleted successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Shelf deleted successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid UUID format",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Invalid UUID format")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Shelf not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Shelf not found")
 *         )
 *     )
 * )
 */
    public function destroy(string $uuid): JsonResponse
    {
        $uuid = trim($uuid);

        if (!Str::isUuid($uuid)) {
            return response()->json(['success' => false, 'message' => 'Invalid UUID format'], 400);
        }

        $shelve = Shelve::where('uuid', $uuid)->first();

        if (!$shelve) {
            return response()->json(['success' => false, 'message' => 'Shelf not found'], 404);
        }

        $shelve->delete();

        return response()->json(['success' => true, 'message' => 'Shelf deleted successfully']);
    }
/**
 * @OA\Get(
 *     path="/api/merchendisher/shelves/show/{uuid}",
 *     tags={"Shelves"},
 *     summary="Get a single shelf by UUID",
 *     description="Fetches a single shelf's details by its UUID, including related customers and merchandisers.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         description="UUID of the shelf to fetch",
 *         required=true,
 *         @OA\Schema(type="string", format="uuid", example="c2f86c61-526c-47a3-8a6a-9b3f1a535943")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Shelf fetched successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="uuid", type="string", format="uuid", example="c2f86c61-526c-47a3-8a6a-9b3f1a535943"),
 *                 @OA\Property(property="shelf_name", type="string", example="Vivek yadav"),
 *                 @OA\Property(property="height", type="number", format="float", example=6.9),
 *                 @OA\Property(property="width", type="number", format="float", example=7.8),
 *                 @OA\Property(property="depth", type="number", format="float", example=7.0),
 *                 @OA\Property(property="valid_from", type="string", format="date", example="2025-07-12"),
 *                 @OA\Property(property="valid_to", type="string", format="date", example="2025-07-14"),
 *                 
 *                 @OA\Property(
 *                     property="customers",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="customer_code", type="string", example="CUST-001"),
 *                         @OA\Property(property="customer_type", type="string", example="Retailer"),
 *                         @OA\Property(property="owner_name", type="string", example="John Doe")
 *                     )
 *                 ),
 *                 
 *                 @OA\Property(
 *                     property="merchandisers",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="osa_code", type="string", example="OSA-123"),
 *                         @OA\Property(property="type", type="string", example="Sales"),
 *                         @OA\Property(property="name", type="string", example="Jane Smith")
 *                     )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Shelf not found",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Shelf not found")
 *         )
 *     )
 * )
 */
        public function show($uuid)
    {
         $shelve = $this->service->getByUuid($uuid);

    if (!$shelve) {
        return response()->json([
            'message' => 'No Shelve found',
            'code' => 200,
            'data' => null,
        ]);
    }

    return response()->json([
        'message' => 'Shelve retrieved successfully',
        'code' => 200,
        'data' => new ShelveResource($shelve),
       ]);
    }
/**
 * @OA\Put(
 *     path="/api/merchendisher/shelves/update/{uuid}",
 *     tags={"Shelves"},
 *     summary="Update a shelf by UUID",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         required=true,
 *         description="UUID of the shelf",
 *         @OA\Schema(type="string", format="uuid")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="shelf_name", type="string", example="Updated Shelf Name"),
 *             @OA\Property(property="height", type="number", format="float", example=6.9),
 *             @OA\Property(property="width", type="number", format="float", example=7.8),
 *             @OA\Property(property="depth", type="number", format="float", example=7.0),
 *             @OA\Property(property="valid_from", type="string", format="date", example="2025-07-12"),
 *             @OA\Property(property="valid_to", type="string", format="date", example="2025-07-14"),
 *             @OA\Property(property="customer_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="merchendiser_ids", type="array", @OA\Items(type="integer")),
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Shelf updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Shelf updated successfully."),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Shelf not found"
 *     )
 * )
 */
public function update(UpdateShelveRequest $request, $uuid)
{
    $shelve = (new ShelveService())->updateByUuid($uuid, $request->validated());

    if (!$shelve) {
        return response()->json([
            'success' => false,
            'message' => 'Shelf not found.',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'message' => 'Shelf updated successfully.',
        'data' => new ShelveResource($shelve),
    ]);
}

/**
 * @OA\Get(
 *     path="/api/merchendisher/shelves/global-search",
 *     operationId="globalSearchShelves",
 *     tags={"Shelves"},
 *     summary="Global search for shelves",
 *     security={{"bearerAuth":{}}},
 *     description="Search shelves by shelf fields, related users, customers, and CSV IDs. Supports pagination.",
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Search term for shelves, users, customers, or IDs",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         description="Number of results per page",
 *         required=false,
 *         @OA\Schema(type="integer", default=10)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Shelves retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Shelves retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=23),
 *                     @OA\Property(property="shelf_name", type="string", example="Harsh Chaubey"),
 *                     @OA\Property(property="height", type="string", example="6.70"),
 *                     @OA\Property(property="width", type="string", example="7.80"),
 *                     @OA\Property(property="depth", type="string", example="7.20"),
 *                     @OA\Property(property="valid_from", type="string", format="date-time", example="2025-07-12T00:00:00.000000Z"),
 *                     @OA\Property(property="valid_to", type="string", format="date-time", example="2025-07-21T00:00:00.000000Z"),
 *                     @OA\Property(
 *                         property="customer_details",
 *                         type="array",
 *                         @OA\Items(
 *                             type="object",
 *                             @OA\Property(property="id", type="integer", example=72),
 *                             @OA\Property(property="customer_code", type="string", example="fdsg"),
 *                             @OA\Property(property="owner_name", type="string", example="sfdgsdf")
 *                         )
 *                     ),
 *                     @OA\Property(property="created_user", type="integer", example=5),
 *                     @OA\Property(property="updated_user", type="integer", example=5),
 *                     @OA\Property(property="deleted_user", type="integer", nullable=true, example=null),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-26T18:16:47.000000Z"),
 *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-26T18:16:47.000000Z"),
 *                     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null)
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
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="code", type="integer", example=500),
 *             @OA\Property(property="message", type="string", example="Internal server error"),
 *             @OA\Property(property="data", type="null")
 *         )
 *     )
 * )
 */
 public function globalSearch(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $searchTerm = $request->get('search');

            $details = $this->service->globalSearch($perPage, $searchTerm);

            $message = $details->isEmpty() ? 'Shelves not found' : 'Shelves retrieved successfully';

            return response()->json([
                "status" => "success",
                "code" => 200,
                "message" => $message,
                "data" => $details->items(),
                "pagination" => [
                    "current_page" => $details->currentPage(),
                    "last_page" => $details->lastPage(),
                    "per_page" => $details->perPage(),
                    "total" => $details->total(),
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

/**
 * @OA\Get(
 *     path="/mob/merchendisher_mob/Customermerchendiserlist",
 *     summary="Export customer data for the authenticated merchandiser",
 *     description="Returns a URL to download customer data in a file for the currently authenticated merchandiser.",
 *     operationId="getCustomerDataFile",
 *     tags={"Shelves"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Response(
 *         response=200,
 *         description="Customer data exported successfully.",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Customer data exported successfully."),
 *             @OA\Property(
 *                 property="file_url",
 *                 type="string",
 *                 example="https://example.com/storage/customers/merchandiser_123_customers.csv"
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="An error occurred while exporting customer data.")
 *         )
 *     )
 * )
 */
public function getCustomerDataFile(): JsonResponse
{
    $fileUrl = $this->service->exportCustomerDataForMerchandiser();

    return response()->json([
        'status' => true,
        'message' => 'Customer data exported successfully.',
        'file_url' => asset($fileUrl),
    ]);
}

/**
 * @OA\Get(
 *     path="/mob/merchendisher_mob/shelve-list",
 *     summary="Get shelves assigned to the authenticated merchandiser",
 *     description="Generates and returns a file URL containing shelf data related to the logged-in merchandiser.",
 *     operationId="getShelvesByMerchandiser",
 *     tags={"Shelves"},
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Response(
 *         response=200,
 *         description="Shelves data file generated successfully.",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Shelves exported successfully."),
 *             @OA\Property(property="file_url", type="string", example="https://example.com/storage/shelves/user_123_shelves.csv")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Internal Server Error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="An error occurred while generating the shelves file.")
 *         )
 *     )
 * )
 */
    public function getShelvesByMerchandiser(ShelveService $shelveService)
{
    $response = $shelveService->getShelvesForLoggedInMerchandiser(auth()->user()->id);

    return response()->json([
        'status' => true,
        'message' => $response['message'],
        'file_url' => $response['file_url'],
    ]);
}
/**
 * @OA\Post(
 *     path="/web/merchendisher_web/shelve/bluckupload",
 *     summary="Import shelves from a file (CSV, TXT, XLSX, XLS)",
 *     tags={"Shelves"},
 *     security={{"bearerAuth":{}}},
 *     operationId="importShelves",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"file"},
 *                 @OA\Property(
 *                     property="file",
 *                     type="string",
 *                     format="binary",
 *                     description="The file to upload (csv, txt, xlsx, xls)"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Shelves import completed",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Shelves import completed"),
 *             @OA\Property(property="inserted_count", type="integer", example=10),
 *             @OA\Property(property="skipped_count", type="integer", example=2),
 *             @OA\Property(
 *                 property="failures",
 *                 type="array",
 *                 @OA\Items(type="string", example="Row 5: Missing shelf name")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error (missing or invalid file)",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="The file field is required."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 @OA\Property(
 *                     property="file",
 *                     type="array",
 *                     @OA\Items(type="string", example="The file must be a file of type: csv, txt, xlsx, xls.")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error during import",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Import failed"),
 *             @OA\Property(property="error", type="string", example="Unexpected format in file.")
 *         )
 *     )
 * )
 */
public function import(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:csv,txt,xlsx,xls',
    ]);

    $file = $request->file('file');

    try {
        $result = $this->service->importFromCsv($file);

        return response()->json([
            'message' => 'Shelves import completed',
            'inserted_count' => $result['inserted_count'],
            'skipped_count' => $result['skipped_count'],
            'failures' => $result['failures'],
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Import failed',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * @OA\Get(
 *     path="/api/merchendisher/shelves/export",
 *     summary="Export shelves as CSV or XLSX",
 *     operationId="exportShelves",
 *     tags={"Shelves"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="format",
 *         in="query",
 *         required=true,
 *         description="Export format (csv or xlsx)",
 *         @OA\Schema(
 *             type="string",
 *             enum={"csv", "xlsx"},
 *             example="csv"
 *         )
 *     ),
 *     @OA\Parameter(
 *         name="valid_from",
 *         in="query",
 *         required=false,
 *         description="Start date for filtering shelves (YYYY-MM-DD)",
 *         @OA\Schema(type="string", format="date", example="2023-01-01")
 *     ),
 *     @OA\Parameter(
 *         name="valid_to",
 *         in="query",
 *         required=false,
 *         description="End date for filtering shelves (YYYY-MM-DD)",
 *         @OA\Schema(type="string", format="date", example="2023-12-31")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Returns a downloadable CSV or XLSX file",
 *         content={
 *             @OA\MediaType(
 *                 mediaType="application/octet-stream",
 *                 @OA\Schema(type="string", format="binary")
 *             )
 *         }
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="No shelves found for the given date range",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="message", type="string", example="No shelves found for the given date range.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error (e.g., invalid format or date logic)",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 @OA\Property(
 *                     property="format",
 *                     type="array",
 *                     @OA\Items(type="string", example="The format field must be one of: csv, xlsx.")
 *                 )
 *             )
 *         )
 *     )
 * )
 */
public function exportShelves(Request $request)
{
    $request->validate([
        'format'     => 'required|in:csv,xlsx',
        'valid_from' => 'nullable|date',
        'valid_to'   => 'nullable|date|after_or_equal:valid_from',
    ]);
    $shelves = $this->service->getFilteredShelves(
        $request->valid_from,
        $request->valid_to
    );
    if ($shelves->isEmpty()) {
        return response()->json([
            'status'  => 'error',
            'message' => 'No shelves found for the given date range.'
        ], 404);
    }
    $allCustomerIds = $shelves
        ->pluck('customer_ids')
        ->map(fn ($ids) => is_array($ids) ? $ids : explode(',', $ids))
        ->flatten()
        ->unique()
        ->filter()
        ->values();
    $customerMap = CompanyCustomer::whereIn('id', $allCustomerIds)
        ->pluck('business_name', 'id');
    $allMerchandiserIds = $shelves
        ->pluck('merchendiser_ids')
        ->map(fn ($ids) => is_array($ids) ? $ids : explode(',', $ids))
        ->flatten()
        ->unique()
        ->filter()
        ->values();
    $merchandiserMap = Salesman::whereIn('id', $allMerchandiserIds)
        ->pluck('name', 'id');   
    $data = $shelves->map(function ($item) use ($customerMap, $merchandiserMap) {
    $customerIds = is_array($item->customer_ids)
        ? $item->customer_ids
        : explode(',', $item->customer_ids);
    $customerNames = collect($customerIds)
        ->map(fn ($id) => $customerMap[$id] ?? null)
        ->filter()
        ->implode(', ');
    $merchandiserIds = is_array($item->merchendiser_ids)
        ? $item->merchendiser_ids
        : explode(',', $item->merchendiser_ids);
    $merchandiserNames = collect($merchandiserIds)
        ->map(fn ($id) => $merchandiserMap[$id] ?? null)
        ->filter()
        ->implode(', ');     
        return [
            'Shelf Name'   => $item->shelf_name,
            'Code'         => $item->code,
            'Height (cm)'  => $item->height,
            'Width (cm)'   => $item->width,
            'Depth (cm)'   => $item->depth,
            'Valid From'   => $item->valid_from,
            'Valid To'     => $item->valid_to,
            'Customers'    => $customerNames,
            'Merchandisers'=> $merchandiserNames,
        ];
    });
    $fileName = 'shelves_list_' . now()->format('Y_m_d_H_i_s');
    if ($request->format === 'csv') {
        $fileName .= '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];
        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_keys($data->first()));
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };
        return Response::stream($callback, 200, $headers);
    } else {
        $fileName .= '.xlsx';
        return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            private $data;
            public function __construct($data) { $this->data = $data; }
            public function collection() { return $this->data; }
            public function headings(): array { return array_keys($this->data->first()); }
        }, $fileName, ExcelFormat::XLSX);
    }
}
}
