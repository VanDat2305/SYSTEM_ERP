<?php

namespace Modules\Core\Services;

use Modules\Core\Repositories\MenuRepository;

class MenuService
{
    protected $menuRepository;

    public function __construct(MenuRepository $menuRepository)
    {
        $this->menuRepository = $menuRepository;
    }

    public function getAllMenus($perPage = 2)
    {
        return $this->menuRepository->getAll($perPage);
    }

    public function createMenu(array $data)
    {
        return $this->menuRepository->create($data);
    }

    public function updateMenu(string $id, array $data)
    {
        return $this->menuRepository->update($id, $data);
    }

    public function deleteMenu(string $id)
    {
        return $this->menuRepository->delete($id);
    }

    public function findMenu(string $id)
    {
        return $this->menuRepository->find($id);
    }
}
