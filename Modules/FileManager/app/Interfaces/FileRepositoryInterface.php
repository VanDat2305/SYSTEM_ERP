<?php

namespace Modules\FileManager\Interfaces;

use Modules\FileManager\Models\File;
use Illuminate\Support\Collection;

interface FileRepositoryInterface
{
    public function create(array $data): File;
    public function find(string $id): ?File;
    public function delete(string $id): bool;
    public function getByFolderId(?string $folderId = null): Collection;
    public function getRecentFiles(int $limit = 5): Collection;
    public function uploadMultiple(array $files, ?string $folderId = null): array;
    public function getByObjectId(string $objectId): Collection;
}