<?php
namespace Modules\Users\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['name', 'description', 'is_active'];
    protected $casts = [
        'is_active' => 'boolean',
    ];
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'team_user')->withTimestamps()->withPivot('role');
    }
}