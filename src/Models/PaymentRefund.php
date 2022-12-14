<?php

namespace Aphly\LaravelPayment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Aphly\Laravel\Models\Model;

class PaymentRefund extends Model
{
    use HasFactory;
    protected $table = 'payment';
    protected $primaryKey = 'id';
    //public $timestamps = false;

    protected $fillable = [
        'payment_id','amount','status'
    ];


}
