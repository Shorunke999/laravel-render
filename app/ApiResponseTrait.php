<?php

namespace App;

trait ApiResponseTrait
{
    protected function successResponse($data = [],string $message, int $statusCode = 200){
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data
        ],$statusCode);
    }

    protected function errorResponse(string $message = "Error", int $statusCode = 400){
        return response()->json([
            'status' => false,
            'message' => $message,
        ],$statusCode);
    }
}
