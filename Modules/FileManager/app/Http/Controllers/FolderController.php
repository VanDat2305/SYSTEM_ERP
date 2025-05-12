<?php

namespace Modules\FileManager\Http\Controllers;

use Illuminate\Http\Request;
use Modules\FileManager\Services\FolderService;
use App\Http\Controllers\Controller;
use Modules\FileManager\Http\Requests\FolderCreateRequest;
use Modules\FileManager\Http\Requests\FolderDeleteRequest;

class FolderController extends Controller
{
    protected FolderService $folderService;

    public function __construct(FolderService $folderService)
    {
        $this->folderService = $folderService;
    }

    public function index(Request $request)
    {
        $parentId = $request->has('parent_id') ? $request->parent_id : null;
        
        $folders = $this->folderService->getFolderTree($parentId);
        
        return response()->json([
            'data' => $folders
        ]);
    }

    public function store(FolderCreateRequest $request)
    {
        $folder = $this->folderService->create($request->validated());
        
        return response()->json([
            'data' => $folder,
            'message' => __("filemanager::messages.created_success")
        ], 201);
    }

    public function destroy(FolderDeleteRequest $request, string $id)
    {
        $this->folderService->delete($id);
        
        return response()->json([
            'message' => __("filemanager::messages.deleted_success")
        ]);
    }
}