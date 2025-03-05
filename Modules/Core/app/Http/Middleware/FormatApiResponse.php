<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Core\Helpers\ResponseHelper;

class FormatApiResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Nếu response không phải là JSON, trả về nguyên trạng
        if (!$response instanceof JsonResponse) {
            return $response;
        }

        // Lấy dữ liệu từ response
        $data = $response->getData(true);

        // Kiểm tra nếu response đã đúng format, không cần format lại
        if (isset($data['status'], $data['data'], $data['message'], $data['code'])) {
            return $response;
        }
        // Nếu đã có errors (vd: lỗi validation), không cần format lại
        if (isset($data['errors'])) {
            return $response;
        }
        // Nếu message nằm trong data, lấy nó và tránh lặp lại
        $message = $data['message'] ?? 'Success';
        unset($data['message']); // Xóa message trong data để tránh lặp lại

        // Format lại response theo chuẩn chung
        return ResponseHelper::success(
            data: $data['data'] ?? $data, // Nếu có key `data` thì dùng, không thì lấy toàn bộ response
            message: $message, // Nếu không có message, mặc định là "Success"
            code: $response->status() // Giữ nguyên HTTP status code
        );
    }
}
