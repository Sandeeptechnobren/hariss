<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\CompanyTypeRequest;
use App\Http\Resources\V1\Settings\Web\CompanyTypeResource;
use App\Services\V1\Settings\Web\CompanyTypeService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="CompanyTypes",
 *     description="Company type management"
 * )
 */
class CompanyTypeController extends Controller
{
    use ApiResponse;
    protected CompanyTypeService $service;

    public function __construct(CompanyTypeService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/settings/company-types/list",
     *     tags={"CompanyTypes"},
     *     security={{"bearerAuth":{}}},
     *     summary="List company types with pagination & filters",
     *     @OA\Parameter(name="code", in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Paginated list of company types")
     * )
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 50);

        $filters = $request->only([
            'status',
            'code',
            'name',
            'dropdown'
        ]);

        // Convert dropdown param to boolean
        if ($request->has('dropdown')) {
            $filters['dropdown'] = filter_var(
                $request->dropdown,
                FILTER_VALIDATE_BOOLEAN
            );
        }

        $data = $this->service->all($perPage, $filters);

        /**
         * 🔹 DROPDOWN RESPONSE (no pagination)
         */
        if (!empty($filters['dropdown']) && $filters['dropdown'] === true) {
            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Company types fetched for dropdown',
                'data'    => $data
            ]);
        }

        /**
         * 🔹 NORMAL PAGINATED RESPONSE
         */
        return response()->json([
            'status'     => 'success',
            'code'       => 200,
            'message'    => 'Company types fetched successfully',
            'data'       => CompanyTypeResource::collection($data->items()),
            'pagination' => [
                'page'         => $data->currentPage(),
                'limit'        => $data->perPage(),
                'totalPages'   => $data->lastPage(),
                'totalRecords' => $data->total(),
            ]
        ]);
    }

    // public function index(Request $request)
    // {
    //     $perPage = $request->get('per_page', 50);
    //     $filters = $request->only(['status', 'code', 'name']);

    //     $data = $this->service->all($perPage, $filters);

    //     return response()->json([
    //         'status'     => 'success',
    //         'code'       => 200,
    //         'message'    => 'Company types fetched successfully',
    //         'data'       => CompanyTypeResource::collection($data->items()),
    //         'pagination' => [
    //             'page'         => $data->currentPage(),
    //             'limit'        => $data->perPage(),
    //             'totalPages'   => $data->lastPage(),
    //             'totalRecords' => $data->total(),
    //         ]
    //     ]);
    // }

    /**
     * @OA\Get(
     *     path="/api/settings/company-types/generate-code",
     *     tags={"CompanyTypes"},
     *     security={{"bearerAuth":{}}},
     *     summary="Generate unique ServiceType code",
     *     description="Returns a unique auto-generated CompanyType code which can be shown as readonly in UI",
     *     @OA\Response(
     *         response=200,
     *         description="Unique CompanyType code generated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Unique CompanyType code generated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="code", type="string", example="ST001")
     *             )
     *         )
     *     )
     * )
     */
    public function generateCode()
    {
        try {
            $code = $this->service->generateCode();

            return $this->success(
                data: ['code' => $code],
                message: 'Unique CompanyType code generated successfully'
            );
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/settings/company-types/add",
     *     tags={"CompanyTypes"},
     *     security={{"bearerAuth":{}}},
     *     summary="Create a company type",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="Trades"),
     *             @OA\Property(property="status", type="integer", enum={0,1})
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CompanyTypeRequest $request)
    {
        $companyType = $this->service->create($request->validated());

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Company type created successfully',
            'data'    => new CompanyTypeResource($companyType)
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/settings/company-types/show/{uuid}",
     *     tags={"CompanyTypes"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get single company type by UUID",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(string $uuid)
    {
        $companyType = $this->service->find($uuid);

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Company type fetched successfully',
            'data'    => new CompanyTypeResource($companyType)
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/settings/company-types/update/{uuid}",
     *     tags={"CompanyTypes"},
     *     security={{"bearerAuth":{}}},
     *     summary="Update a company type by UUID",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="Trades"),
     *             @OA\Property(property="status", type="integer", enum={0,1})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function update(CompanyTypeRequest $request, string $uuid)
    {
        $companyType = $this->service->updateByUuid($uuid, $request->validated());

        return response()->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Company type updated successfully',
            'data'    => new CompanyTypeResource($companyType)
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/settings/company-types/delete/{uuid}",
     *     tags={"CompanyTypes"},
     *     security={{"bearerAuth":{}}},
     *     summary="Delete company type by UUID",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(string $uuid)
    {
        try {
            $deleted = $this->service->deleteByUuid($uuid);

            if ($deleted) {
                return $this->success(
                    data: null,
                    message: 'Company Type deleted successfully',
                    code: 200
                );
            }

            return $this->fail(
                message: 'Failed to delete Company Type',
                code: 400
            );
        } catch (\Exception $e) {
            return $this->fail(
                message: $e->getMessage(),
                code: 404
            );
        }
    }
}
