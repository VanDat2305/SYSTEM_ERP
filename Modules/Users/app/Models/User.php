<?php
namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Users\Enums\UserStatus;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

// use Modules\Users\Database\Factories\App/Models/UserFactory;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles;
    
    
    protected $fillable = ['id', 'name', 'email', 'password', 'last_login_at', 'status']; // THÊM CÁC CỘT CẦN THIẾT

    protected $hidden = ['password']; // Ẩn password khi trả về JSON

    protected $casts = [
        'status' => UserStatus::class, // Tự động cast trạng thái sang Enum
        'id' => 'string', // Đảm bảo Laravel đọc UUID đúng cách
    ];
    
    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }
    
}
