<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Exports\CompanyCustomerExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\CompanyCustomerRequest;
use App\Http\Requests\V1\MasterRequests\Web\UpdateCompanyCustomer;
use App\Http\Resources\V1\Master\Web\CompanyCustomerResource;
use App\Models\CompanyCustomer;
use Illuminate\Http\Request;
use App\Helpers\LogHelper;
use App\Services\V1\MasterServices\Web\CompanyCustomerService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Schema(
 *     schema="CompanyCustomer",
 *     type="object",
 *     required={"sap_code", "customer_code", "owner_name", "owner_no", "language", "bank_name", "bank_account_number", "creditday", "tin_no", "guarantee_name", "guarantee_amount", "guarantee_from", "guarantee_to", "totalcreditlimit", "region_id", "area_id", "vat_no", "threshold_radius", "dchannel_id", "status"},
 *     @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *     @OA\Property(property="sap_code", type="string", example="SAP123"),
 *     @OA\Property(property="customer_code", type="string", example="CUST001"),
 *     @OA\Property(property="business_name", type="string", nullable=true, example="ABC Traders"),
 *     @OA\Property(property="customer_type", type="string", enum={"0","1","2"}, example="1"),
 *     @OA\Property(property="owner_name", type="string", example="John Doe"),
 *     @OA\Property(property="owner_no", type="string", example="9876543210"),
 *     @OA\Property(property="is_whatsapp", type="string", enum={"0","1"}, example="1"),
 *     @OA\Property(property="whatsapp_no", type="string", nullable=true, example="9876543210"),
 *     @OA\Property(property="email", type="string", nullable=true, example="customer@example.com"),
 *     @OA\Property(property="language", type="string", example="English"),
 *     @OA\Property(property="contact_no2", type="string", nullable=true, example="9123456789"),
 *     @OA\Property(property="buyerType", type="string", enum={"0","1"}, example="0"),
 *     @OA\Property(property="road_street", type="string", nullable=true, example="MG Road"),
 *     @OA\Property(property="town", type="string", nullable=true, example="Bangalore"),
 *     @OA\Property(property="landmark", type="string", nullable=true, example="Near Metro Station"),
 *     @OA\Property(property="district", type="string", nullable=true, example="Bangalore Urban"),
 *     @OA\Property(property="balance", type="number", format="float", example=1500.75),
 *     @OA\Property(property="payment_type", type="string", enum={"0","1","2","3"}, example="1"),
 *     @OA\Property(property="bank_name", type="string", example="State Bank of India"),
 *     @OA\Property(property="bank_account_number", type="string", example="1234567890"),
 *     @OA\Property(property="creditday", type="string", example="30"),
 *     @OA\Property(property="tin_no", type="string", example="TIN12345"),
 *     @OA\Property(property="accuracy", type="string", nullable=true, example="95%"),
 *     @OA\Property(property="creditlimit", type="number", format="float", example=50000.00),
 *     @OA\Property(property="guarantee_name", type="string", example="Guarantee Person"),
 *     @OA\Property(property="guarantee_amount", type="number", format="float", example=10000.00),
 *     @OA\Property(property="guarantee_from", type="string", format="date", example="2025-01-01"),
 *     @OA\Property(property="guarantee_to", type="string", format="date", example="2025-12-31"),
 *     @OA\Property(property="totalcreditlimit", type="number", format="float", example=60000.00),
 *     @OA\Property(property="credit_limit_validity", type="string", format="date", nullable=true, example="2025-12-31"),
 *     @OA\Property(property="region_id", type="integer", example=2),
 *     @OA\Property(property="area_id", type="integer", example=5),
 *     @OA\Property(property="vat_no", type="string", example="VAT67890"),
 *     @OA\Property(property="longitude", type="string", nullable=true, example="77.5946"),
 *     @OA\Property(property="latitude", type="string", nullable=true, example="12.9716"),
 *     @OA\Property(property="threshold_radius", type="integer", example=50),
 *     @OA\Property(property="dchannel_id", type="integer", example=3),
 *     @OA\Property(property="status", type="integer", enum={0,1}, example=1),
 *     @OA\Property(property="created_user", type="integer", example=1),
 *     @OA\Property(property="updated_user", type="integer", example=2),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true)
 * )
 */
class CompanyCustomerController extends Controller
{
    protected $service;

    public function __construct(CompanyCustomerService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/master/companycustomer/list",
     *     summary="Get all company customers",
     *     tags={"Company Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of company customers",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/CompanyCustomer"))
     *     )
     * )
     */
    // public function index(Request $request): JsonResponse
    // {
    //     $perPage = $request->get('per_page', 50);
    //     $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
    //     $filters = $request->only(['region_id', 'status']);

