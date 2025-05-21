<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ObjectMeta extends Model
{
    use HasUuids;
    protected $table = 'object_meta';
    protected $fillable = ['object_id', 'key', 'value'];
}