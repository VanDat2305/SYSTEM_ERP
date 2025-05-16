<?php

namespace Modules\FileManager\Repositories;


use Modules\FileManager\Models\File;
use Modules\FileManager\Interfaces\FileRepositoryInterface;
use Illuminate\Support\Collection;

class FileRepository implements FileRepositoryInterface
{
    public function create(array $data): File
    {
        return File::create($data);
    }

    public function find(string $id): ?File
    {
        return File::find($id);
    }

    public function delete(string $id): bool
    {
        $file = $this->find($id);
        return $file ? $file->delete() : false;
    }

    public function getByFolderId(?string $folderId = null): Collection
    {
        $query = File::query();
        
        if ($folderId) {
            $query->where('folder_id', $folderId);
        } else {
            $query->whereNull('folder_id');
        }
        
        return $query->get();
    }

    public function getRecentFiles(int $limit = 5): Collection
    {
        return File::orderBy('created_at', 'desc')
                   ->limit($limit)
                   ->get();
    }
    public function uploadMultiple(array $files, ?string $folderId = null): array
    {
        $uploadedFiles = [];
        foreach ($files as $file) {
            $uploadedFiles[] = $this->create($file, $folderId);
        }
        return $uploadedFiles;
    }
}