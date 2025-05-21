<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ObjectType extends Model
{
    use HasUuids;

    protected $fillable = ['code', 'name', 'created_by', 'tenant_id', 'order', 'status'];

    public function object_items()
    {
        return $this->hasMany(ObjectItem::class, 'object_type_id', 'id')->orderBy('order', 'asc');
    }
}