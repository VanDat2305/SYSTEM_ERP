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
    public function upload(UploadedFile $file, ?string $folderId = null): File
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
                'user_id' => auth()->id()
            ]);
            
        } catch (Exception $e) {
            Log::error("File upload failed: " . $e->getMessage());
            throw new Exception("Failed to upload file: " . $e->getMessage());
        }
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
}