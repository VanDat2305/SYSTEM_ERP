<?php

namespace Modules\Service\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;

class ServicePackageFeature extends Model
{
    use LogsActivity;

    protected $table = 'service_package_features';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];
    protected $casts = [
        'is_optional' => 'boolean',
        'is_customizable' => 'boolean',
        'limit_value' => 'integer',
        'used_count' => 'integer',
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
            ->logOnly(['feature_key', 'feature_name', 'feature_type', 'unit', 'limit_value', 'is_optional', 'is_customizable', 'display_order'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class, 'package_id');
    }
}