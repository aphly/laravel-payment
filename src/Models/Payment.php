<?php

namespace Aphly\LaravelPayment\Models;

use Aphly\Laravel\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Aphly\Laravel\Models\Model;
use Illuminate\Support\Facades\Log;

class Payment extends Model
{
    use HasFactory;
    protected $table = 'payment';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    //public $timestamps = false;

    protected $fillable = [
        'id','method_id','method_name','transaction_id','cred_id','status','amount','notify_func','success_url','fail_url','currency_code','cancel_url'
    ];

    function orderId($md5 = true){
        if($md5){
            return md5(uniqid(mt_rand(), true));
        }else{
            return date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        }
    }

    //status 1未支付 2已支付

    public $log;

    function __construct(){
        parent::__construct();
        $this->log = Log::channel('payment');
    }

    public function make($data)
    {
        $paymentMethod = PaymentMethod::where('id',$data['method_id'])->where('status',1)->firstOrError();
        $data['amount'] = number_format(floatval($data['amount']),2);
        if($data['amount']>0){
            $data['id'] = $this->orderId();
            $data['method_name'] = $paymentMethod->name;
            $data['currency_code'] = $data['currency_code']??'USD';
            return Payment::create($data);
        }else{
            throw new ApiException(['code'=>1,'msg'=>'payment amount error']);
        }
    }

    public function pay($redirect = true)
    {
        $this->log->debug('payment_pay start');
        $class = '\Aphly\LaravelPayment\Models\\'.ucfirst($this->method_name);
        if (class_exists($class)){
            (new $class)->pay($this,$redirect);
        }
    }
}
