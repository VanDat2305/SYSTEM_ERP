<?php

namespace Modules\FileManager\Repositories;

use Modules\FileManager\Models\Folder;
use Modules\FileManager\Interfaces\FolderRepositoryInterface;
use Illuminate\Support\Collection;

class FolderRepository implements FolderRepositoryInterface
{
    public function create(array $data): Folder
    {
        return Folder::create($data);
    }

    public function find(string $id): ?Folder
    {
        return Folder::find($id);
    }

    public function delete(string $id): bool
    {
        $folder = $this->find($id);
        return $folder ? $folder->delete() : false;
    }

    public function getByParentId(?string $parentId = null): Collection
    {
        return Folder::where('parent_id', $parentId)->get();
    }

    public function getFolderTree(?string $parentId = null): Collection
    {
        $folders = $this->getByParentId($parentId);
        
        return $folders->map(function ($folder) {
            $folder->children = $this->getFolderTree($folder->id);
            return $folder;
        });
    }
    public function findByPath(string $path): ?Folder
    {
        return Folder::where('path', $path)->first();
    }
}