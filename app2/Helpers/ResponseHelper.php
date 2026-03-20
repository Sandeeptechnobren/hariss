<?php

namespace App\Helpers;

class ResponseHelper
{
    public static function paginatedResponse($message, $resource, $paginator, $status = 'success', $code = 200)
    {
        return response()->json([
            'status' => $status,
            'code'   => $code,
            'message'=> $message,
            'data'   => $resource::collection($paginator),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ]
        ], $code);
    }
}