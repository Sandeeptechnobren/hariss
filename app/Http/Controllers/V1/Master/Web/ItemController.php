<?php

namespace App\Http\Controllers\V1\Master\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MasterRequests\Web\ItemRequest;
use App\Http\Resources\V1\Master\Web\ItemResource;
use App\Models\Item;
use App\Exports\ItemWiseInvoiceExport;
use App\Http\Resources\V1\Agent_Transaction\InvoiceHeaderResource;
use App\Exports\ReturnExport;
use App\Models\Agent_Transaction\ReturnDetail;
use App\Services\V1\MasterServices\Web\ItemService;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\LogHelper;

/**
 * @OA\Schema(
 *     schema="Item",
 *     type="object",
 *     required={"category_id","sub_category_id","shelf_life","community_code","excise_code"},
 *     @OA\Property(property="name", type="string", maxLength=255, example="Sample Item"),
 *     @OA\Property(property="description", type="string", maxLength=255, example="This is a sample item"),
 *     @OA\Property(property="uom", type="integer", example=1),
 *     @OA\Property(property="upc", type="integer", example=123456),
 *     @OA\Property(property="category_id", type="integer", example=96),
 *     @OA\Property(property="sub_category_id", type="integer", example=28),
 *     @OA\Property(property="vat", type="number", format="double", example=18),
 *     @OA\Property(property="excies", type="number", format="double", example=5),
 *     @OA\Property(property="shelf_life", type="string", maxLength=50, example="12 Months"),
 *     @OA\Property(property="community_code", type="string", maxLength=100, example="COMM001"),
 *     @OA\Property(property="excise_code", type="string", maxLength=500, example="EXC001"),
 *     @OA\Property(property="status", type="integer", example=1)
 * )
 */
class ItemController extends Controller
{
    use ApiResponse;
    protected ItemService $service;
    public function __construct(ItemService $service)
    {
        $this->service = $service;
    }
    /**
     * @OA\Get(
     *     path="/api/master/items/list",
     *     tags={"Item"},
     *     summary="Get paginated list of items with filters",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="item_code", in="query", description="Filter by item code", @OA\Schema(type="string")),
     *     @OA\Parameter(name="item_name", in="query", description="Filter by item name", @OA\Schema(type="string")),
     *     @OA\Parameter(name="item_category_id", in="query", description="Filter by category ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="List of items",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Item")),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="page", type="integer"),
     *                 @OA\Property(property="limit", type="integer"),
     *                 @OA\Property(property="totalPages", type="integer"),
     *                 @OA\Property(property="totalRecords", type="integer")
     *             )
     *         )
     *     )
     * )
     */

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('limit', 50);
            $dropdown = filter_var($request->get('dropdown', false), FILTER_VALIDATE_BOOLEAN);
            $allData = filter_var($request->get('allData', false), FILTER_VALIDATE_BOOLEAN);

            $filters = $request->only(['category_id', 'status', 'warehouse_id']);

            if (!empty($filters['category_id'])) {

                if (is_string($filters['category_id'])) {
                    $filters['category_id'] = explode(',', $filters['category_id']);
                }

                $filters['category_id'] = (array) $filters['category_id']; 
            }
            $items = $this->service->getAll($perPage, $filters, $dropdown, $allData);

            if ($dropdown || $allData) {
                return response()->json([
                    'status' => 'success',
                    'code' => 200,
                    'data' => $items,
                ]);
            }

            $pagination = [
                'page' => $items->currentPage(),
                'limit' => $items->perPage(),
                'totalPages' => $items->lastPage(),
                'totalRecords' => $items->total(),
            ];

