<?php

namespace Aphly\LaravelPayment\Controllers\Front;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\Laravel\Libs\Func;
use Aphly\LaravelPayment\Controllers\Controller;
use Aphly\LaravelPayment\Models\Payment;

use Aphly\LaravelPayment\Services\Paypal\Order;
use Illuminate\Support\Facades\Log;

class PaypalController extends Controller
{
    public $order;
    public $log;

    function __construct(){
        parent::__construct();
        $this->order = new Order;
        $this->log = Log::channel('payment');
    }

    public function pay($data)
    {
        $this->log->debug('payment_paypal pay start');
        $amount = number_format(floatval($data['amount']),2);
        $data['currency_code'] = $data['currency_code']??'USD';
        $data['return_url'] = Func::siteUrl(request()->url()).'/payment/return';
        $payment = Payment::create($data);
        if($amount && $payment->id){
            $purchaseUnits = [
                [
                    'amount' => [
                        'currency_code' => $data['currency_code'],
                        'value' => $amount,
                    ],
                    'invoice_id'=>$payment->id
                ],
            ];
            $applicationContext = [
                'brand_name' => env('APP_NAME'),
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
                'return_url' => $data['return_url'],
                'cancel_url' => $data['cancel_url'],
            ];
            $res_arr = $this->order->create($purchaseUnits, 'CAPTURE', $applicationContext);
            if($res_arr['id']){
                $pay_url = $this->order->getLinkByRel($res_arr['links'],'approve');
                $payment->transaction_id = $res_arr['id'];
                $payment->save();
                redirect($pay_url)->cookie('payment_token', encrypt($payment->id.','.$res_arr['id']), 60)->send();
            }else{
                throw new ApiException(['code'=>2,'msg'=>'payment create error']);
            }
        }else{
            throw new ApiException(['code'=>1,'msg'=>'payment id error']);
        }
    }

    public function return($method_id)
    {
        //payment/return?token=7U168763NS774425V&PayerID=5JBR62CD2ZXS4
        $request = request();
        $payment_token = decrypt($_COOKIE["payment_token"]);
        if($payment_token){
            list($payment_id,$order_id) = explode(',',$payment_token);
            if($order_id == $request->query('token')){
                $this->log->debug('payment_paypal return start',$request->all());
                $payment = Payment::where(['id'=>$payment_id,'method_id'=>$method_id])->first();
                if(!empty($payment)){
                    $info = $this->order->show($order_id);
                    $this->log->debug('payment_paypal return ',$info);
                    if($info['status']=='COMPLETED'){
                        if($payment->success_func){
                            list($class,$func) = explode('@',$payment->success_func);
                            if (class_exists($class) && method_exists($class,$func)){
                                return (new $class)->{$func}($payment);
                            }
                        }
                        throw new ApiException(['code'=>3,'msg'=>'payment COMPLETED']);
                    }else if($info['status']=='APPROVED' && $payment->status==1){
                        $capture = $this->order->capture($order_id);
                        $this->log->debug('payment_paypal return APPROVED to COMPLETED',$capture);
                        if($payment->success_func){
                            list($class,$func) = explode('@',$payment->success_func);
                            if (class_exists($class) && method_exists($class,$func)){
                                $this->log->debug('payment_paypal return success_func');
                                return (new $class)->{$func}($payment);
                            }
                        }
                        throw new ApiException(['code'=>0,'msg'=>'payment success']);
                    }else{
                        if($payment->fail_func){
                            list($class,$func) = explode('@',$payment->fail_func);
                            if (class_exists($class) && method_exists($class,$func)){
                                $this->log->debug('payment_paypal return fail_func');
                                return (new $class)->{$func}($payment);
                            }
                        }
                        throw new ApiException(['code'=>2,'msg'=>'fail_func null']);
                    }
                }else{
                    throw new ApiException(['code'=>1,'msg'=>'fail']);
                }
            }else{
                throw new ApiException(['code'=>-1,'msg'=>'token error']);
            }
        }else{
            throw new ApiException(['code'=>-2,'msg'=>'payment_token error']);
        }
    }

    public function notify($method_id)
    {
        $input = request()->all();
        $this->log->debug('payment_paypal notify start',$input);
        $order_id = $input['resource']['supplementary_data']['related_ids']['order_id'];
        $invoice_id = $input['resource']['invoice_id'];
        $payment = Payment::where(['transaction_id'=>$order_id,'id'=>$invoice_id])->first();
        if(!empty($payment)){
            if($payment->status!=1){
                throw new ApiException('');
            }
            $info = $this->order->show($order_id);
            if($info['status']=='COMPLETED'){
                $payment->status=2;
                if($payment->save() && $payment->notify_func){
                    list($class,$func) = explode('@',$payment->notify_func);
                    if (class_exists($class) && method_exists($class,$func)){
                        $this->log->debug('payment_paypal notify func');
                        (new $class)->{$func}($payment);
                    }
                }
                throw new ApiException('');
            }else{
                throw new ApiException(['code'=>2,'msg'=>'fail']);
            }
        }else{
            throw new ApiException(['code'=>1,'msg'=>'fail']);
        }
    }

    public function show($payment)
    {
        $info = $this->order->show($payment->transaction_id);
        throw new ApiException(['code'=>0,'msg'=>'success','data'=>['info'=>$info]]);
    }


}
