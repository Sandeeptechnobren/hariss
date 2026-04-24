<?php

namespace App\Services\V1\EfrisAPI;

use App\Models\EfrisAPI\EfrisItemSyncFlag;
use App\Models\EfrisAPI\EfrisSyncLogs;
use App\Models\Item;
use App\Models\Warehouse;
use App\Helpers\DataAccessHelper;

class UraSyncService
{
    public function syncItems($itemId)
    {
        $user = auth()->user();

        $itemId = is_array($itemId) ? $itemId : [$itemId];
        // dd($itemId);
        $items = Item::with(['uoms' => function ($q) {
            $q->whereNotNull('price')
                ->where('price', '>', 0);
        }])
            ->whereIn('id', $itemId)
            ->where('status', 1)
            ->whereHas('uoms', function ($q) {
                $q->whereNotNull('price')
                    ->where('price', '>', 0);
            })
            ->get();

        if ($items->isEmpty()) {
            return [
                'status' => false,
                'message' => 'No valid items found'
            ];
        }

        $warehouses = Warehouse::where([
            'is_efris' => 1,
            'status' => 1
        ])->get();
        $warehouses = DataAccessHelper::filterWarehouses($warehouses, $user);
        // dd($warehouses->count());

        foreach ($warehouses as $warehouse) {
            foreach ($items as $item) {

                if ($item->uoms->isEmpty()) continue;

                $operationType = $this->getOperationType($item, $warehouse);
                $payload = $this->buildPayload($item, $operationType);

                $response = $this->make_post("T130", $payload, $warehouse);

                $results[] = [
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse->id,
                    'response' => $response
                ];

                $this->handleResponse($item, $warehouse, $response, $operationType, $payload);
            }
        }

        $results = $results ?? [];

        $successCount = collect($results)->where('status', true)->count();
        $failedCount = collect($results)->where('status', false)->count();

        return [
            'status' => true,
            'message' => 'Item Synced',
            'total' => count($results),
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'data' => $results
        ];
    }

    // 🔽 SAME METHODS (UNCHANGED)

