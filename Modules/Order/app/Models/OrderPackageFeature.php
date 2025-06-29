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
        'id',
        'order_detail_id',
        'feature_key',
        'feature_name',
        'feature_type',
        'unit',
        'limit_value',
        'used_count',
        'is_optional',
        'is_customizable',
        'is_active',
        'display_order'
    ];
    protected $casts = [
        'limit_value' => 'integer',
        'used_count' => 'integer',
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

    public function detail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id');
    }

    public function getTotalQuotaAttribute()
    {
        return $this->limit_value * ($this->detail->quantity ?? 1);
    }
    public function getQuotaRemainAttribute()
    {
        return $this->total_quota - $this->used_count;
    }
}
