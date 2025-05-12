<?php

namespace Modules\FileManager\Interfaces;


use Modules\FileManager\Models\Folder;
use Illuminate\Support\Collection;

interface FolderRepositoryInterface
{
    public function create(array $data): Folder;
    public function find(string $id): ?Folder;
    public function delete(string $id): bool;
    public function getByParentId(?string $parentId = null): Collection;
    public function getFolderTree(?string $parentId = null): Collection;
    public function findByPath(string $path): ?Folder;
}
