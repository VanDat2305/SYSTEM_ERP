<?php
namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Users\Enums\UserStatus;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use Modules\Users\Notifications\ResetPasswordNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
// use Modules\Users\Database\Factories\App/Models/UserFactory;
use Modules\Users\Models\UserTwoFactorCode;
// use Spatie\Activitylog\Traits\LogsActivity;
// use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable, HasRoles, SoftDeletes;
    // use LogsActivity;
    
    
    protected $guard_name = 'api';
    
    protected $fillable = ['id', 'name', 'email', 'password', 'last_login_at', 'status','two_factor_enabled', 'email_verified_at']; // THÊM CÁC CỘT CẦN THIẾT

    protected $hidden = ['password']; // Ẩn password khi trả về JSON

    protected $casts = [
        'status' => UserStatus::class, // Tự động cast trạng thái sang Enum
        'id' => 'string', // Đảm bảo Laravel đọc UUID đúng cách
        'email_verified_at' => 'datetime',
    ];
    public $incrementing = false;
    protected $keyType = 'string';

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
        static::deleting(function ($user) {
            // Ngăn chặn xóa chính mình
            if ($user->id === Auth::id()) {
                throw new \Exception('Bạn không thể xóa chính mình.');
            }
            if ($user->hasRole('superadmin')) {
                throw new \Exception('Không thể xóa tài khoản có quyền superadmin.');
            }
        });
        // static::updating(function ($model) {
        //     if ($model->isDirty('password')) {
        //         // Lọc bỏ giá trị password để không lưu ra log
        //         $model->password = '******';
        //     }
        // });
    }

        /**
     * Cấu hình log cho Spatie
     */
    // public function getActivitylogOptions(): LogOptions
    // {
    //     return LogOptions::defaults()
    //         ->useLogName('user') // tên log
    //         ->logOnly(['name', 'email', 'status', 'two_factor_enabled', 'password']) // chỉ log các trường này
    //         ->logOnlyDirty() // chỉ log nếu có thay đổi
    //         ->dontSubmitEmptyLogs(); // không log nếu không có gì thay đổi
    // }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function twoFactorCodes()
    {
        return $this->hasMany(UserTwoFactorCode::class);
    }
    
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_user')->withTimestamps()->withPivot('role');
    }

}
