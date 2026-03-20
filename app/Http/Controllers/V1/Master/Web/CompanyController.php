<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\CompanyRequest;
use App\Http\Requests\V1\MasterRequests\Web\CompanyUpdateRequest;
use App\Http\Resources\V1\Master\Web\CompanyResource;
use App\Services\V1\MasterServices\Web\CompanyService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Helpers\LogHelper;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Company Web",
 *     description="API endpoints for managing companies"
 * )
 *
 * @OA\Schema(
 *     schema="CompanyRequest",
 *     type="object",
 *     title="Company Request",
 *     required={"company_name","service_type","company_type","status"},
 *     @OA\Property(property="company_name", type="string", maxLength=255, example="Riham Ltd"),
 *     @OA\Property(property="email", type="string", format="email", nullable=true, example="info@riham.com"),
 *     @OA\Property(property="address", type="string", nullable=true, example="123 Street, City"),
 *     @OA\Property(property="tin_number", type="string", nullable=true, example="TIN123456"),
 *     @OA\Property(property="vat", type="string", nullable=true, example="VAT654321"),
 *     @OA\Property(property="country_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="selling_currency", type="string", nullable=true, example="USD"),
 *     @OA\Property(property="purchase_currency", type="string", nullable=true, example="USD"),
 *     @OA\Property(property="toll_free_no", type="string", nullable=true, example="1800-123-456"),
 *     @OA\Property(property="logo", type="string", nullable=true, example="logo.png"),
 *     @OA\Property(property="website", type="string", nullable=true, example="https://riham.com"),
 *     @OA\Property(property="service_type", type="string", enum={"branch","warehouse"}, example="branch"),
 *     @OA\Property(property="company_type", type="string", enum={"trading","manufacturing"}, example="trading"),
 *     @OA\Property(property="status", type="string", enum={"active","inactive"}, example="active"),
 *     @OA\Property(property="module_access", type="array", @OA\Items(type="string"), nullable=true)
 * )
 */
class CompanyController extends Controller
{
    use ApiResponse;

    protected $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    /**
     * @OA\Get(
     *     path="/api/master/company/list_company",
     *     tags={"Company"},
     *     summary="Get all companies with optional filters and pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="company_name",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         description="Filter by company name"
     *     ),
     *     @OA\Parameter(
     *         name="selling_currency",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         description="Filter by selling currency"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of companies with pagination and optional filters",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseSuccess")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     )
     * )
     */
    public function index()
    {
        try {
            $perPage  = request()->get('per_page', 10);
            $dropdown = request()->boolean('dropdown', false);

            $filters = request()->only([
                'company_name',
                'email',
                'tin_number',
                'vat',
                'country_id',
                'selling_currency',
                'purchase_currency',
                'toll_free_no',
                'website',
                'service_type',
                'company_type',
                'status',
                'city',
                'address',
                'primary_contact',
            ]);

            $companies = $this->companyService->getAll(
                $perPage,
                $filters,
                $dropdown
            );

            /**
             * 🔹 DROPDOWN RESPONSE (No pagination)
             */
            if ($dropdown) {
                return response()->json([
                    'status'  => 'success',
                    'code'    => 200,
                    'message' => 'Companies fetched successfully',
                    'data'    => CompanyResource::collection($companies),
                ], 200);
            }

            /**
             * 🔹 PAGINATED RESPONSE (Country-style)
             */
            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Companies fetched successfully',
                'data'    => CompanyResource::collection($companies),
                'pagination' => [
                    'page'         => $companies->currentPage(),
                    'limit'        => $companies->perPage(),
                    'totalPages'   => $companies->lastPage(),
                    'totalRecords' => $companies->total(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    // public function index()
    // {
    //     try {
    //         $perPage = request()->get('per_page', 10);
    //         $filters = request()->only([
    //             'company_name',
    //             'email',
    //             'tin_number',
    //             'vat',
    //             'country_id',
    //             'selling_currency',
    //             'purchase_currency',
    //             'toll_free_no',
    //             'website',
    //             'service_type',
    //             'company_type',
    //             'status',
    //             'city',
    //             'address',
    //             // 'street',
    //             // 'landmark',
    //             // 'region',
    //             // 'sub_region',
    //             'primary_contact',
    //         ]);

    //         $companies = $this->companyService->getAll($perPage, $filters);
    //         return $this->success(
    //             CompanyResource::collection($companies),

    //             'Success',
    //             200,
    //             [
    //                 'current_page' => $companies->currentPage(),
    //                 'last_page'    => $companies->lastPage(),
    //                 'per_page'     => $companies->perPage(),
    //                 'total'        => $companies->total(),
    //             ]
    //         );
    //     } catch (\Exception $e) {
    //         return $this->fail($e->getMessage(), 500);
    //     }
    // }

    /**
     * @OA\Get(
     *     path="/api/master/company/global_search",
     *     tags={"Company"},
     *     summary="Search companies globally across multiple fields",
     *     description="Search in company_name, code, email, VAT, town, region, contact, etc. and return paginated results.",
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

            // 🔹 Call the service layer
            $companies = $this->companyService->search($perPage, $keyword);

            // 🔹 Return paginated data with meta info
            return response()->json([
                'status' => 'success',
                'message' => 'Search results',
                'data' => CompanyResource::collection($companies),
                'meta' => [
                    'current_page' => $companies->currentPage(),
                    'last_page'    => $companies->lastPage(),
                    'per_page'     => $companies->perPage(),
                    'total'        => $companies->total(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Search failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // public function global_search(Request $request)
    // {
    //     try {
    //         $perPage = $request->get('per_page', 10);
    //         $keyword = $request->get('query');

    //         $companies = $this->companyService->search($perPage, $keyword);

    //         return $this->success(
    //             CompanyResource::collection($companies),
    //             'Search results',
    //             200,
    //             [
    //                 'current_page' => $companies->currentPage(),
    //                 'last_page'    => $companies->lastPage(),
    //                 'per_page'     => $companies->perPage(),
    //                 'total'        => $companies->total(),
    //             ]
    //         );
    //     } catch (\Exception $e) {
    //         return $this->fail($e->getMessage(), 500);
    //     }
    // }


    /**
     * @OA\Post(
     *     path="/api/master/company/add_company",
     *     tags={"Company"},
     *     summary="Create a new company",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 
     *                 @OA\Property(property="company_code", type="string", example="CMP001"),
     *                 @OA\Property(property="company_name", type="string", example="ABC Trading Ltd."),
     *                 @OA\Property(property="email", type="string", format="email", example="info@abc.com"),
     *                 @OA\Property(property="tin_number", type="string", example="TIN123456"),
     *                 @OA\Property(property="vat", type="string", example="VAT78910"),
     *                 @OA\Property(property="country_id", type="integer", example=1, description="FK → tbl_country.id"),
     *                 @OA\Property(property="selling_currency", type="string", example="USD"),
     *                 @OA\Property(property="purchase_currency", type="string", example="UGX"),
     *                 @OA\Property(property="toll_free_no", type="string", example="+1800123456"),
     *
     *                 @OA\Property(
     *                     property="logo",
     *                     type="string",
     *                     format="binary",
     *                     description="Company logo image file"
     *                 ),
     *
     *                 @OA\Property(property="website", type="string", example="https://abc.com"),
     *                 @OA\Property(property="service_type", type="string", enum={"branch","warehouse"}, example="branch"),
     *                 @OA\Property(property="company_type", type="string", enum={"trading","manufacturing"}, example="trading"),
     *                 @OA\Property(property="status", type="string", enum={"0","1"}, example="active"),
     *                 @OA\Property(
     *                     property="module_access",
     *                     type="string",
     *                     nullable=true,
     *                     example={"inventory": true, "sales": true}
     *                 ),
     *                 @OA\Property(property="city", type="string", example="Kampala"),
     *                 @OA\Property(property="address", type="string", example="Central Town"),
     *                 @OA\Property(property="street", type="string", example="Main Street"),
     *                 @OA\Property(property="landmark", type="string", example="Near Central Park"),
     *                 @OA\Property(property="region",  type="integer", example=1),
     *                 @OA\Property(property="sub_region", type="integer", example=2),
     *                 @OA\Property(property="primary_contact", type="string", example="+256700123456")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Company created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseSuccess")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     )
     * )
     */
    // public function store(CompanyRequest $request): JsonResponse
    // {
    //     try {
    //         $data = $request->validated();
    //         $company = $this->companyService->create($data);
    //         return $this->success(new CompanyResource($company), "Company created successfully", 201);
    //     } catch (\Exception $e) {
    //         return $this->fail($e->getMessage(), 500);
    //     }
    // }
    public function store(CompanyRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('logo')) {
                $data['logo'] = $request->file('logo');
            }

            $company = $this->companyService->create($data);
            LogHelper::store(
                'settings',
                'company',
                'add',
                null,
                $company->toArray(),
                auth()->id()
            );

            return $this->success(
                new CompanyResource($company),
                "Company created successfully",
                201
            );
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/master/company/company/{id}",
     *     tags={"Company"},
     *     summary="Get a single company",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Company details",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseSuccess")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Company not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $company = $this->companyService->findById($id);
            return $this->success(new CompanyResource($company));
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/master/company/company/{id}",
     *     tags={"Company"},
     *     summary="Update a company",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="integer", example=174)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="company_code",
     *                     type="string",
     *                     example="CMP-001",
     *                     description="Optional company code"
     *                 ),
     *                 @OA\Property(
     *                     property="company_name",
     *                     type="string",
     *                     example="Acme Trading Ltd",
     *                     maxLength=255
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     format="email",
     *                     example="info@acme.com"
     *                 ),
     *                 @OA\Property(
     *                     property="tin_number",
     *                     type="string",
     *                     example="TIN-987654"
     *                 ),
     *                 @OA\Property(
     *                     property="vat",
     *                     type="string",
     *                     example="VAT-123456"
     *                 ),
     *                 @OA\Property(
     *                     property="country_id",
     *                     type="integer",
     *                     example=78,
     *                     description="Must exist in tbl_country"
     *                 ),
     *                 @OA\Property(
     *                     property="selling_currency",
     *                     type="string",
     *                     example="USD",
     *                     maxLength=5
     *                 ),
     *                 @OA\Property(
     *                     property="purchase_currency",
     *                     type="string",
     *                     example="EUR",
     *                     maxLength=5
     *                 ),
     *                 @OA\Property(
     *                     property="toll_free_no",
     *                     type="string",
     *                     example="1800-123-456"
     *                 ),
     *                 @OA\Property(
     *                     property="website",
     *                     type="string",
     *                     format="url",
     *                     example="https://acme.com"
     *                 ),
     *                 @OA\Property(
     *                     property="service_type",
     *                     type="string",
     *                     enum={"branch", "warehouse"},
     *                     example="branch"
     *                 ),
     *                 @OA\Property(
     *                     property="company_type",
     *                     type="string",
     *                     enum={"trading", "manufacturing"},
     *                     example="trading"
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="integer",
     *                     enum={0,1,2},
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="module_access",
     *                     type="object",
     *                     description="Module access permissions",
     *                     @OA\Property(
     *                         property="inventory",
     *                         type="boolean",
     *                         example=true
     *                     ),
     *                     @OA\Property(
     *                         property="sales",
     *                         type="boolean",
     *                         example=false
     *                     ),
     *                 ),
     *                 @OA\Property(
     *                     property="city",
     *                     type="string",
     *                     maxLength=255,
     *                     example="New York"
     *                 ),
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                     maxLength=255,
     *                     example="123 Main Street"
     *                 ),
     *                 @OA\Property(
     *                     property="primary_contact",
     *                     type="string",
     *                     maxLength=255,
     *                     example="John Doe"
     *                 ),
     *                 @OA\Property(
     *                     property="logo",
     *                     type="string",
     *                     format="binary",
     *                     description="Company logo file upload (jpg, jpeg, png, webp)"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Company updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseSuccess")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Company not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     )
     * )
     */

    public function update(CompanyUpdateRequest $request, $id)
    {

        try {
            $company = Company::findOrFail($id); // ✅ REQUIRED
            $data = $request->validated();
            if ($request->hasFile('logo')) {
                $data['logo'] = $request->file('logo');
            }
            $updatedCompany = $this->companyService->update($company, $data);

            return response()->json([
                'status'  => true,
                'message' => 'Company updated successfully',
                'data'    => $updatedCompany
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
       // public function update(CompanyRequest $request, $id)
    // {
    //     try {
    //         $company = $this->companyService->findById($id);
    //         $updated = $this->companyService->update($company, $request->validated());
    //         return $this->success(new CompanyResource($updated), "Company updated successfully");
    //     } catch (\Exception $e) {
    //         return $this->fail($e->getMessage(), 404);
    //     }
    // }


    /**
     * @OA\Delete(
     *     path="/api/master/company/company/{id}",
     *     tags={"Company"},
     *     summary="Delete a company",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Company deleted successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseSuccess")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Company not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     )
     * )
     */
    public function destroy(int $id)
    {
        try {
            $company = $this->companyService->delete($id);

            if (!$company) {
                return $this->fail("Company not found with ID: $id", 404);
            }

            return $this->success("Company deleted successfully", 200);
        } catch (\Exception $e) {
            return $this->fail("Failed to delete company", 500, [$e->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/master/company/bulk_upload",
     *     tags={"Company"},
     *     summary="Bulk upload companies from Excel file",
     *     description="Upload multiple companies using an Excel (.xlsx, .xls, or .csv) file.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Excel or CSV file containing company data"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Companies uploaded successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseSuccess")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid file format or data",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponseError")
     *     )
     * )
     */
    public function bulkUpload(Request $request): JsonResponse
    {

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048', // max 2MB
        ]);
        try {
            $result = $this->companyService->bulkUpload($request->file('file'));
            $data = json_decode($result->getContent(), true);
            $message = $data['message'] ?? null;
            return $this->success(null, $message);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
