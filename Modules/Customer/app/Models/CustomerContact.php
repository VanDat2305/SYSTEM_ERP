<?php

namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CustomerContact extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'customer_id',
        'contact_type',
        'value',
        'label',
        'is_primary',
        'note'
    ];

    protected $casts = [
        'id' => 'string',
        'customer_id' => 'string',
        'is_primary' => 'boolean'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function scopeByType($query, $type)
    {
        return $query->where('contact_type', $type);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}