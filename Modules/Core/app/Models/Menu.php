<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'route',
        'permission_name',
        'icon',
        'sort_order',
        'status',
    ];

    // Mối quan hệ với menu cha
    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }
    protected $casts = [
        'id' => 'string',
    ];
    
    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }
}
