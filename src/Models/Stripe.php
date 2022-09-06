<?php

namespace Aphly\LaravelPayment\Models;

use Aphly\Laravel\Exceptions\ApiException;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Webhook;

class Stripe
{

    public $log;

    public $environment = '';
    public $pk = '';
    public $sk = '';

    function __construct(){
        $this->log = Log::channel('payment');
        $method = Method::where('name','stripe')->with('params')->first();
        if(!empty($method)){
            foreach ($method->params as $val){
                $key = $val->key;
                $this->$key = $val->val;
            }
        }
    }

    public function pay($payment,$redirect=true)
    {
        $this->log->debug('payment_stripe pay start');
        if($payment->id){
            $stripe = new StripeClient($this->sk);
            $checkoutSession = $stripe->checkout->sessions->create([
                'success_url' =>  $payment->success_url,
                'cancel_url' => $payment->cancel_url,
                'payment_method_types' => ['card'],
                'locale'=>'auto',
                'line_items' => [
                    [
                        'currency' => 'usd',
                        'amount' => $payment->amount*100,
                        'name' => 'Payment',
                        'quantity' => 1,
                        'description'=>"Payment",
                    ],
                ],
                'mode' => 'payment',
            ]);
            if($checkoutSession->payment_intent){
                $this->log->debug('payment_stripe pay create'.$checkoutSession->payment_intent);
                $pay_url = $checkoutSession->url;
                $payment->transaction_id = $checkoutSession->payment_intent;
                $payment->save();
                if($redirect){
                    redirect($pay_url)->cookie('payment_token', encrypt($payment->id.','.$checkoutSession->payment_intent), 60)->send();
                }else{
                    throw new ApiException(['code'=>0,'msg'=>'success','data'=>['redirect'=>$pay_url]],cookie('payment_token',$payment->id.','.$checkoutSession->payment_intent, 60));
                }
            }else{
                throw new ApiException(['code'=>2,'msg'=>'payment create error']);
            }
        }else{
            throw new ApiException(['code'=>1,'msg'=>'payment id error']);
        }
    }

    public function callBack($classfunc,$payment,$notify=false)
    {
        if($notify){
            if($classfunc && $payment){
                list($class,$func) = explode('@',$classfunc);
                if (class_exists($class) && method_exists($class,$func)){
                    $this->log->debug('payment_stripe notify callBack '.$classfunc);
                    (new $class)->{$func}($payment);
                }
            }
        }else{
            redirect($classfunc.'?payment_id='.$payment->id)->send();
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
            list($payment_id,$order_id) = explode(',',$payment_token);
            if($order_id == $request->query('token')){
                $this->log->debug('payment_stripe return start',$request->all());
                $payment = Payment::where(['id'=>$payment_id,'method_id'=>$method_id])->first();
                if(!empty($payment)){
                    if($payment->status==1){
                        $capture = $this->order->capture($order_id);
                        $this->log->debug('payment_stripe return APPROVED to COMPLETED',$capture);
                        if(isset($capture['status']) && $capture['status']=='COMPLETED'){
                            $payment->status=2;
                            if($payment->save() && $payment->notify_func){
                                $this->callBack($payment->notify_func,$payment,true);
                            }
                            $this->callBack($payment->success_url,$payment);
                        }else{
                            $this->log->debug('payment_stripe return APPROVED to COMPLETED error');
                            $this->callBack($payment->fail_url,$payment);
                        }
                    }else if($payment->status>1){
                        $this->callBack($payment->success_url,$payment);
                    }else{
                        $this->callBack($payment->fail_url,$payment);
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

    public function notify1($method_id)
    {
        $raw_post_data = file_get_contents('php://input');
        if($raw_post_data){
            $input = json_decode($raw_post_data,true);
            if(!$input){
                throw new ApiException(['code'=>-2,'msg'=>'fail']);
            }
            $this->log->debug('payment_stripe notify start',$input);
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
                        $this->callBack($payment->notify_func,$payment,true);
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

    function notify(){
        \Stripe\Stripe::setApiKey($this->sk);
        $this->log->debug('payment_stripe notify start');
        $endpoint_secret = env('NOTIFY_SIGN');
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;
        try {
            $event = Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            $this->log->debug('payment_stripe notify error '.$e->getMessage());
            throw new ApiException(['code'=>1,'msg'=>'fail']);
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            $this->log->debug('payment_stripe notify Invalid signature '.$e->getMessage());
            throw new ApiException(['code'=>2,'msg'=>'fail']);
        }
        $this->log->debug('payment_stripe notify event ',get_object_vars ($event));
        $session = $event->data->object;
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->log->debug('payment_stripe notify completed ',get_object_vars ($session));
                create_order($session);
                if ($session->payment_status == 'paid') {
                    // Fulfill the purchase
                    fulfill_order($session);
                }
                break;
            case 'checkout.session.async_payment_succeeded':
                $this->log->debug('payment_stripe notify async_payment_succeeded ',get_object_vars ($session));
                break;
            case 'checkout.session.async_payment_failed':
                $this->log->debug('payment_stripe notify async_payment_failed ',get_object_vars ($session));
                break;
        }

        $payment = Payment::where(['transaction_id'=>$order_id,'id'=>$invoice_id])->first();

    }

    function handle($payment){
        if(!empty($payment)){
            if($payment->status!=1){
                throw new ApiException('');
            }
            $info = $this->order->show($order_id);
            if($info['status']=='COMPLETED'){
                $payment->status=2;
                if($payment->save() && $payment->notify_func){
                    $this->callBack($payment->notify_func,$payment,true);
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
