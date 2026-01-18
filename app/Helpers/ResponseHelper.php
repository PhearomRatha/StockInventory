<?php

namespace App\Helpers;

class ResponseHelper
{
    /**
     * Success response
     */
    public static function success($message = 'Success', $data = null, $statusCode = 200)
    {
        $response = [
            'status' => $statusCode,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Error response
     */
    public static function error($message = 'Error', $statusCode = 500, $errors = null)
    {
        $response = [
            'status' => $statusCode,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Paginated response
     */
    public static function paginated($data, $message = 'Data retrieved successfully')
    {
        return response()->json([
            'status' => 200,
            'message' => $message,
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ]
        ]);
    }
}