<?php

namespace Aphly\LaravelPayment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Aphly\Laravel\Models\Model;
use Illuminate\Support\Facades\Cache;

class Payment extends Model
{
    use HasFactory;
    protected $table = 'payment';
    protected $primaryKey = 'id';
    //public $timestamps = false;

    protected $fillable = [
        'method_id','transaction_id','status','amount','notify_func','success_func','fail_func','currency_code'
    ];

    public function findAll() {
        return Cache::rememberForever('payment', function () {
            return self::get()->keyBy('id')->toArray();
        });
    }


}
