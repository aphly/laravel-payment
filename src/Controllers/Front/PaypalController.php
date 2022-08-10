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

    public function pay($payment)
    {
        $this->log->debug('payment_paypal pay start');
        if($payment->id){
            $purchaseUnits = [
                [
                    'amount' => [
                        'currency_code' => $payment->currency_code,
                        'value' => $payment->amount,
                    ],
                    'invoice_id'=>$payment->id
                ],
            ];
            $applicationContext = [
                'brand_name' => env('APP_NAME'),
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
                'return_url' => Func::siteUrl(request()->url()).'/payment/return',
                'cancel_url' => $payment->cancel_url,
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

    public function callBack($func,$payment)
    {
        if($func && $payment){
            list($class,$func) = explode('@',$func);
            if (class_exists($class) && method_exists($class,$func)){
                return (new $class)->{$func}($payment);
            }
        }
        throw new ApiException(['code'=>2,'msg'=>'payment callBack fail']);
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
                    if($payment->status==1){
                        $capture = $this->order->capture($order_id);
                        $this->log->debug('payment_paypal return APPROVED to COMPLETED',$capture);
                        if(isset($capture['status']) && $capture['status']=='COMPLETED'){
                            $payment->status=2;
                            if($payment->save() && $payment->notify_func){
                                list($class,$func) = explode('@',$payment->notify_func);
                                if (class_exists($class) && method_exists($class,$func)){
                                    $this->log->debug('payment_paypal notify func capture');
                                    (new $class)->{$func}($payment);
                                }
                                return $this->callBack($payment->success_func,$payment);
                            }
                            if($payment->success_func){
                                list($class,$func) = explode('@',$payment->success_func);
                                if (class_exists($class) && method_exists($class,$func)){
                                    $this->log->debug('payment_paypal return success_func');
                                    return (new $class)->{$func}($payment);
                                }
                            }
                            throw new ApiException(['code'=>0,'msg'=>'payment success']);
                        }else{
                            $this->log->debug('payment_paypal return APPROVED to COMPLETED error');
                            return $this->callBack($payment->fail_func,$payment);
                        }
                    }else if($payment->status>1){
                        return $this->callBack($payment->success_func,$payment);
                    }else{
                        return $this->callBack($payment->fail_func,$payment);
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
        //$input = request()->all();
        $raw_post_data = file_get_contents('php://input');
        if($raw_post_data){
            $input = json_decode($raw_post_data,true);
            if(!$input){
                throw new ApiException(['code'=>-2,'msg'=>'fail']);
            }
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
        }else{
            throw new ApiException(['code'=>-1,'msg'=>'fail']);
        }
    }

    public function show($payment)
    {
        $info = $this->order->show($payment->transaction_id);
        throw new ApiException(['code'=>0,'msg'=>'success','data'=>['info'=>$info]]);
    }


}
