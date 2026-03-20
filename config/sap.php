<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SAP Base URL
    |--------------------------------------------------------------------------
    | Base URL of the SAP OData service
    | Example: https://sap.company.com:44300
    */
    'base_url' => env('SAP_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | SAP Client
    |--------------------------------------------------------------------------
    | SAP client number (e.g. 900)
    */
    'client' => env('SAP_CLIENT', '900'),

    /*
    |--------------------------------------------------------------------------
    | SAP Authorization Token
    |--------------------------------------------------------------------------
    | Basic Authentication token
    | Example: Basic xxxxxxxxxxxxxxxxx
    */
    'token' => env('SAP_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => env('SAP_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | SSL Verification
    |--------------------------------------------------------------------------
    | SAP environments often use self-signed certificates.
    | Set false to disable SSL verification (same as curl flags).
    */
    'verify_ssl' => env('SAP_VERIFY_SSL', false),
];
