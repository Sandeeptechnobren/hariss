<?php

namespace App\Http\Controllers\V1\Agent_Transaction;

use App\Http\Controllers\Controller;
use App\Exports\TargetCommisionaDummyCSV;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelExcel;
use App\Services\V1\Agent_Transaction\AgentTargetService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Exports\AgentTargetExport;
use App\Models\ImportTempFile;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use File;
use URL;
use Illuminate\Support\Facades\Storage;
use App\Imports\TargetCommitionImport;

class AgentTargetController extends Controller
{
    protected $service;

    public function __construct(AgentTargetService $service)
    {
        $this->service = $service;
    }

    public function getFields(): JsonResponse
    {
        try {
            // $export = new TargetCommisionaDummyCSV();

            // return response()->json(['status' => 200, 'message' => 'Fields fetched successfully','data' => ['fields' => $export->headings()]], 200);
            $mappingarray = array("Item Code", "Item Name", "Category", "Month", "Year");
            return response()->json(['status' => 200, 'message' => 'Fields fetched successfully', 'data' => ['fields' => $mappingarray]], 200);

            // return prepareResult(true, $mappingarray, [], "Fields fetched successfully.", $this->success);
        } catch (\Exception $e) {
            return response()->json(['status' => 422, 'message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
        }
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls,csv,txt'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'data' => []
            ], 422);
        }

        $errors = [];

