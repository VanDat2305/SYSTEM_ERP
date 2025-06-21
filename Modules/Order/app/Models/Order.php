<?php

namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Order\Models\OrderDetail;

class Order extends Model
{
    use SoftDeletes;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'orders';

    protected $fillable = [
        'id', 'order_code', 'customer_id', 'opportunity_id', 'order_status',
        'currency', 'total_amount', 'billing_cycle', 'contract_id', 'team_id', 'created_by',
        'reason_cancel'
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
    public function creator()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'created_by');
    }

    public function getCreatorNameAttribute()
    {
        return $this->creator()->value('name');
    }
    public function details() { return $this->hasMany(OrderDetail::class, 'order_id'); }
    public function customer() { return $this->belongsTo(\Modules\Customer\Models\Customer::class, 'customer_id'); }
    public function team() { return $this->belongsTo(\Modules\Users\Models\Team::class, 'team_id'); }
}