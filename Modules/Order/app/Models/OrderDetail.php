<?php

namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderDetail extends Model
{
    use SoftDeletes;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'order_details';

    protected $fillable = [
        'id', 'order_id', 'service_type', 'service_package_id', 'package_code', 'package_name', 'base_price',
        'quantity', 'total_price', 'currency', 'start_date', 'end_date', 'is_active',
        'tax_rate', 'tax_included', 'tax_amount', 'total_with_tax'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function features() { return $this->hasMany(OrderPackageFeature::class, 'order_detail_id'); }
    public function order() { return $this->belongsTo(Order::class, 'order_id'); }
}