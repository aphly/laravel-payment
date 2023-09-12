<?php

namespace Aphly\LaravelPayment\Models;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\LaravelPayment\Services\Paypal\Order;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

class Paypal
{
    public $order;
    public $log;

    function __construct(){
        $this->order = new Order;
        $this->log = Log::channel('payment');
    }

    public function pay($payment,$redirect=true)
    {
        if($payment->id){
            $this->log->debug('payment_paypal pay start');
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
                'brand_name' => config('common.hostname'),
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
                'return_url' => url('/payment/paypal/return'),
                'cancel_url' => $payment->cancel_url,
            ];
            $res_arr = $this->order->create($purchaseUnits, 'CAPTURE', $applicationContext);
            if($res_arr && $res_arr['id']){
                $this->log->debug('payment_paypal pay create paypal_id: '.$res_arr['id']);
                $pay_url = $this->order->getLinkByRel($res_arr['links'],'approve');
                $payment->transaction_id = $res_arr['id'];
                $payment->save();
                if($redirect){
                    redirect($pay_url)->cookie('payment_token', encrypt($payment->id.','.$res_arr['id']), 60)->send();
                }else{
                    throw new ApiException(['code'=>0,'msg'=>'success','data'=>['redirect'=>$pay_url]],cookie('payment_token',$payment->id.','.$res_arr['id'], 60));
                }
            }else{
                throw new ApiException(['code'=>2,'msg'=>'payment create error']);
            }
        }else{
            throw new ApiException(['code'=>1,'msg'=>'payment id error']);
        }
    }

    public function callBack($classfunc,$payment)
    {
        if($classfunc && $payment){
            list($class,$func) = explode('@',$classfunc);
            if (class_exists($class) && method_exists($class,$func)){
                $this->log->debug('payment_paypal notify callBack '.$classfunc);
                (new $class)->{$func}($payment);
            }
        }
    }

    public function return($method_id)
    {
        //payment/return?token=7U168763NS774425V&PayerID=5JBR62CD2ZXS4
        $request = request();
        $payment_token = Cookie::get('payment_token');
        if(!$payment_token){
            $payment_token = decrypt($_COOKIE["payment_token"]);
        }
        if($payment_token){
            list($payment_id,$transaction_id) = explode(',',$payment_token);
            if($transaction_id == $request->query('token')){
                $this->log->debug('payment_paypal return start',$request->all());
                $payment = Payment::where(['id'=>$payment_id,'method_id'=>$method_id])->first();
                if(!empty($payment)){
                    if($payment->status==0){
                        $capture = $this->order->capture($transaction_id);
                        //$this->log->debug('payment_paypal return APPROVED to COMPLETED',$capture);
                        if(isset($capture['status']) && $capture['status']=='COMPLETED'){
                            $payment->status=1;
                            $payment->notify_type='return';
                            $payment->cred_id=$capture['purchase_units'][0]['payments']['captures']['0']['id'];
                            if($payment->save() && $payment->notify_func){
                                $this->callBack($payment->notify_func,$payment);
                            }
                            $payment->return_redirect($payment->success_url);
                        }else{
                            $this->log->debug('payment_paypal return APPROVED to COMPLETED error');
                            $payment->return_redirect($payment->fail_url);
                        }
                    }else if($payment->status>0){
                        $payment->return_redirect($payment->success_url);
                    }else{
                        $payment->return_redirect($payment->fail_url);
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

    public function notify()
    {
        $raw_post_data = file_get_contents('php://input');
        if($raw_post_data){
            $input = json_decode($raw_post_data,true);
            if(!$input){
                throw new ApiException(['code'=>-2,'msg'=>'fail']);
            }
            $this->log->debug('payment_paypal notify start',$input);
            $transaction_id = $input['resource']['supplementary_data']['related_ids']['order_id'];
            $invoice_id = $input['resource']['invoice_id'];
            $payment = Payment::where(['transaction_id'=>$transaction_id,'id'=>$invoice_id])->first();
            if(!empty($payment)){
                if($payment->status>0){
                    throw new ApiException('');
                }
                $orderShow = $this->order->show($transaction_id);
                if($orderShow['status']=='COMPLETED'){
                    $payment->status=1;
                    $payment->notify_type='notify';
                    $payment->cred_id=$input['resource']['id'];
                    if($payment->save() && $payment->notify_func){
                        $this->callBack($payment->notify_func,$payment);
                    }
                    throw new ApiException('');
                }else{
                    throw new ApiException(['code'=>3,'msg'=>'fail']);
                }
            }else{
                throw new ApiException(['code'=>2,'msg'=>'fail']);
            }
        }else{
            throw new ApiException(['code'=>-1,'msg'=>'fail']);
        }
    }

    public function show($payment)
    {
        return $this->order->show($payment->transaction_id);
    }

    public function refund($payment,$refund){
        $refund_res = $this->order->refund($payment->cred_id,$refund->amount, $payment->currency_code, $refund->reason??'');
        $this->log->debug('payment_paypal refund res',$refund_res);
        if(isset($refund_res['id'])){
            $refund->cred_id = $refund_res['id'];
            $refund->cred_status = $refund_res['status'];
            if($refund_res['status']=='COMPLETED'){
                $refund->status = 1;
            }
            $refund->save();
        }else{
            throw new ApiException(['code'=>3,'msg'=>'refund res error']);
        }
    }

}
