<?php

namespace Modules\Service\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;


class ServicePackage extends Model
{
    use LogsActivity;

    protected $table = 'service_packages';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];
    protected $casts = [
        'is_active' => 'boolean',
        'base_price' => 'float',
        'tax_rate' => 'integer',
        'tax_included' => 'boolean',
    ];
    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type_service', 'customer_type', 'package_code', 'package_name', 'description', 'base_price', 'currency', 'billing_cycle', 'is_active', 'display_order', 'tax_rate', 'tax_included'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function features(): HasMany
    {
        return $this->hasMany(ServicePackageFeature::class, 'package_id');
    }
}