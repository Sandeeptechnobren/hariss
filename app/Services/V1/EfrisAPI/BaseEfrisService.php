<?php

namespace App\Services\V1\EfrisAPI;

class BaseEfrisService
{

    protected function makePost($interfaceCode, $payload, $warehouse)
    {
        try {


            $data = $this->fetchData($warehouse);
            $aesKey = $this->getAESKey($warehouse);

            // dd($aesKey);
            $encrypted = openssl_encrypt(
                json_encode($payload),
                "aes-128-ecb",
                $aesKey
            );

            if (!$encrypted) {
                throw new \Exception('Encryption failed');
            }

            $data['globalInfo']['interfaceCode'] = $interfaceCode;
            $data['globalInfo']['deviceNo'] = $warehouse->device_no;

            $data['data']['content'] = $encrypted;
            $data['data']['dataDescription']['codeType'] = "1";
            $data['data']['dataDescription']['encryptCode'] = "2";

            // ✅ SIGNATURE
            $privateKey = $this->getPrivKey($warehouse);

            if (!openssl_sign($encrypted, $signature, $privateKey, OPENSSL_ALGO_SHA1)) {
                throw new \Exception('Signature failed');
            }

            $data['data']['signature'] = base64_encode($signature);

            $resp = json_decode($this->postReq($data));
            // dd($resp);
            if (!$resp) {
                throw new \Exception('Invalid response from EFRIS');
            }

            $return = $resp->returnStateInfo;

            $encryptedContent = $resp->data->content ?? '';
            $decrypted = '';

            if ($encryptedContent) {
                $decrypted = openssl_decrypt($encryptedContent, "aes-128-ecb", $aesKey);
                $decoded = json_decode($decrypted, true);
            }
            // dd($decoded);
            return [
                'success' => $return->returnCode === "00",
                'returnCode' => $return->returnCode,
                'message' => $return->returnMessage,
                'inner_response' => $decoded ?? null
            ];
        } catch (\Exception $e) {

            return [
                'success' => false,
                'returnCode' => 'EXCEPTION',
                'message' => $e->getMessage(),
                'inner_response' => []
            ];
        }
    }
    // protected function fetchData($warehouse = null)
    // {
    //     return [
    //         "data" => [
    //             "content" => "",
    //             "signature" => "",
    //             "dataDescription" => [
    //                 "codeType" => "0",
    //                 "encryptCode" => "1",
    //                 "zipCode" => "0"
    //             ]
    //         ],
    //         "globalInfo" => [
    //             "appId" => "AP04",
    //             "version" => "1.1.20191201",
    //             "dataExchangeId" => uniqid(),
    //             "interfaceCode" => "",
    //             "requestTime" => date("Y-m-d H:i:s"),
    //             "requestCode" => "TP",
    //             "responseCode" => "TA",
    //             "userName" => "admin",
    //             "deviceMAC" => "FFFFFFFFFFFF",
    //             "deviceNo" => "",
    //             "tin" => $warehouse->tin_no ?? "",
    //         ],
    //         "returnStateInfo" => [
    //             "returnCode" => "",
    //             "returnMessage" => ""
    //         ]
    //     ];
    // }

    protected function fetchData($warehouse = null)
    {
        return [
            "data" => [
                "content" => "",
                "signature" => "",
                "dataDescription" => [
                    "codeType" => "0",
                    "encryptCode" => "1",
                    "zipCode" => "0"
                ]
            ],
            "globalInfo" => [
                "appId" => "AP04",
                "version" => "1.1.20191201",
                "dataExchangeId" => $this->guidv4(),
                "interfaceCode" => "",
                "requestTime" => date("Y-m-d H:i:s"),
                "requestCode" => "TP",
                "responseCode" => "TA",
                "userName" => "admin",
                "deviceMAC" => "FFFFFFFFFFFF",
                "deviceNo" => "",
                "tin" => $warehouse->tin_no ?? "",
                "brn" => "",
                "taxpayerID" => "1",
                "longitude" => $warehouse->longitude ?? "116.397128",
                "latitude" => $warehouse->latitude ?? "39.916527",
                "extendField" => [
                    "responseDateFormat" => "dd/MM/yyyy",
                    "responseTimeFormat" => "dd/MM/yyyy HH:mm:ss"
                ]
            ],
            "returnStateInfo" => [
                "returnCode" => "",
                "returnMessage" => ""
            ]
        ];
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

    private function guidv4($data = null)
    {
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s', str_split(bin2hex($data), 4));
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
        // dd($passowrdDes);

        $privKey = $this->getPrivKey($warehouse);

        $isDecrypted = openssl_private_decrypt($passowrdDes, $aesKey, $privKey, OPENSSL_PKCS1_PADDING);
        return  base64_decode($aesKey);
    }

    public function callApi($interfaceCode, $payload, $warehouse)
    {
        return $this->makePost($interfaceCode, $payload, $warehouse);
    }
}
