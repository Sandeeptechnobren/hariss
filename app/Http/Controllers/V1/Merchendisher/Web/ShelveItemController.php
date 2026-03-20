<?php

namespace App\Http\Controllers\V1\Merchendisher\Web;

use App\Http\Controllers\Controller;
use App\Models\ShelfItem;
use App\Models\Shelve;
use App\Services\V1\Merchendisher\Web\ShelveItemService;
use App\Http\Requests\V1\Merchendisher\Web\StoreShelveItemRequest;
use App\Http\Requests\V1\Merchendisher\Web\UpdateShelveItemRequest;
use App\Http\Resources\V1\Merchendisher\Web\ShelveItemResource;
use App\Http\Resources\V1\Merchendisher\Web\DamageResource;
use App\Http\Resources\V1\Merchendisher\Web\ExpiryResource;
use App\Http\Resources\V1\Merchendisher\Web\ViewStockResource;
use App\Helpers\ResponseHelper;

class ShelveItemController extends Controller
{
    protected $service;

    public function __construct(ShelveItemService $service)
    {
        $this->service = $service;
    }

/**
 * @OA\Get(
 *     path="/api/merchendisher/shelve_item/list/{shelf_uuid}",
 *     tags={"Shelf Items"},
 *     summary="Get shelf items by shelf UUID",
 *     description="Returns a paginated list of shelf items filtered by shelf UUID",
 *
 *     @OA\Parameter(
 *         name="shelf_uuid",
 *         in="path",
 *         required=true,
 *         description="Shelf UUID",
 *         @OA\Schema(
 *             type="string",
 *             format="uuid",
 *             example="9d1f8c12-9f22-4e91-b99f-8899abcd1234"
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Shelf item list retrieved successfully"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Shelf not found"
 *     )
 * )
 */
public function index(string $shelf_uuid)
{
    $shelfId = $this->getShelfIdByUuid($shelf_uuid);
    $data = $this->service->list(50, $shelfId);
    return ResponseHelper::paginatedResponse(
           'Shelve item fetched successfully',
           ShelveItemResource::class,
           $data
    );
}

/**
 * @OA\Post(
 *     path="/api/merchendisher/shelve_item/add",
 *     tags={"Shelf Items"},
 *     summary="Create a new shelf item",
 *     description="Store a new shelf item into database",
 * 
 *     @OA\RequestBody(
 *         required=true,
 *         description="Payload for creating shelf item",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="shelf_id", type="integer", example=1),
 *             @OA\Property(property="product_id", type="integer", example=101),
 *             @OA\Property(property="capacity", type="number", example=50),
 *             @OA\Property(property="status", type="string", example="active"),
 *             @OA\Property(property="total_no_of_fatching", type="number", example=5)
 *         )
 *     ),
 * 
 *     @OA\Response(
 *         response=201,
 *         description="Shelf item created successfully"
 *     )
 * )
 */
public function store(StoreShelveItemRequest $request)
{
    $item = $this->service->create($request->validated());
    return new ShelveItemResource($item);
}

/**
 * @OA\Get(
 *     path="/api/merchendisher/shelve_item/show/{uuid}",
 *     tags={"Shelf Items"},
 *     summary="Get single shelf item",
 *     description="Fetch one shelf item by ID",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Shelf item ID"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Shelf item retrieved successfully"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Shelf item not found"
 *     )
 * )
 */
public function show($uuid)
{
    $item = ShelfItem::where('uuid', $uuid)->firstOrFail();
    return new ShelveItemResource($item);
}

/**
 * @OA\Put(
 *     path="/api/merchendisher/shelve_item/update/{uuid}",
 *     tags={"Shelf Items"},
 *     summary="Update shelf item",
 *     description="Update details of an existing shelf item",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Shelf item ID"
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         description="Updated data for shelf item"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Shelf item updated successfully"
 *     )
 * )
 */
public function update(UpdateShelveItemRequest $request, $uuid)
{
    $item = $this->service->update($uuid, $request->validated());
    return new ShelveItemResource($item);
}

/**
 * @OA\Delete(
 *     path="/api/merchendisher/shelve_item/destroy/{uuid}",
 *     tags={"Shelf Items"},
 *     summary="Delete shelf item",
 *     description="Soft delete a shelf item",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Shelf item ID"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Shelf item deleted successfully"
 *     )
 * )
 */
public function destroy($uuid)
    {
        $this->service->delete($uuid);
        return response()->json(['message' => 'Deleted Successfully']);
    }
/**
 * @OA\Get(
 *     path="/api/merchendisher/shelve_item/damage-list/{shelf_uuid}",
 *     tags={"Shelf Items"},
 *     summary="Get damage list by shelf UUID",
 *     description="Returns a paginated list of damaged shelf items based on shelf UUID",
 *
 *     @OA\Parameter(
 *         name="shelf_uuid",
 *         in="path",
 *         required=true,
 *         description="Shelf UUID",
 *         @OA\Schema(
 *             type="string",
 *             format="uuid",
 *             example="9d1f8c12-9f22-4e91-b99f-8899abcd1234"
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Damage list retrieved successfully"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Shelf not found"
 *     )
 * )
 */
public function damagelist(string $shelf_uuid)
{
    $shelfId = $this->getShelfIdByUuid($shelf_uuid);
    $data = $this->service->damagelist(50, $shelfId);
    return ResponseHelper::paginatedResponse(
        'Damage fetched successfully',
        DamageResource::class,
        $data
    );
}   
/**
 * @OA\Get(
 *     path="/api/merchendisher/shelve_item/expiry-list/{shelf_uuid}",
 *     tags={"Shelf Items"},
 *     summary="Get expiry list by shelf UUID",
 *     description="Returns a paginated list of expiry shelf items based on shelf UUID",
 *
 *     @OA\Parameter(
 *         name="shelf_uuid",
 *         in="path",
 *         required=true,
 *         description="Shelf UUID",
 *         @OA\Schema(
 *             type="string",
 *             format="uuid",
 *             example="9d1f8c12-9f22-4e91-b99f-8899abcd1234"
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Expiry list retrieved successfully"
 *     )
 * )
 */
public function expirylist(string $shelf_uuid)
{
    $shelfId = $this->getShelfIdByUuid($shelf_uuid);
    $data = $this->service->expiry(50, $shelfId);
    return ResponseHelper::paginatedResponse(
        'Expiry fetched successfully',
        ExpiryResource::class,
        $data
    );
}
/**
 * @OA\Get(
 *     path="/api/merchendisher/shelve_item/viewstock-list/{shelf_uuid}",
 *     tags={"Shelf Items"},
 *     summary="Get view stock list by shelf UUID",
 *     description="Returns a paginated view stock list based on shelf UUID",
 *
 *     @OA\Parameter(
 *         name="shelf_uuid",
 *         in="path",
 *         required=true,
 *         description="Shelf UUID",
 *         @OA\Schema(
 *             type="string",
 *             format="uuid",
 *             example="9d1f8c12-9f22-4e91-b99f-8899abcd1234"
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="View stock list retrieved successfully"
 *     )
 * )
 */
public function viewstock(string $shelf_uuid)
{
    $shelfId = $this->getShelfIdByUuid($shelf_uuid);
    $data = $this->service->viewstock(50, $shelfId);
    return ResponseHelper::paginatedResponse(
        'View stock fetched successfully',
        ViewStockResource::class,
        $data
    );
}
protected function getShelfIdByUuid(?string $uuid): ?int
{
    if (empty($uuid)) {
        return null;
    }

    return Shelve::where('uuid', $uuid)->value('id');
}
}
