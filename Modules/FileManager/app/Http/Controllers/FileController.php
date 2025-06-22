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
            $file = $this->fileService->uploadMultiple(
                $request->file('files'),
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
    public function convertToPdfBase64($fileId)
    {
        // 1. Lấy link file docx từ API file manager
        $remoteUrl = config("app.url") . "/v1/file/{$fileId}";

        // 2. Tải file docx về local
        $tmpDir = storage_path('app/tmp');
        if (!file_exists($tmpDir)) mkdir($tmpDir, 0777, true);
        $tmpDocx = $tmpDir . '/' . uniqid('doc_') . '.docx';
        file_put_contents($tmpDocx, file_get_contents($remoteUrl));

        // 3. Convert bằng LibreOffice CLI
        $command = "libreoffice --headless --convert-to pdf --outdir " . escapeshellarg($tmpDir) . " " . escapeshellarg($tmpDocx);
        exec($command);

        // 4. Tìm file PDF kết quả
        $pdfPath = $tmpDir . '/' . pathinfo($tmpDocx, PATHINFO_FILENAME) . '.pdf';
        if (!file_exists($pdfPath)) {
            // Dọn file tạm
            @unlink($tmpDocx);
            return response()->json(['success' => false, 'message' => 'Không convert được PDF'], 500);
        }

        // 5. Đọc file PDF ra base64
        $base64 = base64_encode(file_get_contents($pdfPath));

        // 6. Dọn file tạm
        @unlink($tmpDocx);
        @unlink($pdfPath);

        // 7. Trả response
        return response()->json([
            'success' => true,
            'base64_pdf' => $base64
        ]);
    }
}
