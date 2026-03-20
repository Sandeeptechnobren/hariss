<?php

namespace App\Http\Controllers\V1\Assets\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Assets\Web\StoreServiceTerritoryRequest;
use App\Http\Requests\V1\Assets\Web\UpdateServiceTerritoryRequest;
use App\Http\Resources\V1\Assets\Web\ServiceTerritoryHierarchyResource;
use App\Http\Resources\V1\Assets\Web\ServiceTerritoryResource;
use App\Services\V1\Assets\Web\ServiceTerritoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use App\Exports\ServiceTerritoryExport;
use App\Models\Warehouse;
use Maatwebsite\Excel\Facades\Excel;


class ServiceTerritoryController extends Controller
{
    protected ServiceTerritoryService $service;

    public function __construct(ServiceTerritoryService $service)
    {
        $this->service = $service;
    }


    /**
     * List with Pagination + Filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 50);
            $filters = $request->all();

            $result = $this->service->getAll($perPage, $filters);

            return response()->json([
                'status'  => 'success',
                'message' => 'Service territories fetched successfully',

                // 👇 Only transformed data
                'data' => ServiceTerritoryResource::collection($result->items()),

                // 👇 Standard pagination structure
                'pagination' => [
                    'total'        => $result->total(),
                    'current_page' => $result->currentPage(),
                    'per_page'     => $result->perPage(),
                    'last_page'    => $result->lastPage(),
                ],
            ]);
        } catch (Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch service territories',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Store
     */
    public function store(StoreServiceTerritoryRequest $request): JsonResponse
    {
        try {
            $record = $this->service->create($request->validated());

            return response()->json([
                'status'  => 'success',
                'message' => 'Service territory created successfully',
                'data'    => new ServiceTerritoryResource($record),
            ], 201);
        } catch (Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create service territory',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Show by UUID
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $record = $this->service->findByUuid($uuid);

            if (!$record) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Service territory not found for UUID: {$uuid}"
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Record fetched successfully',
                'data'    => new ServiceTerritoryResource($record)
            ]);
        } catch (Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch record',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function getViewData(string $uuid): JsonResponse
    {
        // dd($uuid);
        try {
            $result = $this->service->ViewData($uuid);

            if (!$result) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Service territory not found for UUID: {$uuid}"
                ], 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Record fetched successfully',
                'data'    => new ServiceTerritoryHierarchyResource((object) [
                    'osa_code'    => $result['territory']->osa_code,
                    'technician_id' => $result['territory']->technician_id,
                    'technician'    => $result['territory']->technician ?? null,
                    'hierarchy'   => $result['hierarchy']
                ])
            ]);
        } catch (Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch record',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update by UUID
     */

    public function update(UpdateServiceTerritoryRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $request->validated();

            $updatedRecord = $this->service->updateByUuid($uuid, $data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Service territory updated successfully',
                'data'    => new ServiceTerritoryResource($updatedRecord),
            ]);
        } catch (Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update record',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }



    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->service->deleteByUuid($uuid);

            return response()->json([
                'status'  => 'success',
                'message' => 'Service territory deleted successfully'
            ]);
        } catch (Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete record',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function exportTerritory(Request $request)
    {
        try {
            $uuid = $request->query('uuid');
            $format = strtolower($request->query('format', 'xlsx'));
            $extension = $format === 'csv' ? 'csv' : 'xlsx';

            if (!$uuid) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'UUID is required'
                ], 422);
            }

            $result = $this->service->ViewData($uuid);

            if (!$result) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Territory not found'
                ], 404);
            }

            $hierarchy = json_decode(json_encode($result['hierarchy']), true);

            // $hasRows = false;

            // foreach ($hierarchy as $region) {
            //     foreach ($region['area'] as $area) {
            //         if (!empty($area['warehouses'])) {
            //             $hasRows = true;
            //             break 2;
            //         }
            //     }
            // }

            // if (!$hasRows) {
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'No warehouses found to export'
            //     ], 422);
            // }

            $filename = 'service_territory_report_' . now()->format('Ymd_His') . '.' . $extension;
            $path = 'territoryreports/' . $filename;

            Excel::store(
                new ServiceTerritoryExport($hierarchy, $result['territory']),
                $path,
                'public',
                $format === 'csv'
                    ? \Maatwebsite\Excel\Excel::CSV
                    : \Maatwebsite\Excel\Excel::XLSX
            );

            $fullUrl = rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;

            return response()->json([
                'status'       => 'success',
                'message'      => 'Service territory exported successfully',
                'download_url' => $fullUrl,
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to export',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
