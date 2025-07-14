<?php

namespace Modules\FileManager\Services;
namespace Modules\FileManager\Services;

use Modules\FileManager\Models\File;
use Modules\FileManager\Interfaces\FileRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Log;
use Modules\FileManager\Interfaces\FolderRepositoryInterface;

class FileService
{
    protected FileRepositoryInterface $fileRepository;

    public function __construct(FileRepositoryInterface $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }
    public function find(string $fileId): ?File
    {
        return $this->fileRepository->find($fileId);
    }
    public function upload(UploadedFile $file, ?string $folderId = null, $objectId = null, $documentType = null): File
    {
        try {
            $folder = $folderId ? app(FolderRepositoryInterface::class)->find($folderId) : null;
            $path = $folder ? $folder->path : 'uploads';
            $fileName = $this->generateFileName($file);
            $fullPath = $path.'/'.$fileName;
            
            Storage::disk('spaces')->put($fullPath, file_get_contents($file));
            
            return $this->fileRepository->create([
                'id' => Str::uuid(),
                'name' => $fileName,
                'original_name' => $file->getClientOriginalName(),
                'path' => $fullPath,
                'url' => Storage::disk('spaces')->url($fullPath),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'folder_id' => $folderId,
                'category' => $this->detectCategory($file),
                'user_id' => auth()->id(),
                'object_id'     => $objectId,
                'document_type' => $documentType,
            ]);
            
        } catch (Exception $e) {
            Log::error("File upload failed: " . $e->getMessage());
            throw new Exception("Failed to upload file: " . $e->getMessage());
        }
    }
    /**
     * Upload multiple files.
     *
     * @param UploadedFile[] $files
     * @param string|null $folderId
     * @return File[]
     * @throws Exception
     */
    public function uploadMultiple(array $files, ?string $folderId = null): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue; // Bỏ qua nếu không phải là một instance hợp lệ
            }

            try {
                $folder = $folderId ? app(FolderRepositoryInterface::class)->find($folderId) : null;
                $path = $folder ? $folder->path : 'uploads';
                $fileName = $this->generateFileName($file);
                $fullPath = $path . '/' . $fileName;
                
                Log::info("Uploading file '{$file->getClientOriginalName()}' to path '{$fullPath}'");

                Storage::disk('spaces')->putFileAs($path, $file, $fileName);

                $uploadedFiles[] = $this->fileRepository->create([
                    'id' => Str::uuid(),
                    'name' => $fileName,
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $fullPath,
                    'url' => Storage::disk('spaces')->url($fullPath),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'folder_id' => $folderId,
                    'category' => $this->detectCategory($file),
                    'user_id' => auth()->id()
                ]);
            } catch (Exception $e) {
                Log::error("File upload failed for file '{$file->getClientOriginalName()}': " . $e->getMessage());
                // Optional: continue uploading other files even if one fails
                continue;
                // Hoặc nếu muốn dừng ngay khi gặp lỗi:
                throw new Exception("Failed to upload file '{$file->getClientOriginalName()}': " . $e->getMessage());
            }
        }

        return $uploadedFiles;
    }


    public function delete(string $fileId): void
    {
        try {
            $file = $this->fileRepository->find($fileId);
            
            if (!$file) {
                throw new Exception("File not found");
            }
            
            if (Storage::disk('spaces')->exists($file->path)) {
                Storage::disk('spaces')->delete($file->path);
            }
            
            $this->fileRepository->delete($fileId);
            
        } catch (Exception $e) {
            Log::error("File deletion failed: " . $e->getMessage());
            throw new Exception("Failed to delete file: " . $e->getMessage());
        }
    }

    private function generateFileName(UploadedFile $file): string
    {
        return Str::random(20).'_'.time().'.'.$file->getClientOriginalExtension();
    }

    private function detectCategory(UploadedFile $file): string
    {
        $mime = $file->getMimeType();
        
        if (str_starts_with($mime, 'image/')) return 'image';
        if (str_starts_with($mime, 'video/')) return 'video';
        if (str_starts_with($mime, 'audio/')) return 'audio';
        
        return 'document';
    }

    public function getFilesByFolder(?string $folderId = null): array
    {
        try {
            return $this->fileRepository->getByFolderId($folderId)->toArray();
        } catch (Exception $e) {
            Log::error("Failed to get files by folder: " . $e->getMessage());
            throw new Exception("Failed to get files: " . $e->getMessage());
        }
    }

    public function getRecentFiles(int $limit = 5): array
    {
        try {
            return $this->fileRepository->getRecentFiles($limit)->toArray();
        } catch (Exception $e) {
            Log::error("Failed to get recent files: " . $e->getMessage());
            throw new Exception("Failed to get recent files: " . $e->getMessage());
        }
    }
    public function uploadFromContent(
        string $content, 
        string $originalName, 
        ?string $folderId = null, 
        ?string $mimeType = 'application/octet-stream'
    ): File {
        try {
            $folder = $folderId ? app(FolderRepositoryInterface::class)->find($folderId) : null;
            $path = $folder ? $folder->path : 'uploads';

            // Đảm bảo tên file duy nhất
            $extension = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'bin';
            $fileName = Str::random(20) . '_' . time() . '.' . $extension;
            $fullPath = $path . '/' . $fileName;

            // Lưu file vào storage
            Storage::disk('spaces')->put($fullPath, $content);

            // Nếu không truyền mimeType thì thử đoán theo tên file
            if (!$mimeType || $mimeType === 'application/octet-stream') {
                $mimeType = \Illuminate\Support\Facades\File::mimeType(storage_path('app/' . $fullPath)) ?: 'application/octet-stream';
            }

            // Tự động category
            $category = 'document';
            if (str_starts_with($mimeType, 'image/')) $category = 'image';
            if (str_starts_with($mimeType, 'video/')) $category = 'video';
            if (str_starts_with($mimeType, 'audio/')) $category = 'audio';

            return $this->fileRepository->create([
                'id' => Str::uuid(),
                'name' => $fileName,
                'original_name' => $originalName,
                'path' => $fullPath,
                'url' => Storage::disk('spaces')->url($fullPath),
                'mime_type' => $mimeType,
                'size' => strlen($content),
                'folder_id' => $folderId,
                'category' => $category,
                'user_id' => auth()->id(),
            ]);
        } catch (Exception $e) {
            Log::error("File upload from content failed: " . $e->getMessage());
            throw new Exception("Failed to upload file from content: " . $e->getMessage());
        }
    }
    public function getFilesByObjectId(string $objectId): array
    {
        try {
            return $this->fileRepository->getByObjectId($objectId)->toArray();
        } catch (Exception $e) {
            Log::error("Failed to get files by object ID: " . $e->getMessage());
            throw new Exception("Failed to get files by object ID: " . $e->getMessage());
        }
    }
    public function updateFile(string $fileId, array $data): File
    {
        try {
            $file = $this->fileRepository->find($fileId);
            if (!$file) {
                throw new Exception("File not found");
            }

            // Cập nhật các trường cần thiết
            $file->fill($data);
            $file->save();

            return $file;
        } catch (Exception $e) {
            Log::error("Failed to update file: " . $e->getMessage());
            throw new Exception("Failed to update file: " . $e->getMessage());
        }
    }
    public function syncCustomerFiles( string $customerId, array $fileIds): void
    {
        try {
            // Thêm các file mới
            foreach ($fileIds as $fileId) {
                $file = $this->fileRepository->find($fileId['id']);
                if ($file) {
                    $file->object_id = $customerId;
                    $file->save();
                }
            }
        } catch (Exception $e) {
            Log::error("Failed to sync customer files: " . $e->getMessage());
            throw new Exception("Failed to sync customer files: " . $e->getMessage());
        }
    }
}