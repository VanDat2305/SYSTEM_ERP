<?php

namespace Modules\FileManager\Http\Controllers;

use Illuminate\Http\Request;
use Modules\FileManager\Services\FileService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\FileManager\Http\Requests\FileUploadRequest;
use Modules\FileManager\Http\Requests\FileDeleteRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    protected FileService $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index(Request $request)
    {
        try {
            $folderId = $request->input('folder_id');
            $files = $this->fileService->getFilesByFolder($folderId);
            
            return response()->json([
                'data' => $files,
                'message' => __("filemanager::messages.file.retrieved_success")
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(FileUploadRequest $request)
    {
        try {
            $file = $this->fileService->upload(
                $request->file('file'),
                $request->input('folder_id')
            );
            
            return response()->json([
                'data' => $file,
                'message' => __("filemanager::messages.file.uploaded_success")
            ], Response::HTTP_CREATED);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(FileDeleteRequest $request, string $id)
    {
        try {
            $this->fileService->delete($id);
            
            return response()->json([
                'message' => __("filemanager::messages.file.deleted_success")
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function recentFiles(Request $request)
    {
        try {
            $limit = $request->input('limit', 5);
            $files = $this->fileService->getRecentFiles($limit);
            
            return response()->json([
                'data' => $files,
                'message' => __("filemanager::messages.file.retrieved_success")
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function serve($id, Request $request)
    {
        $file = $this->fileService->find($id);

        if (!$file || !Storage::disk('spaces')->exists($file->path)) {
            return response()->json(['error' => __("filemanager::messages.file.no_files_found")], 404);
        }

        $stream = Storage::disk('spaces')->readStream($file->path);
        $disposition = $request->query('download') == '1' ? 'attachment' : 'inline';

        return new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => $file->mime_type ?? Storage::disk('spaces')->mimeType($file->path),
            'Content-Disposition' => "{$disposition}; filename=\"{$file->name}\"",
        ]);
    }
}