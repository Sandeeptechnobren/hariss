<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AgentCustomer;

class ImportController extends Controller
{
//     private function toPgDate($value)
//     {
//         if (!$value || strtolower($value) === 'null') return null;
//         $value = trim($value);
//         $dt = \DateTime::createFromFormat('d-m-Y H:i', $value);
//         if ($dt) return $dt->format('Y-m-d H:i:s');
//         $dt = \DateTime::createFromFormat('d-m-Y', $value);
//         if ($dt) return $dt->format('Y-m-d');
//         return null;
//     }

//     private function intOrNull($value)
//     {
//         $value = trim((string)$value);
//         return ($value === '' || strtolower($value) === 'null') ? null : (int) $value;
//     }

//     private function floatOrNull($value)
//     {
//         $value = trim((string)$value);
//         return ($value === '' || strtolower($value) === 'null') ? null : (float) $value;
//     }

//     // public function import(Request $request)
//     // {
           
//     //     try {
//     //          ini_set('memory_limit', '-1');
//     //          ini_set('max_execution_time','0');
            
//     //         $path = storage_path('app/agent_customer.csv'); 
//     //         if (!file_exists($path)) {
//     //             return response()->json(['error' => 'File does not exist at: ' . $path], 404);
//     //         }
//     //         if (!is_readable($path)) {
//     //             return response()->json(['error' => 'File exists but is not readable: ' . $path], 403);
//     //         }
//     //         $data = array_map('str_getcsv', file($path));
//     //         $headers = array_map('trim', array_shift($data));
//     //         $records = [];
//     //         $skippedRows = [];

//     //         $start = $request->start ?? 0;
//     //         $limit = $request->limit ?? 500;


//     //         $end   = $start + $limit;
//     //         foreach ($data as $index=>$row) {
//     //             if (count($headers) !== count($row)) {
//     //                 $skippedRows[] = $row;
//     //                 continue;
//     //             }
//     //              if ($index >= $start && $index < $end) {
//     //             $rowData = array_combine($headers, $row);
//     //             //  dd($rowData);
//     //             // $records[] = [
//     //             //     'osa_code'                 => $rowData['ccode'] ?? null,
//     //             //     'customer_type'            => $rowData['cctype'] ?? null,
//     //             //     'rc'                       => $rowData['rc'] ?? null,
//     //             //     'street'                   => $rowData['street'] ?? null,
//     //             //     'customersequence'         => $rowData['customersequence'] ?? null,
//     //             //     'name'                     => $rowData['customername'] ?? null,
//     //             //     'owner_name'               => $rowData['owner_name'] ?? null,
//     //             //     'email'                    => $rowData['email'] ?? null,
//     //             //     'language'                 => $rowData['language'] ?? null,
//     //             //     'fridge'                   => $rowData['fridge'] ?? null,
//     //             //     'buyertype'                => $rowData['buyerType'] ?? null,
//     //             //     'ura_address'              => $rowData['ura_address'] ?? null,
//     //             //     'town'                     => $rowData['customeraddress1'] ?? null,
//     //             //     'landmark'                 => $rowData['customeraddress2'] ?? null,
//     //             //     'contact_no'               => $rowData['customerphone'] ?? null,
//     //             //     'contact_no2'              => $rowData['customer_phone2'] ?? null,
//     //             //     'balance'                  => $this->floatOrNull($rowData['balance'] ?? null),
//     //             //     'category_id'              => $rowData['category'] ?? null,
//     //             //     'outlet_channel_id'        => $rowData['outlet_channel'] ?? null,
//     //             //     'subcategory_id'           => $rowData['outlet_sub_category'] ?? null,
//     //             //     'pricingkey'               => $rowData['pricingkey'] ?? null,
//     //             //     'promotionkey'             => $rowData['promotionkey'] ?? null,
//     //             //     'authorizeditemgrpkey'     => $rowData['authorizeditemgrpkey'] ?? null,
//     //             //     'paymentmethod'            => $rowData['paymentmethod'] ?? null,
//     //             //     'payment_type'             => $rowData['payment_type'] ?? null,
//     //             //     'bank_name'                => $rowData['bank_name'] ?? null,
//     //             //     'bank_account_number'      => $rowData['bank_account_number'] ?? null,
//     //             //     'creditday'                => $rowData['creditday'] ?? null,
//     //             //     'salesopt'                 => $rowData['salesopt'] ?? 0,
//     //             //     'returnsopt'               => $rowData['returnsopt'] ?? 0,
//     //             //     'surveykey'                => $rowData['surveykey'] ?? null,
//     //             //     'callfrequency'            => $rowData['callfrequency'] ?? null,
//     //             //     'customercity'             => $rowData['customercity'] ?? null,
//     //             //     'customerstate'            => $rowData['customerstate'] ?? null,
//     //             //     'customerzip'              => $rowData['customerzip'] ?? null,
//     //             //     'invoicepriceprint'        => $rowData['invoicepriceprint'] ?? 0,
//     //             //     'enablepromotrxn'          => $rowData['enablepromotrxn'] ?? 0,
//     //             //     'trn_no'                   => $rowData['trn_no'] ?? null,
//     //             //     'accuracy'                 => $this->floatOrNull($rowData['accuracy'] ?? null),
//     //             //     'creditlimit'              => $this->floatOrNull($rowData['creditlimit'] ?? null),
//     //             //     'expirylimit'              => $this->floatOrNull($rowData['expirylimit'] ?? null),
//     //             //     'exprunningvalue'          => $this->floatOrNull($rowData['exprunningvalue'] ?? null),
//     //             //     'barcode'                  => $rowData['barcode'] ?? null,
//     //             //     'division'                 => $rowData['division'] ?? null,
//     //             //     'price_survey_id'          => $rowData['price_survey_id'] ?? null,
//     //             //     'allowchequecollection'    => ($rowData['allowchequecollection'] ?? '') === '' || strtoupper($rowData['allowchequecollection']) === 'NULL' ? 0 : (int) $rowData['allowchequecollection'],
//     //             //     'region_id'                => $this->intOrNull($rowData['region_id'] ?? null),
//     //             //     'sub_region_id'            => $this->intOrNull($rowData['sub_region_id'] ?? null),
//     //             //     'vat_no'                   => $rowData['vat_no'] ?? null,
//     //             //     'longitude'                => $rowData['longitude'] ?? null,
//     //             //     'latitude'                 => $rowData['latitude'] ?? null,
//     //             //     'threshold_radius'         => $this->intOrNull($rowData['threshold_radius'] ?? null),
//     //             //     'salesman_id'              => $this->intOrNull($rowData['salesman_id'] ?? null),
//     //             //     'status'                   => $this->intOrNull($rowData['status'] ?? 1),
//     //             //     'print_status'             => $this->intOrNull($rowData['print_status'] ?? 0),
//     //             //     'guarantee_name'           => $rowData['guarantee_name'] ?? null,
//     //             //     'guarantee_amount'         => $this->floatOrNull($rowData['guarantee_amount'] ?? null),
//     //             //     'guarantee_from'           => $this->toPgDate($rowData['guarantee_from'] ?? null),
//     //             //     'guarantee_to'             => $this->toPgDate($rowData['guarantee_to'] ?? null),
//     //             //     'givencreditlimit'         => $this->floatOrNull($rowData['givencreditlimit'] ?? null),
//     //             //     'qrcode_image'             => $rowData['qrcode_image'] ?? null,
//     //             //     'qr_value'                 => $rowData['qr_value'] ?? null,
//     //             //     'qr_latitude'              => $rowData['qr_latitude'] ?? null,
//     //             //     'qr_longitude'             => $rowData['qr_longitude'] ?? null,
//     //             //     'qr_accuracy'              => $this->floatOrNull($rowData['qr_accuracy'] ?? null),
//     //             //     'qrreset_count'            => $this->intOrNull($rowData['qrreset_count'] ?? null),
//     //             //     'qrreset_date'             => $this->toPgDate($rowData['qrreset_date'] ?? null),
//     //             //     'capital_invest'           => $this->floatOrNull($rowData['capital_invest'] ?? null),
//     //             //     'sap_id'                   => $rowData['sap_id'] ?? null,
//     //             //     'dchannel_id'              => $this->intOrNull($rowData['dchannel_id'] ?? null),
//     //             //     'last_updated_serial_no'   => $rowData['last_updated_serial_no'] ?? null,
//     //             //     'credit_limit_validity'    => $this->toPgDate($rowData['credit_limit_validity'] ?? null),
//     //             //     'invoice_code'             => $rowData['invoice_code'] ?? null,
//     //             //     'fridge_id'                => $this->intOrNull($rowData['fridge_id'] ?? null),
//     //             //     'installation_date'        => $this->toPgDate($rowData['installation_date'] ?? null),
//     //             //     'is_fridge_assign'         => $this->intOrNull($rowData['is_fridge_assign'] ?? 0),
//     //             //     'serial_number_temp'       => $rowData['serial_number_temp'] ?? null,
//     //             //     'created_user'             => $this->intOrNull($rowData['created_user'] ?? null),
//     //             //     'updated_user'             => $this->intOrNull($rowData['updated_user'] ?? null),
//     //             //     'updated_at'               => $this->toPgDate($rowData['updated_date'] ?? null),
//     //             // ];
//     //             $records[] = [
//     //                 'osa_code'                => $rowData['ccode'] ?? null,
//     //                 'customer_type'           => $this->intOrNull($rowData['cctype'] ?? null),
//     //                 'rc'                      => $rowData['rc'] ?? null,
//     //                 'street'                  => $rowData['street'] ?? null,
//     //                 'customersequence'        => $rowData['customersequence'] ?? null,
//     //                 'name'                    => $rowData['customername'] ?? null,
//     //                 'owner_name'              => $rowData['owner_name'] ?? null,
//     //                 'email'                   => $rowData['email'] ?? null,
//     //                 'language'                => $rowData['language'] ?? null,
//     //                 'fridge'                  => $rowData['fridge'] ?? null,
//     //                 'buyertype'               => $this->intOrNull($rowData['buyerType'] ?? null),
//     //                 'ura_address'             => $rowData['ura_address'] ?? null,
//     //                 'town'                    => $rowData['customeraddress1'] ?? null,
//     //                 'landmark'                => $rowData['customeraddress2'] ?? null,
//     //                 'contact_no'              => $rowData['customerphone'] ?? null,
//     //                 'contact_no2'             => $rowData['customer_phone2'] ?? null,
//     //                 'balance'                 => $this->floatOrNull($rowData['balance'] ?? null),
//     //                 'category_id'             => $this->intOrNull($rowData['category'] ?? null),
//     //                 'outlet_channel_id'       => $this->intOrNull($rowData['outlet_channel'] ?? null),
//     //                 'subcategory_id'          => $this->intOrNull($rowData['outlet_sub_category'] ?? null),
//     //                 'pricingkey'              => $rowData['pricingkey'] ?? null,
//     //                 'promotionkey'            => $rowData['promotionkey'] ?? null,
//     //                 'authorizeditemgrpkey'    => $rowData['authorizeditemgrpkey'] ?? null,
//     //                 'paymentmethod'           => $rowData['paymentmethod'] ?? null,
//     //                 'payment_type'            => $rowData['payment_type'] ?? null,
//     //                 'bank_name'               => $rowData['bank_name'] ?? null,
//     //                 'bank_account_number'     => $rowData['bank_account_number'] ?? null,
//     //                 'creditday'               => $this->intOrNull($rowData['creditday'] ?? null),
//     //                 'salesopt'                => $rowData['salesopt'] ?? null,
//     //                 'returnsopt'              => $rowData['returnsopt'] ?? null,
//     //                 'surveykey'               => $rowData['surveykey'] ?? null,
//     //                 'callfrequency'           => $this->intOrNull($rowData['callfrequency'] ?? null),
//     //                 'customercity'            => $rowData['customercity'] ?? null,
//     //                 'customerstate'           => $rowData['customerstate'] ?? null,
//     //                 'customerzip'             => $rowData['customerzip'] ?? null,
//     //                 'invoicepriceprint'       => $this->intOrNull($rowData['invoicepriceprint'] ?? null),
//     //                 'enablepromotrxn'         => $this->intOrNull($rowData['enablepromotrxn'] ?? null),
//     //                 'trn_no'                  => $rowData['trn_no'] ?? null,
//     //                 'accuracy'                => $this->floatOrNull($rowData['accuracy'] ?? null),
//     //                 'creditlimit'             => $this->floatOrNull($rowData['creditlimit'] ?? null),
//     //                 'expirylimit'             => $this->floatOrNull($rowData['expirylimit'] ?? null),
//     //                 'exprunningvalue'         => $this->floatOrNull($rowData['exprunningvalue'] ?? null),
//     //                 'barcode'                 => $rowData['barcode'] ?? null,
//     //                 'division'                => $rowData['division'] ?? null,
//     //                 'price_survey_id'         => $this->intOrNull($rowData['price_survey_id'] ?? null),
//     //                 'allowchequecollection'   => $this->intOrNull($rowData['allowchequecollection'] ?? null),
//     //                 'region_id'               => $this->intOrNull($rowData['region_id'] ?? null),
//     //                 'sub_region_id'           => $this->intOrNull($rowData['sub_region_id'] ?? null),
//     //                 'vat_no'                  => $rowData['vat_no'] ?? null,
//     //                 'longitude'               => $rowData['longitude'] ?? null,
//     //                 'latitude'                => $rowData['latitude'] ?? null,
//     //                 'threshold_radius'        => $this->intOrNull($rowData['threshold_radius'] ?? null),
//     //                 'salesman_id'             => $this->intOrNull($rowData['salesman_id'] ?? null),
//     //                 'status'                  => $this->intOrNull($rowData['status'] ?? 1),
//     //                 'print_status'            => $this->intOrNull($rowData['print_status'] ?? null),
//     //                 'guarantee_name'          => $rowData['guarantee_name'] ?? null,
//     //                 'guarantee_amount'        => $this->floatOrNull($rowData['guarantee_amount'] ?? null),
//     //                 'guarantee_from'          => $this->toPgDate($rowData['guarantee_from'] ?? null),
//     //                 'guarantee_to'            => $this->toPgDate($rowData['guarantee_to'] ?? null),
//     //                 'givencreditlimit'        => $this->floatOrNull($rowData['givencreditlimit'] ?? null),
//     //                 'qrcode_image'            => $rowData['qrcode_image'] ?? null,
//     //                 'qr_value'                => $rowData['qr_value'] ?? null,
//     //                 'qr_latitude'             => $rowData['qr_latitude'] ?? null,
//     //                 'qr_longitude'            => $rowData['qr_longitude'] ?? null,
//     //                 'qr_accuracy'             => $this->floatOrNull($rowData['qr_accuracy'] ?? null),
//     //                 'qrreset_count'           => $this->intOrNull($rowData['qrreset_count'] ?? null),
//     //                 'qrreset_date'            => $this->toPgDate($rowData['qrreset_date'] ?? null),
//     //                 'capital_invest'          => $this->floatOrNull($rowData['capital_invest'] ?? null),
//     //                 'sap_id'                  => $rowData['sap_id'] ?? null,
//     //                 'dchannel_id'             => $this->intOrNull($rowData['dchannel_id'] ?? null),
//     //                 'last_updated_serial_no'  => $rowData['last_updated_serial_no'] ?? null,
//     //                 'credit_limit_validity'   => $this->toPgDate($rowData['credit_limit_validity'] ?? null),
//     //                 'invoice_code'            => $rowData['invoice_code'] ?? null,
//     //                 'fridge_id'               => $this->intOrNull($rowData['fridge_id'] ?? null),
//     //                 'installation_date'       => $this->toPgDate($rowData['installation_date'] ?? null),
//     //                 'is_fridge_assign'        => $this->intOrNull($rowData['is_fridge_assign'] ?? null),
//     //                 'serial_number_temp'      => $rowData['serial_number_temp'] ?? null,
//     //                 'created_user'            => $this->intOrNull($rowData['created_user'] ?? null),
//     //                 'updated_user'            => $this->intOrNull($rowData['updated_user'] ?? null),
//     //                 'updated_at'              => $this->toPgDate($rowData['updated_date'] ?? null),
//     //             ];

//     //          }
//     //         }
             
//     //         if (!empty($records)) {
//     //             // foreach (array_chunk($records, 1000) as $chunk) {
//     //                 AgentCustomer::insert($records);
//     //             // }
//     //         }
//     //         return response()->json([
//     //             'message' => 'Agent customers imported successfully',
//     //             'imported_count' => count($records),
//     //             'skipped_rows' => count($skippedRows),
//     //             'processed_from' => $start,
//     //             'processed_to'   => $end - 1,
//     //             'next_start'     => $end
//     //         ]);
//     //     } catch (\Exception $e) {
//     //         \Log::error('Import Failed:', ['error' => $e->getMessage()]);
//     //         return response()->json(['error' => $e->getMessage()], 500);
//     //     }
//     // }
//  public function import(Request $request)
// {
//     try {
//         ini_set('memory_limit', '-1');
//         ini_set('max_execution_time','0');
//         $path = storage_path('app/agent_customer.csv');
//         if (!file_exists($path)) {
//             return response()->json(['error' => 'File does not exist at: ' . $path], 404);
//         }
//         if (!is_readable($path)) {
//             return response()->json(['error' => 'File exists but is not readable: ' . $path], 403);
//         }
//         $data = array_map('str_getcsv', file($path));
//         $headers = array_map('trim', array_shift($data));
//         $chunkSize = 500;
//         $total = count($data);
//         $processed = 0;
//         $skippedRows = [];
//         while ($processed < $total) {
//             $chunk = array_slice($data, $processed, $chunkSize);
//             $records = [];
//             foreach ($chunk as $row) {
//                 if (count($headers) !== count($row)) {
//                     $skippedRows[] = $row;
//                     continue;
//                 }
//                 $rowData = array_combine($headers, $row);

//                 $records[] = [
//                     'osa_code'                => $rowData['ccode'] ?? null,
//                     'customer_type'           => $this->intOrNull($rowData['cctype'] ?? null),
//                     'rc'                      => $rowData['rc'] ?? null,
//                     'street'                  => $rowData['street'] ?? null,
//                     'customersequence'        => $rowData['customersequence'] ?? null,
//                     'name'                    => $rowData['customername'] ?? null,
//                     'owner_name'              => $rowData['owner_name'] ?? null,
//                     'email'                   => $rowData['email'] ?? null,
//                     'language'                => $rowData['language'] ?? null,
//                     'fridge'                  => $rowData['fridge'] ?? null,
//                     'buyertype'               => $this->intOrNull($rowData['buyerType'] ?? null),
//                     'ura_address'             => $rowData['ura_address'] ?? null,
//                     'town'                    => $rowData['customeraddress1'] ?? null,
//                     'landmark'                => $rowData['customeraddress2'] ?? null,
//                     'contact_no'              => $rowData['customerphone'] ?? null,
//                     'contact_no2'             => $rowData['customer_phone2'] ?? null,
//                     'balance'                 => $this->floatOrNull($rowData['balance'] ?? null),
//                     'category_id'             => $this->intOrNull($rowData['category'] ?? null),
//                     'outlet_channel_id'       => $this->intOrNull($rowData['outlet_channel'] ?? null),
//                     'subcategory_id'          => $this->intOrNull($rowData['outlet_sub_category'] ?? null),
//                     'pricingkey'              => $rowData['pricingkey'] ?? null,
//                     'promotionkey'            => $rowData['promotionkey'] ?? null,
//                     'authorizeditemgrpkey'    => $rowData['authorizeditemgrpkey'] ?? null,
//                     'paymentmethod'           => $rowData['paymentmethod'] ?? null,
//                     'payment_type'            => $rowData['payment_type'] ?? null,
//                     'bank_name'               => $rowData['bank_name'] ?? null,
//                     'bank_account_number'     => $rowData['bank_account_number'] ?? null,
//                     'creditday'               => $this->intOrNull($rowData['creditday'] ?? null),
//                     'salesopt'                => $rowData['salesopt'] ?? null,
//                     'returnsopt'              => $rowData['returnsopt'] ?? null,
//                     'surveykey'               => $rowData['surveykey'] ?? null,
//                     'callfrequency'           => $this->intOrNull($rowData['callfrequency'] ?? null),
//                     'customercity'            => $rowData['customercity'] ?? null,
//                     'customerstate'           => $rowData['customerstate'] ?? null,
//                     'customerzip'             => $rowData['customerzip'] ?? null,
//                     'invoicepriceprint'       => $this->intOrNull($rowData['invoicepriceprint'] ?? null),
//                     'enablepromotrxn'         => $this->intOrNull($rowData['enablepromotrxn'] ?? null),
//                     'trn_no'                  => $rowData['trn_no'] ?? null,
//                     'accuracy'                => $this->floatOrNull($rowData['accuracy'] ?? null),
//                     'creditlimit'             => $this->floatOrNull($rowData['creditlimit'] ?? null),
//                     'expirylimit'             => $this->floatOrNull($rowData['expirylimit'] ?? null),
//                     'exprunningvalue'         => $this->floatOrNull($rowData['exprunningvalue'] ?? null),
//                     'barcode'                 => $rowData['barcode'] ?? null,
//                     'division'                => $rowData['division'] ?? null,
//                     'price_survey_id'         => $this->intOrNull($rowData['price_survey_id'] ?? null),
//                     'allowchequecollection'   => $this->intOrNull($rowData['allowchequecollection'] ?? null),
//                     'region_id'               => $this->intOrNull($rowData['region_id'] ?? null),
//                     'sub_region_id'           => $this->intOrNull($rowData['sub_region_id'] ?? null),
//                     'vat_no'                  => $rowData['vat_no'] ?? null,
//                     'longitude'               => $rowData['longitude'] ?? null,
//                     'latitude'                => $rowData['latitude'] ?? null,
//                     'threshold_radius'        => $this->intOrNull($rowData['threshold_radius'] ?? null),
//                     'salesman_id'             => $this->intOrNull($rowData['salesman_id'] ?? null),
//                     'status'                  => $this->intOrNull($rowData['status'] ?? 1),
//                     'print_status'            => $this->intOrNull($rowData['print_status'] ?? null),
//                     'guarantee_name'          => $rowData['guarantee_name'] ?? null,
//                     'guarantee_amount'        => $this->floatOrNull($rowData['guarantee_amount'] ?? null),
//                     'guarantee_from'          => $this->toPgDate($rowData['guarantee_from'] ?? null),
//                     'guarantee_to'            => $this->toPgDate($rowData['guarantee_to'] ?? null),
//                     'givencreditlimit'        => $this->floatOrNull($rowData['givencreditlimit'] ?? null),
//                     'qrcode_image'            => $rowData['qrcode_image'] ?? null,
//                     'qr_value'                => $rowData['qr_value'] ?? null,
//                     'qr_latitude'             => $rowData['qr_latitude'] ?? null,
//                     'qr_longitude'            => $rowData['qr_longitude'] ?? null,
//                     'qr_accuracy'             => $this->floatOrNull($rowData['qr_accuracy'] ?? null),
//                     'qrreset_count'           => $this->intOrNull($rowData['qrreset_count'] ?? null),
//                     'qrreset_date'            => $this->toPgDate($rowData['qrreset_date'] ?? null),
//                     'capital_invest'          => $this->floatOrNull($rowData['capital_invest'] ?? null),
//                     'sap_id'                  => $rowData['sap_id'] ?? null,
//                     'dchannel_id'             => $this->intOrNull($rowData['dchannel_id'] ?? null),
//                     'last_updated_serial_no'  => $rowData['last_updated_serial_no'] ?? null,
//                     'credit_limit_validity'   => $this->toPgDate($rowData['credit_limit_validity'] ?? null),
//                     'invoice_code'            => $rowData['invoice_code'] ?? null,
//                     'fridge_id'               => $this->intOrNull($rowData['fridge_id'] ?? null),
//                     'installation_date'       => $this->toPgDate($rowData['installation_date'] ?? null),
//                     'is_fridge_assign'        => $this->intOrNull($rowData['is_fridge_assign'] ?? null),
//                     'serial_number_temp'      => $rowData['serial_number_temp'] ?? null,
//                     'created_user'            => $this->intOrNull($rowData['created_user'] ?? null),
//                     'updated_user'            => $this->intOrNull($rowData['updated_user'] ?? null),
//                     'updated_at'              => $this->toPgDate($rowData['updated_date'] ?? null),
//                 ];
//             }

//             if (count($records) > 0) {
//                 AgentCustomer::insert($records);
//             }
//             $processed += $chunkSize;
//         }

//         return response()->json([
//             'message' => 'Agent customers imported successfully',
//             'total_imported_rows' => $processed,
//             'skipped_rows' => count($skippedRows),
//         ]);

//     } catch (\Exception $e) {
//         \Log::error('Import Failed:', ['error' => $e->getMessage()]);
//         return response()->json(['error' => $e->getMessage()], 500);
//     }
// }



//     public function index()
//     {
//         return AgentCustomer::all();
//     }


public function import(Request $request)
{
    DB::beginTransaction();

    try {
        $warehouses = DB::table('tbl_warehouse')
            ->whereBetween('id', [1785, 2038])
            ->orderBy('id')
            ->get();
        foreach ($warehouses as $warehouse) {
            $newId = $warehouse->id - 1783; 
            DB::table('tbl_warehouse')
                ->where('id', $newId)
                ->delete();
            DB::table('tbl_warehouse')
                ->where('id', $warehouse->id)
                ->update(['id' => $newId]);
        }
        DB::commit();
        return response()->json([
            'message' => 'Warehouse IDs updated successfully with conflict replacement!'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}


}
