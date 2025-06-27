<?php

namespace Modules\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Account\Database\Factories\App/models/AccountFactory;

class Account extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['id', 'name', 'email', 'phone', 'address', 'website','erp_customer_id', 'code', 'type', 'tax_code', 'is_active', 'activated_at', 'password'];
    protected $table = 'accounts';
    protected $primaryKey = 'id';
    public $incrementing = false; // Sử dụng UUID, không tự động tăng
    protected $keyType = 'string'; // Kiểu khóa chính là chuỗi (UUID)
    protected $hidden = ['password']; // Ẩn mật khẩu trong các truy vấn
    // protected static function newFactory(): App/models/AccountFactory
    // {
    //     // return App/models/AccountFactory::new();
    // }
}
