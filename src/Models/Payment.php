<?php

namespace Aphly\LaravelPayment\Models;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\LaravelCommon\Models\Currency;
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

    public function pay($redirect = true,$id=false)
    {
        $this->log->debug('payment_pay start');
        if($id){
            $info = Payment::where('id',$id)->where('status',1)->firstOrError();
        }else{
            $info = $this;
        }
        $class = '\Aphly\LaravelPayment\Models\\'.ucfirst($info->method_name);
        if (class_exists($class)){
            (new $class)->pay($info,$redirect);
        }
    }

    public function show($payment,$return=false)
    {
        $this->log->debug('payment show start');
        $class = '\Aphly\LaravelPayment\Models\\'.ucfirst($payment->method_name);
        if (class_exists($class)){
            $info = (new $class)->show($payment,$return);
            if($return){
                return $info;
            }
            throw new ApiException(['code'=>0,'msg'=>'success','data'=>['info'=>$info]]);
        }
    }

    public function refund($payment,$data)
    {
        //'payment_id','amount','status','reason'
        $this->log->debug('payment_refund start');
        $paymentRefund = PaymentRefund::where('payment_id',$payment->id)->where('status',2)->get();
        $refund_total = $data['amount'];
        foreach ($paymentRefund as $val){
            $refund_total += $val['amount'];
        }
        if($refund_total<=$payment->amount){
            $data['status'] = 1;
            list(,$data['amount_format']) = Currency::codeFormat($data['amount'],$payment->currency_code);
            $refund = PaymentRefund::create($data);
            if($refund->id){
                $class = '\Aphly\LaravelPayment\Models\\'.ucfirst($payment->method_name);
                if (class_exists($class)){
                    (new $class)->refund($payment,$refund);
                }else{
                    throw new ApiException(['code'=>3,'msg'=>'class error']);
                }
            }else{
                throw new ApiException(['code'=>2,'msg'=>'refund error']);
            }
        }else{
            throw new ApiException(['code'=>1,'msg'=>'refund amount error'.$refund_total.'||'.$payment->amount]);
        }
    }

    public function refund_api($payment_id,$amount,$reason=false)
    {
        if($amount>0){
            $data['amount'] = $amount;
            $data['reason'] = $reason??'refund';
            $payment = Payment::where('id',$payment_id)->where('status',2)->firstOrError();
            $data['payment_id'] = $payment_id;
            $this->refund($payment,$data);
        }else{
            throw new ApiException(['code'=>1,'msg'=>'cancel refund amount error']);
        }
    }


    public function return_redirect($url,$msg=false)
    {
        if($msg){
            $msg = urlencode($msg);
            if(!str_contains($url, '?')){
                $url.='?msg='.$msg;
            }else{
                $url.='&msg='.$msg;
            }
        }
        redirect($url)->send();
    }
}
