<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserTwoFactorCode extends Model
{
    protected $table = 'user_two_factor_codes'; // Đặt tên bảng nếu không dùng mặc định

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'id' => 'string',
        // thêm các trường khác nếu cần
    ];

    protected $fillable = [
        'id',
        'user_id',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at'
    ];

    public $timestamps = true; // hoặc false nếu không dùng created_at / updated_at
    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