    private function getOperationType($item, $warehouse)
    {
        $exists = EfrisItemSyncFlag::where('item_id', $item->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('is_synced', 1)
            ->exists();

        return $exists ? 102 : 101;
    }

    private function getuomsCode($uomId)
    {
        $map = [1 => 'PP', 2 => 'CS', 3 => 'PP', 4 => '110'];
        return $map[$uomId] ?? '';
    }
    private function buildPayload($item, $operationType)
    {
        $uoms = $item->uoms;
        if ($uoms->isEmpty()) return [];

        $uom_id = $uoms->sortByDesc('upc')->values();

        $measureUnit = '';
        $unitPrice = 1;

        $pieceMeasureUnit = '';
        $pieceUnitPrice = 1;
        $pieceScaledValue = 1;

        $otherUnits = [];

        foreach ($uom_id as $uom) {
            if (in_array($uom->uom_id, [2, 4]) && $measureUnit == '') {
                $measureUnit = $uom->uom_id == 2 ? 'CS' : '110';
                $unitPrice = max($uom->price ?? 1, 1);
            }

            if (in_array($uom->uom_id, [1, 3]) && $pieceMeasureUnit == '') {
                $pieceMeasureUnit = 'PP';
                $pieceScaledValue = $uom->upc ?? 1;
                $pieceUnitPrice = max($uom->price ?? 1, 1);
            }
        }

        foreach ($uom_id->skip(1) as $uom) {
            $code = $this->getuomsCode($uom->uom_id);

            if (!$code) continue;

            $otherUnits[] = [
                "otherUnit" => $code,
                "otherPrice" => (string)max($uom->price ?? 1, 1),
                "otherScaled" => (string)($uom->upc ?? 1),
                "packageScaled" => "1"
            ];
        }

        return [
            [
                "operationType" => (string)$operationType,
                "goodsName" => $item->name ?? '',
                "goodsCode" => $item->sap_id ?? '',
                "measureUnit" => $measureUnit ?: 'PP',
                "unitPrice" => (string)$unitPrice,
                "currency" => "101",
                "commodityCategoryId" => $item->commodity_goods_code ?? '',
                "haveExciseTax" => "102",
                "description" => "1",
                "stockPrewarning" => "10",
                "havePieceUnit" => "101",
                "pieceMeasureUnit" => $pieceMeasureUnit ?: 'PP',
                "pieceUnitPrice" => (string)$pieceUnitPrice,
                "packageScaledValue" => "1",
                "pieceScaledValue" => (string)$pieceScaledValue,
                "goodsTypeCode" => "101",
                "goodsOtherUnits" => array()
            ]
        ];
    }

    private function handleResponse($item, $warehouse, $response, $operationType, $payload)
    {
        $inner = $response['inner_response'][0] ?? null;

        $isSuccess = false;

        if ($inner) {
            $code = $inner['returnCode'];

            if (in_array($code, ["00"])) {
                $isSuccess = true;
            }
        }

        EfrisSyncLogs::updateOrCreate(
            [
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'interface_code' => 'T130',
            ],
            [
                'operation_type' => $operationType,
                'request_payload' => $payload,
                'response_payload' => $response,
                'is_success' => $isSuccess,
                'error_message' => $isSuccess ? null : ($inner['returnMessage'] ?? $response['message']),
                'synced_at' => now(),
            ]
        );

        if ($isSuccess) {
            EfrisItemSyncFlag::updateOrCreate(
                [
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse->id
                ],
                [
                    'is_synced' => 1
                ]
            );
        }
    }

    private function make_post($interfaceCode, $content, $warehouse = null)
    {
        $deviceNo = $warehouse->device_no;
        $data = $this->fetchData($warehouse);
        try {
            $aesKey = $this->getAESKey($warehouse);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
        $json_content = json_encode($content);
        $encrypted = openssl_encrypt($json_content, "aes-128-ecb", $aesKey);

        if ($encrypted) {
            $data['globalInfo']['interfaceCode'] = $interfaceCode;
            $data['globalInfo']['deviceNo'] = $deviceNo;
            $data['data']['content'] = $encrypted;
            $data['data']['dataDescription']['codeType'] = "1";
            $data['data']['dataDescription']['encryptCode'] = "2";

            $privKey = $this->getPrivKey($warehouse);
            openssl_sign($encrypted, $signature, $privKey, OPENSSL_ALGO_SHA1);
            $data['data']['signature'] = base64_encode($signature);
        }

        $jsonresp = $this->postReq($data);
        $resp = json_decode($jsonresp);

        if (!$resp) {
            return ['success' => false, 'message' => 'Invalid JSON response'];
        }

        $return = $resp->returnStateInfo;

        $encryptedContent = $resp->data->content ?? '';
        $decrypted = '';

        if ($encryptedContent) {
            $decrypted = openssl_decrypt($encryptedContent, "aes-128-ecb", $aesKey);
            $decoded = json_decode($decrypted, true);
        }

        return [
            'success' => $return->returnCode === "00",
            'returnCode' => $return->returnCode,
            'message' => $return->returnMessage,
            'inner_response' => $decoded ?? null
        ];
    }

    private function fetchData($warehouse = null)
    {
        date_default_timezone_set("Africa/Nairobi");
        $get_tins = (isset($warehouse->tin_no) ? $warehouse->tin_no : '');
        return array("data" => array("content" => "", "signature" => "", "dataDescription" => array("codeType" => "0", "encryptCode" => "1", "zipCode" => "0")), "globalInfo" => array("appId" => "AP04", "version" => "1.1.20191201", "dataExchangeId" => "9230489223014123", "interfaceCode" => "T101", "requestTime" => date("Y-m-d H:i:s"), "requestCode" => "TP", "responseCode" => "TA", "userName" => "admin", "deviceMAC" => "FFFFFFFFFFFF", "deviceNo" => "", "tin" => "$get_tins", "brn" => "", "taxpayerID" => "1", "longitude" => "116.397128", "latitude" => "39.916527", "extendField" => array("responseDateFormat" => "dd/MM/yyyy", "responseTimeFormat" => "dd/MM/yyyy HH:mm:ss")), "returnStateInfo" => array("returnCode" => "", "returnMessage" => ""));
    }

    private function getAESKey($warehouse = null)
    {
        $data = $this->fetchData($warehouse);
        $deviceNo = $warehouse->device_no;
        $tin = $warehouse->tin_no;
        $brn = "";
        $dataExchangeId = $this->guidv4();

        $data['globalInfo']['interfaceCode'] = "T104";
        $data['globalInfo']['dataExchangeId'] = $dataExchangeId;
        $data['globalInfo']['deviceNo'] = $deviceNo;
        $data['globalInfo']['tin'] = $tin;
        $data['globalInfo']['brn'] = $brn;
        $resp = $this->postReq($data);
        $jsonresp = json_decode($resp);
        $b64content = $jsonresp->{'data'}->{'content'};
        $content = json_decode(base64_decode($b64content, true));

        $b64passowrdDes = $content->{'passowrdDes'};
        $passowrdDes = base64_decode($b64passowrdDes);

        $privKey = $this->getPrivKey($warehouse);

        $isDecrypted = openssl_private_decrypt($passowrdDes, $aesKey, $privKey, OPENSSL_PKCS1_PADDING);
        return  base64_decode($aesKey);
    }

    private function guidv4($data = null)
    {
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s', str_split(bin2hex($data), 4));
    }

    private function postReq($data)
    {

        $url = 'https://efristest.ura.go.ug/efrisws/ws/taapp/getInformation';
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);

        $rst = curl_exec($curl);
        if ($rst === false) {
            dd('CURL ERROR:', curl_error($curl), curl_errno($curl));
        }
        $info = curl_getinfo($curl);
        curl_close($curl);

        return $rst;
    }

    private function fetchUrl()
    {
        return "https://efristest.ura.go.ug/efrisws/ws/taapp/getInformation";
    }

    private function getPrivKey($warehouse = null)
    {
        $pkpath = storage_path('/app/public/' . $warehouse->p12_file);
        if (!file_exists($pkpath)) {
            throw new \Exception("P12 file not found: " . $pkpath);
        }
        $cert_store = file_get_contents($pkpath);
        $isRead = openssl_pkcs12_read($cert_store, $cert_info, $warehouse->password);

        return $cert_info['pkey'];
    }
}
