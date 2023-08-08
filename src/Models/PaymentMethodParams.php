<?php

namespace Aphly\LaravelPayment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Aphly\Laravel\Models\Model;
use Illuminate\Support\Facades\Cache;

class PaymentMethodParams extends Model
{
    use HasFactory;
    protected $table = 'payment_method_params';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'key','val','method_id'
    ];

    public function findAll($cache=true) {
        if($cache){
            return Cache::rememberForever('payment_method_params', function () {
                return self::get()->keyBy('id')->toArray();
            });
        }else{
            return self::get()->keyBy('id')->toArray();
        }
    }


}
