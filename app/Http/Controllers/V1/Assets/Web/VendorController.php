<?php

namespace App\Http\Controllers\V1\Assets\Web;

use App\Http\Controllers\Controller;
use App\Services\V1\Assets\Web\VendorService;
use App\Http\Requests\V1\Assets\Web\VendorRequest;
use App\Http\Resources\V1\Assets\Web\VendorResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Helpers\LogHelper;
use App\Models\Vendor; 

/**
 * @OA\Schema(
 *     schema="Vendor",
 *     type="object",
 *     @OA\Property(property="name", type="string", example="ABC Suppliers"),
 *     @OA\Property(property="address", type="string", example="123 Main Street"),
 *     @OA\Property(property="contact", type="string", example="9876543210"),
 *     @OA\Property(property="email", type="string", example="abc@company.com"),
 *     @OA\Property(property="status", type="integer", example=0)
 * )
 */
class VendorController extends Controller
{
    use ApiResponse;

    protected VendorService $service;

    public function __construct(VendorService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/assets/vendor/list_vendors",
     *     tags={"Vendor"},
     *     summary="Get all vendors with pagination and filters",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="code", in="query", required=false, @OA\Schema(type="string", example="VN001")),
     *     @OA\Response(
     *         response=200,
     *         description="List of vendors",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Vendors fetched successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Vendor")),
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
        $perPage = $request->get('limit', 10);
        $filters = $request->only(['code', 'name', 'contact', 'email', 'status']);
        $vendors = $this->service->all($perPage, $filters);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Vendors fetched successfully',
            'data' => VendorResource::collection($vendors->items()),
            'pagination' => [
                'page' => $vendors->currentPage(),
                'limit' => $vendors->perPage(),
                'totalPages' => $vendors->lastPage(),
                'totalRecords' => $vendors->total(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/assets/vendor/vendor/{uuid}",
     *     tags={"Vendor"},
     *     summary="Get a single vendor by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string", example="6f9c1440-7f52-4f54-8f94-76adf1b1b111")),
     *     @OA\Response(response=200, description="Vendor details", @OA\JsonContent(ref="#/components/schemas/Vendor")),
     *     @OA\Response(response=404, description="Vendor not found")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        $vendor = $this->service->findByUuid($uuid);
        if (!$vendor) {
            return $this->fail('Vendor not found', 404);
        }
        return $this->success($vendor, 'Vendor fetched successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/assets/vendor/generate-code",
     *     tags={"Vendor"},
     *     summary="Generate unique vendor code",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Unique vendor code generated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Unique Vendor code generated successfully"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="vendor_code", type="string", example="VN001"))
     *         )
     *     )
     * )
     */
    public function generateCode(): JsonResponse
    {
        try {
            $vendor_code = $this->service->generateCode();
            return $this->success(['vendor_code' => $vendor_code], 'Unique Vendor code generated successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/assets/vendor/add_vendor",
     *     tags={"Vendor"},
     *     summary="Create a new vendor",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Vendor")),
     *     @OA\Response(response=201, description="Vendor created successfully"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function store(VendorRequest $request): JsonResponse
    {
        $vendor = $this->service->create($request->validated());
         if ($vendor) {
                        LogHelper::store(
                        'settings',                 
                        'assetvendor',                
                        'add',                   
                        null,                      
                        $vendor->getAttributes(),     
                        auth()->id()                
                    );
                }
        return $this->success($vendor, 'Vendor created successfully', 201);
    }

    /**
     * @OA\Put(
     *     path="/api/assets/vendor/update_vendor/{uuid}",
     *     tags={"Vendor"},
     *     summary="Update a vendor",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Vendor")),
     *     @OA\Response(response=200, description="Vendor updated successfully"),
     *     @OA\Response(response=404, description="Vendor not found"),
     *     @OA\Response(response=422, description="Validation failed")
     * )
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        $oldVendor = Vendor::where('uuid', $uuid)->first();
        $previousData = $oldVendor ? $oldVendor->getOriginal() : null;
        try {
            $vendor = $this->service->updateByUuid($uuid, $request->all());
            if ($vendor && $previousData) {
            LogHelper::store(
                'settings',                 
                'assetvendor',                  
                'update',             
                $previousData,              
                $vendor->getAttributes(),  
                auth()->id()                 
            );
        }
            return $this->success($vendor, 'Vendor updated successfully');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/assets/vendor/delete_vendor/{uuid}",
     *     tags={"Vendor"},
     *     summary="Delete a vendor",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Vendor deleted successfully"),
     *     @OA\Response(response=404, description="Vendor not found")
     * )
     */
public function destroy(string $uuid): JsonResponse
{
    $oldVendor = Vendor::where('uuid', $uuid)->first();
    $previousData = $oldVendor ? $oldVendor->getOriginal() : null;
    try {
        $this->service->deleteByUuid($uuid);
        if ($previousData) {
            LogHelper::store(
                'settings',    
                'assetvendor',        
                'delete',            
                $previousData,       
                null,                
                auth()->id()         
            );
        }

        return $this->success(null, 'Vendor deleted successfully');
    } catch (\Exception $e) {
        return $this->fail($e->getMessage(), 404);
    }
}
}
