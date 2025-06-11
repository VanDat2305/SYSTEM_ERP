<?php

namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'customer_type',
        'full_name',
        'short_name',
        'gender',
        'date_of_birth',
        'tax_code',
        'industry',
        'province_code',
        'address',
        'identity_type',
        'identity_number',
        'position',
        'website',
        'team_id',
        'assigned_to',
        'is_active'
    ];

    protected $casts = [
        'id' => 'string',
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
        'team_id' => 'string',
        'assigned_to' => 'string'
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

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function representatives(): HasMany
    {
        return $this->hasMany(CustomerRepresentative::class);
    }

    public function primaryContacts(): HasMany
    {
        return $this->contacts()->where('is_primary', true);
    }

    public function phones(): HasMany
    {
        return $this->contacts()->where('contact_type', 'phone');
    }

    public function emails(): HasMany
    {
        return $this->contacts()->where('contact_type', 'email');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('customer_type', $type);
    }

    public function scopeByTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }
}