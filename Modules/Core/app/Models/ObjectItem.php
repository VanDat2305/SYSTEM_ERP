<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ObjectItem extends Model
{
    use HasUuids;
    protected $table = 'objects';
    protected $fillable = ['object_type_id', 'code', 'name', 'created_by', 'tenant_id', 'order', 'status'];

    public function meta() {
        return $this->hasMany(ObjectMeta::class, 'object_id');
    }
    public function object_type() {
        return $this->belongsTo(ObjectType::class, 'object_type_id', 'id');
    }
}