        try {

            $map_key_value = $request->map_key_value;
            $map_key_value_array = json_decode($map_key_value, true);


            $fileObj = $request->file('file');

            $content = file_get_contents($fileObj->getRealPath());
            $lines = explode("\n", $content);

            // ✅ GET HEADER
            $heading_array_line = isset($lines[0]) ? $lines[0] : '';
            $heading_array = str_getcsv($heading_array_line);
            $heading_array = array_map('trim', $heading_array);


            // ❌ HEADER EMPTY
            if (!$heading_array) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid file header',
                    'data' => [],
                    'errors' => []
                ], 400);
            }

            // ❌ MAPPING EMPTY
            if (!$map_key_value_array) {
                return response()->json([
                    'status' => false,
                    'message' => 'Mapping not provided',
                    'data' => [],
                    'errors' => []
                ], 400);
            }

            // ✅ REQUIRED HEADERS CHECK
            $requiredHeaders = ['Item Code', 'Item Name', 'Category', 'Month', 'Year'];
            $missingHeaders = array_diff($requiredHeaders, $heading_array);


            if (!empty($missingHeaders)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Missing headers: ' . implode(',', $missingHeaders)
                ], 400);
            }

            // ✅ FIND YEAR INDEX
            $yearIndex = array_search('Year', $heading_array);

            if ($yearIndex === false) {
                return response()->json([
                    'status' => false,
                    'message' => 'Year column not found'
                ], 400);
            }

            // ✅ EXTRACT WAREHOUSE HEADERS
            $warehouseHeaders = array_slice($heading_array, $yearIndex + 1);

            if (empty($warehouseHeaders)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No warehouse columns found after Year'
                ], 400);
            }

            $warehouseMap = [];

            foreach ($warehouseHeaders as $whCode) {

                $warehouse = Warehouse::where('warehouse_code', $whCode)->first();
                // dd($warehouseHeaders);
                if (!$warehouse) {
                    return response()->json([
                        'status' => false,
                        'message' => "Invalid warehouse code: $whCode"
                    ], 400);
                }

                $warehouseMap[$whCode] = $warehouse->id;
            }
            // dd($warehouseMap);
            // ✅ IMPORT CALL
            $import = new \App\Imports\TargetCommitionImport(
                $request->skipduplicate,
                $map_key_value_array,
                $heading_array,
                $warehouseMap
            );

            $import->import($fileObj);

            // ✅ SUCCESS HANDLING
            $succussrecords = 0;
            $successfileids = 0;

            if ($import->successAllRecords()) {
                $succussrecords = count($import->successAllRecords());

                $data = json_encode($import->successAllRecords());

                $fileName = time() . '_datafile.txt';

                File::put(storage_path() . '/app/tempimport/' . $fileName, $data);

                $importtempfiles = new ImportTempFile;
                $importtempfiles->FileName = $fileName;
                $importtempfiles->save();

                $successfileids = $importtempfiles->id;
            }

            // ✅ ERROR HANDLING
            $errorrecords = 0;
            $errror_array = [];

            if ($import->failures()) {

                foreach ($import->failures() as $failure) {

                    if ($failure->row() != 1) {

                        $error_msg = isset($failure->errors()[0]) ? $failure->errors()[0] : '';

                        if ($error_msg != "") {

                            $error_result = [];
                            $error_row_loop = 0;

                            foreach ($map_key_value_array as $map_key_value_array_value) {

                                $error_result[$map_key_value_array_value] =
                                    $failure->values()[$error_row_loop] ?? '';

                                $error_row_loop++;
                            }

                            $errror_array[] = [
                                'errormessage' => "Row " . $failure->row() . ": " . $error_msg,
                                'errorresult' => $error_result,
                            ];
                        }
                    }
                }

                $errorrecords = count($errror_array);
            }

            $errors = $errror_array;

            $result = [
                'successrecordscount' => $succussrecords,
                'errorrcount' => $errorrecords,
                'successfileids' => $successfileids
            ];
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {

            $failures = $e->failures();

            foreach ($failures as $failure) {

                if ($failure->row() != 1) {
                    $errors[] = $failure->errors();
                }
            }

            return response()->json([
                'status' => false,
                'message' => 'Failed to validate import',
                'data' => [],
                'errors' => $errors
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => [],
                'errors' => []
            ], 400); // or 422 (better for validation)
        }

        return response()->json([
            'status' => true,
            'message' => 'Customer successfully imported',
            'data' => $result,
            'errors' => $errors
        ], 200);
    }

    // public function final_import(Request $request): JsonResponse
    //  {
    //     try {
    //         $request->validate([
    //             'file' => 'required|mimes:csv,txt'
    //         ]);

    //         $file = $request->file('file');

    //         $data = array_map('str_getcsv', file($file));
    //         $header = array_map(function ($value) {
    //             return strtolower(str_replace(' ', '_', trim($value)));
    //         }, $data[0]);
    //         unset($data[0]);

    //         $rows = [];
    //         foreach ($data as $row) {
    //             if (count($header) == count($row)) {
    //                 $rows[] = array_combine($header, $row);
    //             }
    //         }

    //         $result = $this->service->importData($rows);

    //         return response()->json(['status' => true, 'message' => 'Import completed', 'data' => $result]);
    //     } catch (\Exception $e) {
    //         return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
    //     }
    //  }

    public function finalimport(Request $request)
    {
        $importtempfile = ImportTempFile::select('FileName')
            ->where('id', $request->successfileids)
            ->first();

        if ($importtempfile) {

            $data = File::get(storage_path() . '/app/tempimport/' . $importtempfile->FileName);
            $finaldata = json_decode($data);

            if ($finaldata) :
                foreach ($finaldata as $row) :


                    if (is_object($planogram)) {
                    } else {
                    }
                endforeach;
                unlink(storage_path() . '/app/tempimport/' . $importtempfile->FileName);
                \DB::table('import_temp_files')->where('id', $request->successfileids)->delete();
            endif;
            return prepareResult(true, [], [], "Planogram successfully imported", $this->success);
        } else {
            return prepareResult(false, [], [], "Error while import file.", $this->unauthorized);
        }
    }

    public function downloadDummyCsv(Request $request)
    {
        // format support (default csv)
        $format = strtolower($request->input('format', 'csv'));
        $extension = $format === 'xlsx' ? 'xlsx' : 'csv';

        $filename = 'Target_Commissiona_' . now()->format('Ymd_His') . '.' . $extension;
        $path = 'targetCommissiona/' . $filename;

        $export = new TargetCommisionaDummyCSV();

        if ($format === 'xlsx') {
            Excel::store($export, $path, 'public', ExcelExcel::XLSX);
        } else {
            Excel::store($export, $path, 'public', ExcelExcel::CSV);
        }

        // generate public URL
        $appUrl = rtrim(config('app.url'), '/');
        $downloadUrl = $appUrl . '/storage/app/public/' . $path;

        return response()->json(['status' => 'success', 'download_url'  => $downloadUrl,]);
    }

    public function index(Request $request)
    {
        try {
            $data = $this->service->getList($request);

            return response()->json(['status' => true, 'message' => 'Agent target list fetched successfully', 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request)
    {
        // ✅ Validation
        $request->validate([
            'warehouse_id' => 'required|integer',
            'target_month' => 'required',
            'target_year' => 'required',
        ]);

        try {
            $data = $this->service->getSingle($request);

            return response()->json(['status' => true, 'message' => 'Agent target detail fetched successfully', 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function globalFilter(Request $request)
    {
        // ✅ Validation
        $request->validate([
            'filter.warehouse_id' => 'required|string',
            'filter.target_month' => 'required',
            'filter.target_year' => 'required',
        ]);

        try {
            $data = $this->service->globalFilter($request->filter);
            return response()->json(['status' => true, 'message' => 'Agent target detail fetched successfully', 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 200);
        }
    }

    public function export(Request $request)
    {
        $format    = strtolower($request->input('format', 'xlsx'));
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        $filename = 'agent_targets_' . now()->format('Ymd_His') . '.' . $extension;
        $path     = 'agenttargetexports/' . $filename;

        // ✅ Payload (same as tum use kar rahe ho)
        $filters = $request->all();

        $export = new AgentTargetExport($filters);

        // ✅ Store file
        if ($format === 'csv') {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::store($export, $path, 'public', \Maatwebsite\Excel\Excel::XLSX);
        }

        // ✅ Download URL
        $fullUrl = rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;

        return response()->json(['status' => 'success', 'download_url' => $fullUrl,]);
    }

    public function updateTarget(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required',
            'month' => 'required',
            'year' => 'required',
            'item' => 'required', // single OR array
        ]);

        try {
            $data = $this->service->updateTarget($request);

            return response()->json(['status' => true, 'message' => 'Agent target updated successfully', 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
