<?php
namespace App\Helpers;

class ApiResponse
{
public static function apiResponse($code = 200, $message = null, $data = null)
{
    $response = [
        'status'  => $code,
        'message' => $message,
    ];

    if ($data instanceof \Illuminate\Pagination\LengthAwarePaginator) {
        $response['data'] = $data->items();
        $response['pagination'] = [
            'total'        => $data->total(),
            'per_page'     => $data->perPage(),
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
        ];
    } else {
        $response['data'] = $data;
    }

    return response()->json($response, $code);
}

}
