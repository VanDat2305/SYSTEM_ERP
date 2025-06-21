<?php

namespace Modules\Order\Models\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Order\Database\Factories\App/models/PaymentFactory;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): App/models/PaymentFactory
    // {
    //     // return App/models/PaymentFactory::new();
    // }
}