    //     $customers = $this->service->getAll($perPage, $filters, $dropdown);
    //     if ($dropdown) {
    //         return response()->json([
    //             'status'  => 'success',
    //             'code'    => 200,
    //             'message' => 'Company Customer Dropdown Data Fetched Successfully',
    //             'data'    => $customers,
    //         ]);
    //     }
    //     return response()->json([
    //         'status'     => 'success',
    //         'code'       => 200,
    //         'message'    => 'Company Customer Data Fetched Successfully',
    //         'data'       => $customers->items(),
    //         'pagination' => [
    //             'current_page' => $customers->currentPage(),
    //             'last_page'    => $customers->lastPage(),
    //             'per_page'     => $customers->perPage(),
    //             'total'        => $customers->total(),
    //         ],
    //     ]);
    // }
    public function index(Request $request): JsonResponse
    {
        $perPage  = $request->get('per_page', 50);
        $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
        $filters  = $request->only(['region_id', 'status', 'area_id']);

        $customers = $this->service->getAll($perPage, $filters, $dropdown);

        if ($dropdown) {
            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Company Customer Dropdown Data Fetched Successfully',
                'data'    => $customers,
            ]);
        }

        return response()->json([
            'status'     => 'success',
            'code'       => 200,
            'message'    => 'Company Customer Data Fetched Successfully',
            'data'       => CompanyCustomerResource::collection($customers->items()),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page'    => $customers->lastPage(),
                'per_page'     => $customers->perPage(),
                'total'        => $customers->total(),
            ],
        ]);
    }

    public function globalSearch(Request $request)
    {
        $search = $request->input('query');
        $results = $this->service->globalSearch($search);

        return response()->json([
            'status'     => 'success',
            'code'       => 200,
            'message'    => 'Global search results retrieved successfully',
            'data'       => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'per_page'     => $results->perPage(),
                'total'        => $results->total(),
            ],

        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/master/companycustomer/create",
     *     summary="Create a new company customer",
     *     tags={"Company Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CompanyCustomer")),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/CompanyCustomer"))
     * )
     */
    // public function store(CompanyCustomerRequest $request): JsonResponse
    //     {
    //         $customer = $this->service->create($request->validated());
    //         return response()->json([
    //             'status' => 'success',
    //             'code' => 200,
    //             'message' => "Company Customer created successfully",
    //             'data' => $customer
    //         ]);
    //     }
