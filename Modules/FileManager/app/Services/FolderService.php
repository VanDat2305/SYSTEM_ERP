<?php

namespace Modules\FileManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\FileManager\Models\Folder;
use Modules\FileManager\Interfaces\FolderRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FolderService
{
    protected FolderRepositoryInterface $folderRepository;

    public function __construct(FolderRepositoryInterface $folderRepository)
    {
        $this->folderRepository = $folderRepository;
    }

    public function create(array $data): Folder
    {
        DB::beginTransaction();
        
        try {
            $parent = isset($data['parent_id']) ? $this->folderRepository->find($data['parent_id']) : null;
            
            // Generate initial path
            $path = $this->generatePath($data['name'], $parent);
            
            // Check for existing path and generate unique one if needed
            $path = $this->ensureUniquePath($path);
            
            $folder = $this->folderRepository->create([
                'id' => Str::uuid(),
                'name' => $data['name'],
                'path' => $path,
                'parent_id' => $data['parent_id'] ?? null,
                'user_id' => auth()->id(),
                'description' => $data['description'] ?? null
            ]);
            
            // Create physical directory
            if (!Storage::disk('spaces')->makeDirectory($folder->path)) {
                throw new \Exception(__('filemanager::messages.folder.create_failed'));
            }
            
            DB::commit();
            return $folder;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            if (isset($folder) && isset($folder->path)) {
                Storage::disk('spaces')->deleteDirectory($folder->path);
            }
            
            throw new \Exception("Folder creation failed: " . $e->getMessage());
        }
    }

    /**
     * Ensures the path is unique by appending a counter if needed
     */
    private function ensureUniquePath(string $originalPath): string
    {
        $path = $originalPath;
        $counter = 1;
        
        while ($this->folderRepository->findByPath($path)) {
            $path = $originalPath . '-' . $counter;
            $counter++;
        }
        
        return $path;
    }

    public function delete(string $folderId): void
    {
        DB::beginTransaction();
        
        try {
            $folder = $this->folderRepository->find($folderId);
            
            if (!$folder) {
                throw new \Exception(__('filemanager::messages.folder.not_found'));
            }
            
            $this->deleteFolderRecursive($folder);
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Folder deletion failed: " . $e->getMessage());
        }
    }

    private function deleteFolderRecursive(Folder $folder): void
    {
        try {
            // Delete all files in folder
            foreach ($folder->files as $file) {
                if (!Storage::disk('spaces')->delete($file->path)) {
                    throw new \Exception("Failed to delete file: " . $file->path);
                }
                $file->delete();
            }
            
            // Delete subfolders
            foreach ($folder->children as $child) {
                $this->deleteFolderRecursive($child);
            }
            
            // Delete physical folder
            if (!Storage::disk('spaces')->deleteDirectory($folder->path)) {
                throw new \Exception("Failed to delete directory: " . $folder->path);
            }
            
            // Delete from database
            $this->folderRepository->delete($folder->id);
            
        } catch (\Exception $e) {
            throw new \Exception("Recursive deletion failed for folder {$folder->id}: " . $e->getMessage());
        }
    }

    private function generatePath(string $name, ?Folder $parent): string
    {
        return $parent ? $parent->path.'/'.$name : $name;
    }

    public function getFolderTree(?string $parentId = null): array
    {
        try {
            return $this->folderRepository->getFolderTree($parentId)->toArray();
        } catch (\Exception $e) {
            throw new \Exception("Failed to retrieve folder tree: " . $e->getMessage());
        }
    }
}