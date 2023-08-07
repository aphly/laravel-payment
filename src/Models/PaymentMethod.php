<?php

namespace Aphly\LaravelPayment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Aphly\Laravel\Models\Model;
use Illuminate\Support\Facades\Cache;

class PaymentMethod extends Model
{
    use HasFactory;
    protected $table = 'payment_method';
    protected $primaryKey = 'id';
    //public $timestamps = false;

    protected $fillable = [
        'name','sort','status','default'
    ];

    public function findAll() {
        return Cache::rememberForever('payment_method', function () {
            return self::where(['status'=>1])->orderBy('sort','desc')->get()->keyBy('id')->toArray();
        });
    }


    function params(){
        return $this->hasMany(PaymentMethodParams::class,'method_id','id');
    }
}
