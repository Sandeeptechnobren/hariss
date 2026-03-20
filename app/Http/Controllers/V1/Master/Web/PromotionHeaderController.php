<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\PromotionHeaderRequest;
use App\Http\Requests\V1\MasterRequests\Web\PromotioUpdateRequest;
use App\Http\Resources\V1\Master\Web\PromotionHeaderResource;
use App\Http\Resources\V1\Master\Web\PromotionDataResource;
use App\Services\V1\MasterServices\Web\PromotionHeaderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Imports\CustomerExcelImport;
use Maatwebsite\Excel\Facades\Excel;
use DB;
use App\Models\AgentCustomer;
use App\Http\Resources\V1\Master\Web\AgentCustomerResource;
use App\Helpers\LogHelper;
use App\Models\PromotionHeader;

class PromotionHeaderController extends Controller
{
    protected PromotionHeaderService $service;

    public function __construct(PromotionHeaderService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/master/promotion-headers/list",
     *     tags={"Promotions"},
     *     summary="List promotions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="promtion_name", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Promotion list",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="code", type="integer"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="object")
     *             ),
     *             @OA\Property(property="pagination", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['id', 'promtion_name', 'status', 'limit']);
            $promotionHeaders = $this->service->list($filters);

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Promotion headers retrieved successfully',
                'data'    => PromotionHeaderResource::collection($promotionHeaders),
                'pagination' => [
                    'page'         => $promotionHeaders->currentPage(),
                    'limit'        => $promotionHeaders->perPage(),
                    'totalPages'   => $promotionHeaders->lastPage(),
                    'totalRecords' => $promotionHeaders->total(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/master/promotion-headers/create",
     *     tags={"Promotions"},
     *     summary="Create promotion",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"promotion_name","promotion_type","from_date","to_date","status","promotion_details"},
     *             @OA\Property(property="promotion_name", type="string"),
     *             @OA\Property(property="promotion_type", type="string"),
     *             @OA\Property(property="bundle_combination", type="string"),
     *             @OA\Property(property="from_date", type="string", format="date"),
     *             @OA\Property(property="to_date", type="string", format="date"),
     *             @OA\Property(property="status", type="integer"),
     *             @OA\Property(property="items", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="location", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="customer", type="array", @OA\Items(type="string")),
     *             @OA\Property(
     *                 property="offer_items",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="item_id", type="string"),
     *                     @OA\Property(property="uom", type="string")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="promotion_details",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="from_qty", type="integer"),
     *                     @OA\Property(property="to_qty", type="integer"),
     *                     @OA\Property(property="free_qty", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created")
     * )
     */
    public function store(PromotionHeaderRequest $request): JsonResponse
    {
        try {
            $promotionHeader = $this->service->create($request->validated());
            if ($promotionHeader) {
            LogHelper::store(
            '12',                  
            '31',             
            'add',                      
            null,                       
            $promotionHeader->getAttributes(),   
            auth()->id()                  
        );
    }

            return response()->json([
                'status'  => 'success',
                'code'    => 201,
                'message' => 'Promotion Header and Details created successfully',
                'data'    => new PromotionHeaderResource($promotionHeader)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/master/promotion-headers/show/{uuid}",
     *     tags={"Promotions"},
     *     summary="Show promotion by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Promotion detail")
     * )
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $promotionHeader = $this->service->show($uuid);

            if (!$promotionHeader) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 404,
                    'message' => 'Promotion header not found'
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Promotion header retrieved successfully',
                'data'    => new PromotionHeaderResource($promotionHeader)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/master/promotion-headers/{uuid}",
     *     tags={"Promotions"},
     *     summary="Update promotion by UUID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(PromotioUpdateRequest $request, string $uuid): JsonResponse
    {

        $oldPromotion = PromotionHeader::where('uuid', $uuid)->first();
        $previousData = $oldPromotion ? $oldPromotion->getOriginal() : null;

        try {
            $promotionHeader = $this->service->update($uuid, $request->validated());

            if ($promotionHeader && $previousData) {
            LogHelper::store(
                '12',                      
                '31',                     
                'update',                       
                $previousData,                    
                $promotionHeader->getAttributes(),  
                auth()->id()                        
            );
        }

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Promotion header updated successfully',
                'data'    => new PromotionHeaderResource($promotionHeader)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function getByWarehouse(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');

        if (!$warehouseId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'warehouse_id is required'
            ], 422);
        }

        $data = $this->service->getByWarehouseId((int) $warehouseId);

        return response()->json([
            'status' => 'success',
            'count'  => $data->count(),
            'data'   => PromotionDataResource::collection($data)
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file'
        ]);

        $import = new CustomerExcelImport();
        Excel::import($import, $request->file('file'));

        if (empty($import->rows) || $import->rows->isEmpty()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Uploaded file is empty'
            ], 422);
        }

        $customerCodes = $import->rows
            ->pluck(0)
            ->map(fn($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->values();

        if ($customerCodes->isEmpty()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No customer codes found in file'
            ], 422);
        }

        $customers = DB::table('agent_customers as ac')
            ->leftJoin('tbl_warehouse as w', 'w.id', '=', 'ac.warehouse')
            ->whereIn('ac.osa_code', $customerCodes)
            ->select(
                'ac.id',
                'ac.osa_code',
                'ac.name',
                'w.warehouse_name',
                'w.warehouse_code'
            )
            ->get();

        $foundCodes = $customers->pluck('osa_code')->toArray();
        $missingCodes = $customerCodes->diff($foundCodes)->values();

        return response()->json([
            'status' => 'success',
            'code'   => 200,
            'data'   => [
                'uploaded_customer_ids' => $customers->pluck('id')->values(),
                'customer_details'      => $customers,
                'missing_customer_codes' => $missingCodes
            ]
        ], 200);
    }
    public function getCustomerDetails(Request $request)
    {
        $customerIds = array_filter(
            array_map('intval', explode(',', $request->customer_id))
        );

        validator(
            ['customer_id' => $customerIds],
            [
                'customer_id'   => 'required|array|min:1',
                'customer_id.*' => 'integer|exists:agent_customers,id',
            ]
        )->validate();
        $customers = AgentCustomer::with([
            'customertype:id,code,name',
            'route:id,route_code,route_name',
            'outlet_channel:id,outlet_channel_code,outlet_channel',
            'category:id,customer_category_code,customer_category_name',
            'subcategory:id,customer_sub_category_code,customer_sub_category_name',
            'getWarehouse:id,warehouse_code,warehouse_name'
        ])
            ->whereIn('id', $customerIds)
            ->get();

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Customer details fetched successfully',
            'data'    => AgentCustomerResource::collection($customers),
        ], 200);
    }


    // public function getApplicablePromotions(Request $request): JsonResponse
    // {
    //     $request->validate([
    //         'customer_id'                => 'required|integer',
    //         'warehouse_id'            => 'nullable|integer',
    //         'items'                   => 'required|array|min:1',
    //         'items.*.item_id'         => 'required|integer',
    //         'items.*.item_uom_id'     => 'required|integer',
    //         'items.*.item_qty'        => 'required|integer|min:1',
    //     ]);

    //     $data = $this->service->fetchApplicablePromotions($request->all());
    //     // dd($data->toArray());
    //     return response()->json([
    //         'status' => 'success',
    //         'data'   => $data
    //     ]);
    // }
    public function getApplicablePromotions(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id'            => 'nullable|integer',
            'warehouse_id'           => 'nullable|integer',
            'items'                  => 'nullable|array',
            'items.*.item_id'        => 'nullable|integer',
            'items.*.item_uom_id'    => 'nullable|integer',
            'items.*.item_qty'       => 'nullable|integer',
            'per_page'               => 'nullable|integer|min:1',
            'page'                   => 'nullable|integer|min:1',
        ]);

        $payload   = $request->all();
        $paginator = $this->service->fetchApplicablePromotions($payload);

        // 🔹 SAFE items handling
        $items = is_array($request->items) ? $request->items : [];

        /**
         * 🔹 Transform paginated promotion records
         */
        $itemPromotionInfo = collect($paginator->items())->map(function ($promo) use ($items) {

            $detail = $promo->promotionDetails->first();

            return [
                'id'                 => $promo->id,
                'name'               => $promo->promotion_name,
                'order_item_type'    => '',
                'offer_item_type'    => '',
                'discount_type'      => $promo->promotion_type === 'quantity' ? '1' : '2',
                'FocQty'             => $detail?->free_qty ?? 0,
                'type'               => '',
                'is_key_combination' => 0,

                // 🔹 Map items ONLY if present
                'promotion_items' => collect($items)->map(function ($item) {
                    return [
                        'id'          => $item['item_id'] ?? null,
                        'item_code'   => '',
                        'item_name'   => '',
                        'item_uom_id' => $item['item_uom_id'] ?? null,
                        'name'        => 'PCS',
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'status'     => true,
            'data'       => [
                'itemPromotionInfo' => $itemPromotionInfo
            ],
            'message'    => 'Item price.',
            'errors'     => [],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'has_more'     => $paginator->hasMorePages(),
            ]
        ]);
    }

    public function getAppliedPromotions(Request $request, PromotionHeaderService $service)
    {
        $request->validate([
            'customer_id'            => 'nullable|integer',
            'warehouse_id'           => 'required|integer',
            'items'                  => 'required|array|min:1',
            'items.*.item_id'        => 'required|integer',
            'items.*.item_uom_id'    => 'required|integer',
            'items.*.item_qty'       => 'required|integer|min:1',
        ]);

        $promotions = $service->getApplicablePromotions($request->all());

        return response()->json([
            'status'     => true,
            'data'       => [
                'itemPromotionInfo' => $promotions
            ],
            'message'    => 'Highest priority promotion applied successfully.'
        ]);
    }

    public function globalSearch(Request $request): JsonResponse
    {
        // dd($request);
        $perPage    = (int) $request->get('per_page', 10);
        $searchTerm = $request->get('query');

        $data = $this->service->globalSearch($perPage, $searchTerm);

        return response()->json([
            'status' => 'success',
            'data'    => PromotionHeaderResource::collection($data),
            'pagination' => [
                'page'         => $data->currentPage(),
                'limit'        => $data->perPage(),
                'totalPages'   => $data->lastPage(),
                'totalRecords' => $data->total(),
            ]
        ]);
    }
}