            return $this->success(
                ItemResource::collection($items),
                'Items fetched successfully',
                200,
                $pagination
            );
        } catch (\Exception $e) {
            return $this->fail('Failed to fetch items: ' . $e->getMessage(), 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/master/items/{id}",
     *     tags={"Item"},
     *     summary="Get single item",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Item details", @OA\JsonContent(ref="#/components/schemas/Item")),
     *     @OA\Response(response=404, description="Item not found")
     * )
     */
    public function show($uuid): JsonResponse
    {
        $item = $this->service->getById($uuid);
        return response()->json(['status' => 'success', 'code' => '200',  'data' => new ItemResource($item)]);
    }
    /**
     * @OA\Post(
     *     path="/api/master/items/add",
     *     tags={"Item"},
     *     summary="Create a new item",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Item")),
     *     @OA\Response(response=201, description="Item created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(ItemRequest $request): JsonResponse
    {
        try {
            $item = $this->service->create($request->validated());
            LogHelper::store(
                'master',
                'items',
                'add',
                null,
                $item->toArray()
            );
            return response()->json([
                'status' => 'success',
                'code'   => 200,
                'data'   => $item
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Put(
     *     path="/api/master/items/update/{uuid}",
     *     tags={"Item"},
     *     summary="Update an existing item",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Item UUID",
     *         @OA\Schema(type="string", example="722421cb-837e-460c-b8df-e35eeb8248af")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="name", type="string", example="ITEM 1"),
     *                 @OA\Property(property="description", type="string", example="Item description"),
     *                 @OA\Property(property="image", type="string", format="binary"),
     *                 @OA\Property(property="brand", type="integer", example=1),
     *                 @OA\Property(property="category_id", type="integer", example=9),
     *                 @OA\Property(property="sub_category_id", type="integer", example=12),
     *                 @OA\Property(property="item_weight", type="number", example=12.5),
     *                 @OA\Property(property="shelf_life", type="integer", example=12),
     *                 @OA\Property(property="volume", type="number", example=10.5),
     *                 @OA\Property(property="is_promotional", type="integer", enum={0,1}, example=0),
     *                 @OA\Property(property="is_taxable", type="integer", enum={0,1}, example=1),
     *                 @OA\Property(property="has_excies", type="integer", enum={0,1}, example=0),
     *                 @OA\Property(property="status", type="integer", enum={0,1}, example=1),
     *                 @OA\Property(property="commodity_goods_code", type="string", example="COM0001"),
     *                 @OA\Property(property="excise_duty_code", type="string", nullable=true, example=""),
     *
     *                 @OA\Property(
     *                     property="uoms",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="uom", type="integer", example=2),
     *                         @OA\Property(property="uom_type", type="string", enum={"primary","secondary","third","forth"}, example="secondary"),
     *                         @OA\Property(property="price", type="number", example=154),
     *                         @OA\Property(property="upc", type="number", example=12),
     *                         @OA\Property(property="is_stock_keeping", type="integer", enum={0,1}, example=1),
     *                         @OA\Property(property="keeping_quantity", type="integer", example=133),
     *                         @OA\Property(property="enable_for", type="string", example="sales,return"),
     *                         @OA\Property(property="status", type="integer", enum={0,1}, example=1)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Item updated successfully"),
     *     @OA\Response(response=404, description="Item not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, $uuid)
    {
        try {
            $validated = $request->validate([
                'name'          => 'sometimes|nullable|string|max:255',
                'description'   => 'sometimes|nullable|string|max:255',
                'image'         => 'nullable|file|image|mimes:jpg,jpeg,png,gif|max:2048',
                'brand'         => 'sometimes|integer|exists:tbl_brands,id',
                'category_id'   => 'sometimes|exists:item_categories,id',
                'sub_category_id' => 'sometimes|exists:item_sub_categories,id',
                'item_weight'   => 'sometimes|numeric',
                'shelf_life'    => 'sometimes|integer',
                'volume'        => 'sometimes|numeric',
                'is_promotional' => 'sometimes|integer|in:0,1',
                'caps_promo'     => 'sometimes|integer|in:0,1',
                'is_taxable'     => 'sometimes|integer|in:0,1',
                'has_excies'     => 'sometimes|integer|in:0,1',
                'status'         => 'sometimes|integer|in:0,1',
                'commodity_goods_code' => 'sometimes|nullable|string',
                'excise_duty_code'     => 'sometimes|nullable|string',
                'uoms'                 => 'sometimes|array|min:1',
                'uoms.*.id'            => 'sometimes|integer|exists:item_uoms,id',
                'uoms.*.uom'           => 'sometimes|integer',
                'uoms.*.uom_type'      => 'sometimes|string|in:primary,secondary,third,forth',
                'uoms.*.price'         => 'sometimes|numeric|min:0',
                'uoms.*.upc'           => 'sometimes|numeric|min:0',
                'uoms.*.is_stock_keeping' => 'sometimes|integer|in:0,1',
                'uoms.*.keeping_quantity' => 'sometimes|integer|min:0',
                'uoms.*.enable_for'    => 'sometimes|string|max:50',
                'uoms.*.status'        => 'sometimes|integer|in:0,1'
            ]);
            if ($request->hasFile('image')) {
                $validated['image'] = $request->file('image');
            }
            $previousItem = Item::with('itemUoms')
                ->where('uuid', $uuid)
                ->firstOrFail();
            $previousData = $previousItem->toArray();
            $updatedItem = $this->service->updateItem($validated, $uuid);
            $currentData = $updatedItem->toArray();
            LogHelper::store(
                'master',
                'items',
                'update',
                $previousData,
                $currentData
            );
            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Item updated successfully.',
                'data'    => new ItemResource($updatedItem),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * @OA\Delete(
     *     path="/api/master/items/{id}",
     *     tags={"Item"},
     *     summary="Soft delete an item",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Item deleted successfully"),
     *     @OA\Response(response=404, description="Item not found")
     * )
     */
    public function destroy($id): JsonResponse
    {
        try {
            $item = $this->service->getById($id);
            $previousData = $item->toArray();
            $this->service->delete($item);
            LogHelper::store(
                'master',
                'items',
                'delete',
                $previousData,
                null
            );
            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Item deleted successfully'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // /**
    //  * @OA\Get(
    //  *     path="/api/master/items/global-search",
    //  *     tags={"Item"},
    //  *     summary="Global search across items",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Parameter(
    //  *         name="search",
    //  *         in="query",
    //  *         required=false,
    //  *         description="Search term for items",
    //  *         @OA\Schema(type="string")
    //  *     ),
    //  *     @OA\Parameter(
    //  *         name="per_page",
    //  *         in="query",
    //  *         required=false,
    //  *         description="Number of results per page (default 10)",
    //  *         @OA\Schema(type="integer")
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Global search results",
    //  *         @OA\JsonContent(type="object",
    //  *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Item")),
    //  *             @OA\Property(property="pagination", type="object")
    //  *         )
    //  *     ),
    //  *     @OA\Response(response=500, description="Failed to perform search")
    //  * )
    //  */
    public function globalSearch(Request $request)
    {
        $query = $request->query('query', null);
        $perPage = $request->query('per_page', 50);
        $items = $this->service->globalSearch($perPage, $query);
        return response()->json([
            'status' => 200,
            'message' => 'Items retrieved successfully',
            'data' => $items->items(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'per_page'     => $items->perPage(),
                'total'        => $items->total(),
                'last_page'    => $items->lastPage(),
            ]
        ]);
    }
    /**
     * @OA\Post(
     *     path="/api/master/items/bulk_upload",
     *     tags={"Item"},
     *     summary="Upload bulk items via CSV file (no external library needed)",
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
     *                     description="CSV file containing item data"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Items uploaded successfully"),
     *     @OA\Response(response=400, description="Invalid file format or data"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function bulkUpload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx',
        ]);
        try {
            $result = $this->service->bulkUpload($request->file('file'));
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
    /**
     * @OA\Post(
     *     path="/api/master/items/update-status",
     *     summary="Update status for multiple items",
     *     description="Updates the status of multiple items by their IDs.",
     *     operationId="updateMultipleItemStatus",
     *     tags={"Item"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"item_ids", "status"},
     *             @OA\Property(
     *                 property="item_ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={10, 20, 30}
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="integer",
     *                 example=1
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Item statuses updated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Item statuses updated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"item_ids.0": {"The selected item_ids.0 is invalid."}}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Update failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Update failed.")
     *         )
     *     )
     * )
     */
    public function updateMultipleItemStatus(Request $request)
    {
        $request->validate([
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'integer|exists:items,id',
            'status' => 'required|integer',
        ]);
        $itemIds = $request->input('item_ids');
        $status = $request->input('status');
        $result = $this->service->updateItemsStatus($itemIds, $status);
        if ($result) {
            return response()->json(['success' => true, 'message' => 'Item statuses updated.'], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Update failed.'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/master/items/global_search",
     *     summary="Search items globally across multiple fields and relations",
     *     tags={"Item"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         description="Search keyword to match across multiple fields like name, code, brand, category, subcategory, created user, updated user, etc."
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
     *         description="Search results with pagination",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Search results"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Item")
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=0)
     *             )
     *         )
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

    /**
     * @OA\Post(
     *     path="/api/master/items/export",
     *     summary="Export items list",
     *     tags={"Item"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Item Export")
     * )
     */

    public function export()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $search  = request()->input('search');
        $filters = request()->input('filter', []);
        $columns = request()->input('columns', []);
        $format  = strtolower(request()->input('format', 'csv'));
        $format  = in_array($format, ['csv', 'xlsx']) ? $format : 'csv';
        $filename = 'items_' . now()->format('Ymd_His');
        $filePath = "exports/{$filename}";
        $query = \App\Models\Item::with([
            'itemCategory:id,category_name',
            'itemSubCategory:id,sub_category_name',
            'brandData:id,name',
            'itemUoms'
        ]);

        if (!empty($search)) {
            $like = '%' . strtolower($search) . '%';

            $query->where(function ($q) use ($like) {
                $q->orWhereRaw('LOWER(code) LIKE ?', [$like])
                    ->orWhereRaw('CAST(erp_code AS TEXT) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$like])
                    ->orWhereRaw('CAST(vat AS TEXT) LIKE ?', [$like])
                    ->orWhereRaw('CAST(shelf_life AS TEXT) LIKE ?', [$like]);
            });
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        // dd($query->count());
        $items = $query->get();
        if ($items->isEmpty()) {
            return response()->json(['message' => 'No data available for export'], 404);
        }
        $data = $items->map(function ($item) {
            // dd($item->itemUoms);
            $uom = $item->itemUoms;
            return [
                'erp_code'             => $item->erp_code,
                'code'                 => $item->code,
                'name'                 => $item->name,
                'category_name'        => optional($item->itemCategory)->category_name,
                'sub_category_name'    => optional($item->itemSubCategory)->sub_category_name,
                'shelf_life'           => $item->shelf_life,
                'brand'                => optional($item->brandData)->name,
                'item_weight'          => $item->item_weight,
                'volume'               => $item->volume,
                'is_taxable'           => $item->is_taxable ? 'Yes' : 'No',
                'has_excies'           => $item->has_excies ? 'Yes' : 'No',
                'commodity_goods_code' => $item->commodity_goods_code,
                'excise_duty_code'     => $item->excise_duty_code,

                // UOM (first row)
                // 'uom_name'             => $uom?->uom?->name,
                // 'upc'                  => optional($uom)->upc,
                // 'price'                => optional($uom)->price,
                // 'is_stock_keeping'     => optional($uom)->is_stock_keeping,
                // 'enable_for'           => optional($uom)->enable_for,
                 'uom_name' => $uom->pluck('uom.name')->filter()->implode(', '),
                 'upc'      => $uom->pluck('upc')->filter()->implode(', '),
                 'price'    => $uom->pluck('price')->filter()->implode(', '),
                 'is_stock_keeping' => $uom->pluck('is_stock_keeping')
                                        ->map(fn($v) => $v ? 'Yes' : 'No')
                                        ->implode(', '),

                'enable_for' => $uom->pluck('enable_for')->filter()->implode(', '),
            ];
        });
        $export = new \App\Exports\ItemExport($data, $columns);

        $filePath .= $format === 'xlsx' ? '.xlsx' : '.csv';

        \Maatwebsite\Excel\Facades\Excel::store(
            $export,
            $filePath,
            'public',
            $format === 'xlsx'
                ? \Maatwebsite\Excel\Excel::XLSX
                : \Maatwebsite\Excel\Excel::CSV
        );

        return response()->json([
            'status' => 'success',
            'download_url' => rtrim(config('app.url'), '/') . '/storage/app/public/' . $filePath,
        ], 200);
    }




    /**
     * @OA\Get(
     *     path="/api/master/items/item-invoices/{id}",
     *     tags={"Item"},
     *     summary="Get all invoices containing a specific item",
     *     description="Fetch all invoices (header + detail) where the provided item ID appears in invoice_details table.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the item to filter invoices by",
     *         @OA\Schema(type="integer", example=45)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with invoices list",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="count", type="integer", example=2),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No invoices found for this item",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="No invoices found for this item.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    // public function getItemInvoices(Request $request, $id): JsonResponse
    // {
    //     $perPage = (int) $request->get('limit', 50);
    //     try {
    //         $data = $this->service->getItemInvoices($perPage, (int)$id);
    //         if ($data->isEmpty()) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'No invoices found for this item.',
    //             ], 404);
    //         }
    //         $pagination = [
    //             'page' => $data->currentPage(),
    //             'limit' => $data->perPage(),
    //             'totalPages' => $data->lastPage(),
    //             'totalRecords' => $data->total(),
    //         ];

    //         return response()->json([
    //             'status' => 'success',
    //             'count' => $data->count(),
    //             // 'data' => $data->items(),
    //             'data'   => new InvoiceHeaderResource($data),
    //             'pagination' => $pagination,
    //         ]);
    //     } catch (Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    //     public function getItemInvoices(Request $request, $id): JsonResponse
    // {
    //     $perPage = (int) $request->get('limit', 50);

    //     try {
    //         $paginator = $this->service->getItemInvoices((int) $id, $perPage);

    //         if ($paginator->isEmpty()) {
    //             return response()->json([
    //                 'status'  => 'error',
    //                 'message' => 'No invoices found for this item.',
    //             ], 404);
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'count'  => $paginator->count(),
    //             'data'   => InvoiceHeaderResource::collection($paginator),
    //             'meta'   => [
    //                 'page'         => $paginator->currentPage(),
    //                 'limit'        => $paginator->perPage(),
    //                 'totalPages'   => $paginator->lastPage(),
    //                 'totalRecords' => $paginator->total(),
    //             ],
    //         ]);
    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function getItemInvoices(Request $request, $id): JsonResponse
    {
        $perPage = (int) $request->get('limit', 50);

        try {
            $paginator = $this->service->getItemInvoices((int) $id, $perPage);

            if ($paginator->isEmpty()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No invoices found for this item.',
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'count'  => $paginator->count(),
                'data'   => InvoiceHeaderResource::collection($paginator),
                'meta'   => [
                    'page'         => $paginator->currentPage(),
                    'limit'        => $paginator->perPage(),
                    'totalPages'   => $paginator->lastPage(),
                    'totalRecords' => $paginator->total(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/master/items/item-returns/{id}",
     *     tags={"Item"},
     *     summary="Get all returns containing a specific item",
     *     description="Fetch all returns (header + detail) where the provided item ID appears in return_details table.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the item to filter returns by",
     *         @OA\Schema(type="integer", example=45)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Number of records per page for pagination",
     *         @OA\Schema(type="integer", example=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with returns list",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="count", type="integer", example=2),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=50),
     *                 @OA\Property(property="totalPages", type="integer", example=5),
     *                 @OA\Property(property="totalRecords", type="integer", example=250)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No returns found for this item",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="No returns found for this item.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    public function getItemReturns(Request $request, $id): JsonResponse
    {
        $perPage = (int) $request->get('limit', 50); // pagination
        try {
            $data = $this->service->getItemReturns($perPage, (int)$id);

            if ($data->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No returns found for this item.',
                ], 404);
            }

            $pagination = [
                'page' => $data->currentPage(),
                'limit' => $data->perPage(),
                'totalPages' => $data->lastPage(),
                'totalRecords' => $data->total(),
            ];

            return response()->json([
                'status' => 'success',
                'count' => $data->count(),
                'data' => $data->items(),
                'pagination' => $pagination,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getItems(Request $request)
    {
        $categoryIds = $request->query('category_id');

        $ids = [];
        if (!empty($categoryIds)) {
            $ids = array_map('intval', explode(',', $categoryIds));
        }

        $query = Item::select(['id', 'name', 'erp_code'])->latest();

        if (!empty($ids)) {
            $query->whereIn('category_id', $ids);
        }

        $items = $query->get();

        return response()->json([
            'status' => 'Item fetch successfully',
            'count'  => $items->count(),
            'data'   => $items
        ]);
    }



    public function exportReturn(Request $request)
    {
        // 🔹 Read from URL query params
        $itemId = $request->query('item_id');
        $format = strtolower($request->query('format', 'xlsx')); // default xlsx

        /**
         * 🔹 Validation
         */
        if (empty($itemId)) {
            return response()->json([
                'message' => 'item_id is required'
            ], 422);
        }

        if (! in_array($format, ['xlsx', 'csv'])) {
            return response()->json([
                'message' => 'Invalid format. Allowed: xlsx, csv'
            ], 422);
        }

        $filename = 'returns_item_' . $itemId . '_' . now()->format('Ymd_His') . '.' . $format;
        $filePath = "exports/{$filename}";

        /**
         * 🔹 QUERY
         * Export ALL headers + details for this item
         */
        $data = ReturnDetail::query()
            ->with([
                // Header relations
                'returnHeader.warehouse:id,warehouse_name,warehouse_code',
                'returnHeader.route:id,route_name,route_code',
                'returnHeader.customer:id,name,osa_code',
                'returnHeader.salesman:id,name,osa_code',

                // Detail relations
                'item:id,name,erp_code',
                'returntype:id,return_type',
                // 'returnreason:id,name',
            ])
            ->where('item_id', (int) $itemId)
            ->whereNull('return_details.deleted_at')
            ->whereHas('returnHeader', fn($q) => $q->whereNull('deleted_at'))
            ->orderBy('header_id')   // keeps header-wise grouping
            ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'message' => 'No data found for this item'
            ], 404);
        }

        /**
         * 🔹 EXPORT
         */
        Excel::store(
            new ReturnExport($data),
            $filePath,
            'public',
            $format === 'xlsx'
                ? \Maatwebsite\Excel\Excel::XLSX
                : \Maatwebsite\Excel\Excel::CSV
        );

        /**
         * 🔹 RESPONSE
         */
        return response()->json([
            'status'        => 'success',
            'download_url'  => rtrim(config('app.url'), '/') . '/storage/app/public/' . $filePath,
            'format'        => $format,
            'item_id'       => (int) $itemId,
            'total_records' => $data->count(),
        ], 200);
    }



    public function exportItemInvoices(Request $request)
    {
        $itemId = (int) $request->query('item_id');
        $format = strtolower($request->query('format', 'xlsx'));

        if (!$itemId) {
            return response()->json([
                'message' => 'item_id is required'
            ], 422);
        }

        if (!in_array($format, ['xlsx', 'csv'])) {
            return response()->json([
                'message' => 'Invalid format. Allowed: xlsx, csv'
            ], 422);
        }

        $fromDate = $request->query('from_date')
            ? \Carbon\Carbon::parse($request->query('from_date'))->startOfDay()
            : null;

        $toDate = $request->query('to_date')
            ? \Carbon\Carbon::parse($request->query('to_date'))->endOfDay()
            : null;

        $filename = 'invoices_item_' . $itemId . '_' . now()->format('Ymd_His') . '.' . $format;
        $filePath = "exports/{$filename}";

        $export = new ItemWiseInvoiceExport(
            $itemId,
            $fromDate,
            $toDate
        );

        Excel::store(
            $export,
            $filePath,
            'public',
            $format === 'xlsx'
                ? \Maatwebsite\Excel\Excel::XLSX
                : \Maatwebsite\Excel\Excel::CSV
        );

        return response()->json([
            'status'       => 'success',
            'item_id'      => $itemId,
            'format'       => $format,
            'from_date'    => $fromDate,
            'to_date'      => $toDate,
            'download_url' => rtrim(config('app.url'), '/') . '/storage/app/public/' . $filePath,
        ], 200);
    }
}
