<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Core\Helpers\ResponseHelper;
use Illuminate\Pagination\LengthAwarePaginator;

class FormatApiResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$response instanceof JsonResponse) {
            return $response;
        }

        $data = $response->getData(true);

        // Nếu đã đúng format hoặc có errors, trả về nguyên trạng
        if (isset($data['status'], $data['message'], $data['code']) || isset($data['errors'])) {
            return $response;
        }

        // Xử lý dữ liệu phân trang
        if ($response->original instanceof LengthAwarePaginator) {
            return $this->formatPaginatedResponse($response);
        }

        // Xử lý response thông thường với message đa ngôn ngữ
        return ResponseHelper::success(
            data: $data['data'] ?? $data,
            message: $data['message'] ?? __('messages.success'),
            code: $response->status()
        );
    }

    private function formatPaginatedResponse(JsonResponse $response): JsonResponse
    {
        $paginator = $response->original;

        // Tích hợp meta vào data
        $formattedData = [
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                // 'first_page_url' => $paginator->url(1),
                // 'last_page_url' => $paginator->url($paginator->lastPage()),
                // 'next_page_url' => $paginator->nextPageUrl(),
                // 'prev_page_url' => $paginator->previousPageUrl(),
                // 'path' => $paginator->path(),
                // 'from' => $paginator->firstItem(),
                // 'to' => $paginator->lastItem(),
            ]
        ];

        return ResponseHelper::success(
            data: $formattedData,
            message: __('messages.success'),
            code: $response->status()
        );
    }
}