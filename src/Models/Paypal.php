<?php

namespace Aphly\LaravelPayment\Models;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\LaravelPayment\Services\Paypal\Order;
use Illuminate\Support\Facades\DB;
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
                if($payment->save()){
                    session(['payment_token' => $payment->id.','.$res_arr['id']]);
                    if($redirect){
                        redirect($pay_url)->send();
                    }else{
                        throw new ApiException(['code'=>0,'msg'=>'success','data'=>['redirect'=>$pay_url]]);
                    }
                }else{
                    throw new ApiException(['code'=>3,'msg'=>'payment save error']);
                }
            }else{
                $this->log->debug('payment create error',is_array($res_arr)?$res_arr:[]);
                throw new ApiException(['code'=>2,'msg'=>'payment create error','data'=>$res_arr]);
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

    public function sync($payment)
    {
        $this->log->debug('payment_paypal sync start');
        if(!empty($payment)){
            if($payment->status==0){
                $this->handle($payment,'sync');
            }else if($payment->status>0){
                $this->log->debug('payment_paypal sync status > 0');
                throw new ApiException(['code'=>2,'msg'=>'payment_paypal sync status > 0']);
            }else{
                throw new ApiException(['code'=>3,'msg'=>'payment_paypal sync fail']);
            }
        }else{
            throw new ApiException(['code'=>4,'msg'=>'fail']);
        }
    }

    public function handle($payment,$notify_type){
        $order_show = $this->order->show($payment->transaction_id);
        if(isset($order_show['status']) && $order_show['status']=='COMPLETED') {
            $payment->status = 1;
            $payment->notify_type = $notify_type;
            $payment->cred_id = !empty($order_show['purchase_units'][0]['payments']['captures']['0']['id']) ?? '';
            if ($payment->save() && $payment->notify_func) {
                $this->log->debug('payment_paypal '.$notify_type.' ok');
                $this->callBack($payment->notify_func, $payment);
            }
            if($notify_type=='return'){
                $payment->return_redirect($payment->success_url);
            }else{
                throw new ApiException(['code' => 0, 'msg' => 'success']);
            }
        }else if(isset($order_show['status']) && $order_show['status']=='APPROVED'){
            $capture = $this->order->capture($payment->transaction_id);
            if(isset($capture['status']) && $capture['status']=='COMPLETED'){
                $payment->status=1;
                $payment->notify_type=$notify_type;
                $payment->cred_id=$capture['purchase_units'][0]['payments']['captures']['0']['id'];
                if($payment->save() && $payment->notify_func){
                    $this->log->debug('payment_paypal '.$notify_type.' APPROVED ok');
                    $this->callBack($payment->notify_func,$payment);
                }
                if($notify_type=='return'){
                    $payment->return_redirect($payment->success_url);
                }else{
                    throw new ApiException(['code' => 0, 'msg' => 'success']);
                }
            }else{
                $msg = 'payment_paypal '.$notify_type.' APPROVED to COMPLETED error';
                $this->log->debug('payment_paypal '.$notify_type.' APPROVED to COMPLETED error',is_array($capture)?$capture:[]);
                if($notify_type=='return'){
                    $payment->return_redirect($payment->fail_url);
                }else{
                    throw new ApiException(['code' => 1, 'msg' => $msg]);
                }
            }
        }else{
            $msg = 'payment_paypal '.$notify_type.' show error';
            $this->log->debug($msg,$order_show);
            if($notify_type=='return'){
                $payment->return_redirect($payment->fail_url);
            }else{
                throw new ApiException(['code' => 1, 'msg' => $msg]);
            }
        }
    }

    public function return()
    {
        //payment/return?token=7U168763NS774425V&PayerID=5JBR62CD2ZXS4
        $request = request();
        $payment_token = session('payment_token');
        if($payment_token){
            list($payment_id,$transaction_id) = explode(',',$payment_token);
            if($transaction_id == $request->query('token')){
                $this->log->debug('payment_paypal return start',$request->all());
                DB::beginTransaction();
                try {
                    $payment = Payment::where(['id' => $payment_id])->lockForUpdate()->first();
                    if (!empty($payment)) {
                        if ($payment->status == 0) {
                            $notify_type = 'return';
                            $capture = $this->order->capture($payment->transaction_id);
                            if (isset($capture['status']) && $capture['status'] == 'COMPLETED') {
                                $payment->status = 1;
                                $payment->notify_type = $notify_type;
                                $payment->cred_id = $capture['purchase_units'][0]['payments']['captures']['0']['id'];
                                if ($payment->save() && $payment->notify_func) {
                                    $this->log->debug('payment_paypal ' . $notify_type . ' APPROVED ok');
                                    $this->callBack($payment->notify_func, $payment);
                                }
                                throw new ApiException(['code'=>99,'msg'=>'success','data'=>['payment'=>$payment]]);
                            } else {
                                $msg = 'payment_paypal ' . $notify_type . ' APPROVED to COMPLETED error';
                                $this->log->debug($msg);
                                throw new ApiException(['code'=>98,'msg'=>'fail','data'=>['payment'=>$payment]]);
                            }
                        } else if ($payment->status > 0) {
                            $this->log->debug('payment_paypal return status > 0');
                            throw new ApiException(['code'=>99,'msg'=>'success','data'=>['payment'=>$payment]]);
                        } else {
                            throw new ApiException(['code'=>98,'msg'=>'fail','data'=>['payment'=>$payment]]);
                        }
                    } else {
                        throw new ApiException(['code' => 1, 'msg' => 'fail']);
                    }
                }catch (ApiException $e){
                    if($e->code==99) {
                        DB::commit();
                        $e->data['payment']->return_redirect($e->data['payment']->success_url);
                    }else if($e->code==98){
                        DB::rollBack();
                        $e->data['payment']->return_redirect($e->data['payment']->fail_url);
                    }else{
                        DB::rollBack();
                        throw $e;
                    }
                }
            }else{
                $this->log->debug('payment_paypal return token error '.$transaction_id.'  '.$request->query('token'));
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
            DB::beginTransaction();
            try{
                $payment = Payment::where(['id'=>$invoice_id])->lockForUpdate()->first();
                if(!empty($payment) && $payment->transaction_id==$transaction_id){
                    if($payment->status>0){
                        $this->log->debug('payment_paypal notify status > 0');
                        throw new ApiException('');
                    }
                    $orderShow = $this->order->show($transaction_id);
                    if($orderShow['status']=='COMPLETED') {
                        $payment->status = 1;
                        $payment->notify_type = 'notify';
                        $payment->cred_id=$orderShow['purchase_units'][0]['payments']['captures']['0']['id'];
                        if ($payment->save() && $payment->notify_func) {
                            $this->log->debug('payment_paypal notify COMPLETED ok');
                            $this->callBack($payment->notify_func, $payment);
                        }
                    }else{
                        throw new ApiException(['code'=>3,'msg'=>'fail']);
                    }
                }else{
                    throw new ApiException(['code'=>2,'msg'=>'fail']);
                }
            }catch (ApiException $e){
                DB::rollBack();
                throw $e;
            }
            DB::commit();
            throw new ApiException('');
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
