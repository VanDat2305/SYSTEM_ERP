<?php

// app/Models/PackageNotifyLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageNotifyLog extends Model
{
    protected $table = 'package_notify_logs';
    protected $fillable = [
        'order_detail_id',
        'customer_id',
        'type',
        'milestone',
        'sent_at',
    ];
    public $timestamps = true;
}
