<?php

namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderPackageFeature extends Model
{
    use SoftDeletes;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'order_package_features';

    protected $fillable = [
        'id', 'order_detail_id', 'feature_key', 'feature_name', 'feature_type',
        'unit', 'limit_value', 'is_optional', 'is_customizable', 'is_active', 'display_order'
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

    public function detail() { return $this->belongsTo(OrderDetail::class, 'order_detail_id'); }
}