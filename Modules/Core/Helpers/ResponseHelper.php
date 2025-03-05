<?php

namespace Modules\Core\Helpers;

use Illuminate\Http\JsonResponse;

class ResponseHelper
{
    /**
     * Trả về response thành công
     */
    public static function success($data = [], string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'data'    => $data,
            'message' => $message,
            'errors'  => [],
            'code'    => $code,
        ], $code);
    }

    /**
     * Trả về response thất bại
     */
    public static function error(string $message, array $errors = [], int $code = 400): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'data'    => [],
            'message' => $message,
            'errors'  => $errors,
            'code'    => $code,
        ], $code);
    }
}
