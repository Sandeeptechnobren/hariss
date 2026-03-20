<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Success Response
     *
     * @param mixed       $data
     * @param string      $message
     * @param int         $code
     * @param array|null  $pagination
     * @return JsonResponse
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $code = 200,
        array $pagination = null
    ): JsonResponse {
        $response = [
            'status'  => 'success',
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ];

        if ($pagination) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response, $code);
    }

    /**
     * Error Response
     *
     * @param string     $message
     * @param int        $code
     * @param mixed|null $errors
     * @return JsonResponse
     */
    protected function fail(
        string $message = 'Something went wrong',
        int $code = 400,
        mixed $errors = null
    ): JsonResponse {
        return response()->json([
            'status'  => 'error', // ✅ string instead of boolean
            'code'    => $code,
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }
}
