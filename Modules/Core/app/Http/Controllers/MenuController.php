<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\MenuService;
use Modules\Core\Http\Requests\StoreMenuRequest;
use Modules\Core\Http\Requests\UpdateMenuRequest;

class MenuController extends Controller
{
    protected $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    // Lấy danh sách menu
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 2); // Sử dụng tham số 'per_page' từ query params, mặc định là 2
        $menus = $this->menuService->getAllMenus($perPage);
        return response()->json($menus);
    }

    // Thêm mới menu
    public function store(StoreMenuRequest $request)
    {
        $menu = $this->menuService->createMenu($request->validated());
        return response()->json($menu, 201);
    }

    // Cập nhật menu
    public function update(UpdateMenuRequest $request, $id)
    {
        $menu = $this->menuService->updateMenu($id, $request->validated());
        return response()->json($menu);
    }

    public function destroy(Request $request)
    {
        // Validate danh sách các IDs cần xóa
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|uuid|exists:menus,id',
        ], [
            'ids.required' => __('validation.ids_required'),
            'ids.*.uuid' => __('validation.invalid_uuid'),
            'ids.*.exists' => __('validation.id_not_found'),
        ]);
    
        // Xóa tất cả các menu trong danh sách
        foreach ($validated['ids'] as $menuId) {
            $this->menuService->deleteMenu($menuId);
        }
    
        $message = count($validated['ids']) === 1 
            ? __('messages.deleted_one_success', ['attribute' => trans('core::menus.attributes.menu')]) 
            : __('messages.deleted_many_success', [
                'attribute' => trans('core::menus.attributes.menu'),
                'count' => count($validated['ids'])
            ]);
    
        return response()->json([
            'status' => true,
            'data' => null,
            'message' => $message,
            'code' => 200
        ], 200);
    }
    

    private function validateSingleId($id)
    {
        $validator = validator()->make(['id' => $id], [
            'id' => 'required|uuid|exists:menus,id',
        ], [
            'id.required' => __('validation.required'),
            'id.uuid' => __('validation.invalid_uuid'),
            'id.exists' => __('validation.not_found'),
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->all());
        }
    }
}
