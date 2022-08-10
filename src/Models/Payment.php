<?php

namespace Aphly\LaravelPayment\Models;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\Laravel\Libs\Func;
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

    public function make($data)
    {
//        $data['method_id'] = 1;
//        $data['amount'] = 10.00;
//        $data['cancel_url'] = 'http://test2.com/payment/cancel_url';
//        $data['notify_func'] = '\Aphly\LaravelPayment\Controllers\Front\PayController@t1';
//        $data['success_func'] = '\Aphly\LaravelPayment\Controllers\Front\PayController@t2';
//        $data['fail_func'] = '\Aphly\LaravelPayment\Controllers\Front\PayController@t3';
        $data['amount'] = number_format(floatval($data['amount']),2);
        if($data['amount']>0){
            $data['method_id'] = $data['method_id']??1;
            $data['currency_code'] = $data['currency_code']??'USD';
            $data['return_url'] = Func::siteUrl(request()->url()).'/payment/return';
            return Payment::create($data);
        }else{
            throw new ApiException(['code'=>1,'msg'=>'payment amount error']);
        }
    }
}
