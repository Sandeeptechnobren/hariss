<?php

namespace App\Http\Controllers\V1\Settings\Web;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\StoreDiscountSettingRequest;
use App\Http\Requests\V1\Settings\Web\UpdateDiscountSettingRequest;
use App\Http\Resources\V1\Settings\Web\DiscountSettingResource;
use App\Services\V1\Settings\Web\DiscountSettingService;
use App\Helpers\LogHelper;
use Throwable;

class DiscountSettingController extends Controller
{
    public function __construct(
        protected DiscountSettingService $service
    ) {}

    /**
     * List
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['name', 'status', 'dropdown']);
            $perPage = $request->get('limit', 10);

            // ✅ Important: normalize dropdown boolean
            $isDropdown = $request->boolean('dropdown');

            $data = $this->service->getAll($filters, $perPage, $isDropdown);
            // dd($isDropdown);
            // ✅ Dropdown response (NO pagination)
            if ($isDropdown) {
                return response()->json([
                    'success' => true,
                    'code'    => 200,
                    'message' => 'Discount settings retrieved successfully',
                    'data'    => DiscountSettingResource::collection($data)
                ], 200);
            }

            // ✅ Normal response (WITH pagination)
            return response()->json([
                'success' => true,
                'code'    => 200,
                'message' => 'Discount settings retrieved successfully',
                'data'    => DiscountSettingResource::collection($data->items()),
                'pagination' => [
                    'page'         => $data->currentPage(),
                    'limit'        => $data->perPage(),
                    'totalPages'   => $data->lastPage(),
                    'totalRecords' => $data->total(),
                ]
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'code'    => 500,
                'message' => $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }

    /**
     * Show
     */
    public function show($uuid): JsonResponse
    {
        try {
            $data = $this->service->getByUuid($uuid);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'code'    => 404,
                    'message' => 'Discount setting not found',
                    'data'    => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'code'    => 200,
                'message' => 'Discount setting retrieved successfully',
                'data'    => new DiscountSettingResource($data)
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'code'    => 500,
                'message' => $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }

    /**
     * Store
     */
    public function store(StoreDiscountSettingRequest $request): JsonResponse
    {
        try {
            $data = $this->service->create($request->validated());

            LogHelper::store(
                'settings',
                'discount_setting',
                'add',
                null,
                $data->getAttributes(),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'code'    => 201,
                'message' => 'Discount setting created successfully',
                'data'    => new DiscountSettingResource($data)
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'code'    => 500,
                'message' => $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }

    /**
     * Update
     */
    public function update(UpdateDiscountSettingRequest $request, $uuid): JsonResponse
    {
        try {
            $old = $this->service->getByUuid($uuid);

            if (!$old) {
                return response()->json([
                    'success' => false,
                    'code'    => 404,
                    'message' => 'Discount setting not found',
                    'data'    => null
                ], 404);
            }

            $previousData = $old->getOriginal();

            $data = $this->service->update($uuid, $request->validated());

            LogHelper::store(
                'settings',
                'discount_setting',
                'update',
                $previousData,
                $data->getAttributes(),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'code'    => 200,
                'message' => 'Discount setting updated successfully',
                'data'    => new DiscountSettingResource($data)
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'code'    => 500,
                'message' => $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }
}