public function store(CompanyCustomerRequest $request): JsonResponse
{
    $validated = $request->validated();
    $customer = $this->service->create($validated);

    if (isset($customer['status']) && $customer['status'] === false) {
        return response()->json([
            'status' => 'error',
            'code' => 500,
            'message' => $customer['message'],
            'error' => $customer['error'] ?? null,
        ]);
    }

    LogHelper::store(
        'master',                
        'company_customer',       
        'add',                    
        null,                      
        $customer->toArray(),      
        auth()->id()               
    );

    return response()->json([
        'status' => 'success',
        'code' => 200,
        'message' => 'Company Customer created successfully',
        'data' => $customer,
    ]);
}
    /**
     * @OA\Get(
     *     path="/api/master/companycustomer/{id}",
     *     summary="Get company customer by ID",
     *     tags={"Company Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Company customer details", @OA\JsonContent(ref="#/components/schemas/CompanyCustomer")),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */

    public function show(string $uuid): JsonResponse
    {
        $customer = $this->service->find($uuid);

        if (!$customer) {
            return response()->json([
                'status'  => 'error',
                'code'    => 404,
                'message' => 'Customer not found'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'data'    => $customer
        ]);
    }
    // public function show(int $id): JsonResponse
    // {
    //     $customer = $this->service->findById($id);
    //     if (!$customer) {
    //         return response()->json(['message' => 'Customer not found'], 404);
    //     }
    //     return response()->json($customer);
    // }

    /**
     * @OA\Put(
     *     path="/api/master/companycustomer/{id}/update",
     *     summary="Update company customer",
     *     tags={"Company Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CompanyCustomer")),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/CompanyCustomer")),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
public function update(UpdateCompanyCustomer $request, string $uuid): JsonResponse
{
    $customer = $this->service->find($uuid);
    if (!$customer) {
        return response()->json(['message' => 'Customer not found'], 404);
    }

    $previousData = $customer->toArray();
    $updatedCustomer = $this->service->update($customer, $request->validated());
    $currentData = $updatedCustomer ? $updatedCustomer->toArray() : null;

    LogHelper::store(
        'master',            
        'company_customer',   
        'update',             
        $previousData,         
        $currentData,           
        auth()->id()           
    );

    return response()->json($updatedCustomer);
}

    /**
     * @OA\Delete(
     *     path="/api/master/companycustomer/{id}/delete",
     *     summary="Delete company customer",
     *     tags={"Company Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Deleted successfully"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $customer = $this->service->findById($id);
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }
        $this->service->delete($customer);
        return response()->json(['message' => 'Customer deleted successfully']);
    }

    // /**
    //  * @OA\Get(
    //  *     path="/api/master/companycustomer/region/{regionId}",
    //  *     summary="Get customers by region",
    //  *     tags={"Company Customers"},
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(name="regionId", in="path", required=true, @OA\Schema(type="integer", example=2)),
    //  *     @OA\Response(response=200, description="Customers from region",
    //  *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/CompanyCustomer"))
    //  *     )
    //  * )
    //  */
    // public function getByRegion(int $regionId): JsonResponse
    // {
    //     return response()->json($this->service->getByRegion($regionId));
    // }

    // /**
    //  * @OA\Get(
    //  *     path="/api/master/companycustomer/area/{areaId}",
    //  *     summary="Get customers by area",
    //  *     tags={"Company Customers"},
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(name="areaId", in="path", required=true, @OA\Schema(type="integer", example=5)),
    //  *     @OA\Response(response=200, description="Customers from area",
    //  *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/CompanyCustomer"))
    //  *     )
    //  * )
    //  */
    // public function getByArea(int $areaId): JsonResponse
    // {
    //     return response()->json($this->service->getByArea($areaId));
    // }

    // /**
    //  * @OA\Get(
    //  *     path="/api/master/companycustomer/active",
    //  *     summary="Get all active customers",
    //  *     tags={"Company Customers"},
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Response(response=200, description="List of active customers",
    //  *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/CompanyCustomer"))
    //  *     )
    //  * )
    //  */
    // public function getActive(): JsonResponse
    // {
    //     return response()->json($this->service->getActive());
    // }


    /**
     * @OA\Post(
     *     path="/api/master/companycustomer/export",
     *     summary="Export company customer data in CSV or Excel format",
     *     tags={"Company Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Company customer data exported successfully"),
     *     @OA\Response(response=404, description="No data available for export")
     * )
     */
    // public function export()
    // {
    //     $filters = request()->input('filters', []);
    //     $format = strtolower(request()->input('format', 'csv'));
    //     $filename = 'company_customers_' . now()->format('Ymd_His');
    //     $filePath = "exports/{$filename}";

    //     $query = \DB::table('tbl_company_customer')
    //         ->leftJoin('tbl_region', 'tbl_region.id', '=', 'tbl_company_customer.region_id')
    //         ->leftJoin('tbl_areas', 'tbl_areas.id', '=', 'tbl_company_customer.area_id')
    //         ->leftJoin('outlet_channel', 'outlet_channel.id', '=', 'tbl_company_customer.distribution_channel_id')
    //         ->leftJoin('company_types', 'company_types.id', '=', 'tbl_company_customer.company_type')
    //         // ->leftJoin('tbl_business_type', 'tbl_business_type.id', '=', 'tbl_company_customer.business_type')
    //         ->select(
    //             'tbl_company_customer.sap_code',
    //             'tbl_company_customer.osa_code',
    //             'tbl_company_customer.business_name',
    //             'tbl_company_customer.language',
    //             'tbl_company_customer.town',
    //             'tbl_company_customer.landmark',
    //             'tbl_company_customer.district',
    //             'tbl_company_customer.payment_type',
    //             'tbl_company_customer.creditday',
    //             'tbl_company_customer.tin_no',
    //             'tbl_company_customer.creditlimit',

    //             // ✅ Renamed fields as per your request (bank_*)
    //             'tbl_company_customer.bank_guarantee_name',
    //             'tbl_company_customer.bank_guarantee_amount',
    //             'tbl_company_customer.bank_guarantee_from',
    //             'tbl_company_customer.bank_guarantee_to',

    //             'tbl_company_customer.totalcreditlimit',
    //             'tbl_company_customer.credit_limit_validity',

    //             // ✅ Names instead of IDs
    //             'tbl_region.region_name as region',
    //             'tbl_areas.area_name as area',
    //             'outlet_channel.outlet_channel as distribution_channel',

    //             \DB::raw("CASE 
    //                     WHEN tbl_company_customer.status = 1 THEN 'Active' 
    //                     ELSE 'Inactive' 
    //                 END as status"),

    //             // ✅ Company & Business type names
    //             // 'tbl_business_type.name as business_type',
    //             'tbl_company_customer.contact_number',
    //             'company_types.name as company_type'
    //         );
    //     if (!empty($filters)) {
    //         if (!empty($filters['status'])) {
    //             $query->where('tbl_company_customer.status', $filters['status']);
    //         }
    //         if (!empty($filters['region_id'])) {
    //             $query->where('tbl_company_customer.region_id', $filters['region_id']);
    //         }
    //         if (!empty($filters['area_id'])) {
    //             $query->where('tbl_company_customer.area_id', $filters['area_id']);
    //         }
    //         if (!empty($filters['search'])) {
    //             $query->where('tbl_company_customer.business_name', 'like', '%' . $filters['search'] . '%');
    //         }
    //     }
    //     $data = $query->get();
    //     if ($data->isEmpty()) {
    //         return response()->json(['message' => 'No data available for export'], 404);
    //     }
    //     $export = new CompanyCustomerExport($data);
    //     $filePath .= $format === 'xlsx' ? '.xlsx' : '.csv';

    //     $success = \Maatwebsite\Excel\Facades\Excel::store(
    //         $export,
    //         $filePath,
    //         'public',
    //         $format === 'xlsx'
    //             ? \Maatwebsite\Excel\Excel::XLSX
    //             : \Maatwebsite\Excel\Excel::CSV
    //     );

    //     if (!$success) {
    //         throw new \Exception(strtoupper($format) . ' export failed.');
    //     }
    //     $appUrl = rtrim(config('app.url'), '/');
    //     $fullUrl = $appUrl . '/storage/app/public/' . $filePath;

    //     return response()->json(['url' => $fullUrl], 200);
    // }
public function export()
{
    $filters  = request()->input('filters', []);
    $format   = strtolower(request()->input('format', 'csv'));
    $search   = request()->input('search');
    $fromDate = request()->input('from_date');
    $toDate   = request()->input('to_date');
    $columns  = request()->input('columns', []);

    $filename = 'company_customers_' . now()->format('Ymd_His');
    $filePath = "exports/{$filename}";
    $filePath .= $format === 'xlsx' ? '.xlsx' : '.csv';

    // ✅ Export class now handles query itself
    $export = new CompanyCustomerExport(
        $fromDate,
        $toDate,
        $search,
        $filters,
        $columns
    );

    $success = \Maatwebsite\Excel\Facades\Excel::store(
        $export,
        $filePath,
        'public',
        $format === 'xlsx'
            ? \Maatwebsite\Excel\Excel::XLSX
            : \Maatwebsite\Excel\Excel::CSV
    );

    if (!$success) {
        throw new \Exception(strtoupper($format) . ' export failed.');
    }

    $appUrl = rtrim(config('app.url'), '/');
    $fullUrl = $appUrl . '/storage/app/public/' . $filePath;

    return response()->json(['url' => $fullUrl], 200);
}
    /**
     * @OA\Post(
     *     path="/api/master/companycustomer/bulk-update-status",
     *     summary="Bulk update status for company customers",
     *     tags={"Company Customers"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids","status"},
     *             @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1,2,3}),
     *             @OA\Property(property="status", type="string", example="inactive")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bulk update successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Updated status for 3 customers successfully"),
     *             @OA\Property(property="updated_count", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request"
     *     )
     * )
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:tbl_company_customer,id',
            'status' => 'required',
        ]);

        $updatedCount = CompanyCustomer::whereIn('id', $validated['ids'])
            ->update(['status' => $validated['status']]);

        return response()->json([
            'status'        => 'success',
            'code'          => 200,
            'message'       => "Updated status for {$updatedCount} customers successfully",
            'updated_count' => $updatedCount,
        ], 200);
    }


    public function customer(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 50);

            // Determine customer type logic
            $customerType = $request->get('customer_type')
                ?? ($request->get('type') === 'distributor' ? 2 : 4);

            // Pagination + filtering via service
            $customers = $this->service->getByCustomerType($customerType, $perPage);

            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'message'    => 'Customer list fetched successfully',
                'data'       => CompanyCustomerResource::collection($customers->items()),
                'pagination' => [
                    'current_page' => $customers->currentPage(),
                    'last_page'    => $customers->lastPage(),
                    'per_page'     => $customers->perPage(),
                    'total'        => $customers->total(),
                ],
            ]);
        } catch (Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch customer list',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function activeTypeTwoCustomers()
    {
        $customers = CompanyCustomer::select('id','osa_code', 'business_name')
            ->where('customer_type', 2)
            ->where('status', 1)
            ->get();

        return response()->json([
            'status' => 'success',
            'count' => $customers->count(),
            'data' => $customers
        ], 200);
    }
}
