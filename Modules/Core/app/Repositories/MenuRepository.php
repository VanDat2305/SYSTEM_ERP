<?php

namespace Modules\Core\Repositories;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Core\Models\Menu;
use Modules\Core\Interfaces\MenuRepositoryInterface;

class MenuRepository implements MenuRepositoryInterface
{
    public function all()
    {
        return Menu::get();
    }

    public function getAll($perPage = 2)
    {
        return Menu::paginate($perPage);
    }

    public function find(string $id)
    {
        return Menu::findOrFail($id);
    }

    public function create(array $data)
    {
        return Menu::create($data);
    }

    public function update(string $id, array $data)
    {
        $menu = Menu::findOrFail($id);
        if (!$menu) {
            throw new ModelNotFoundException("Menu not found");
        }
        $menu->update($data);
        return $menu;
    }

    public function delete(string $id)
    {
        $menu = Menu::findOrFail($id);
        if (!$menu) {
            throw new ModelNotFoundException("Menu not found");
        }
        return $menu->delete();
    }
}
