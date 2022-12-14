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
    public $es = '';

    function __construct(){
        $this->log = Log::channel('payment');
        $method = PaymentMethod::where('name','stripe')->with('params')->first();
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
                'success_url' =>  url('/payment/stripe/return'),
                'cancel_url' => $payment->cancel_url,
                'payment_method_types' => ['card'],
                'mode' => 'payment',
                //'customer_email'=>'121099327@qq.com',
                'client_reference_id'=>$payment->id,
                'line_items' => [
                    [
                        'quantity' => 1,
                        'price_data' => [
                            'currency' => 'usd',
                            'unit_amount' => $payment->amount*100,
                            'product_data' => [
                                'name' => 'Payment_name',
                                'description' => 'Payment_description',
                            ],
                        ],
                    ],
                ],
            ]);
            if($checkoutSession->id){
                $this->log->debug('payment_stripe pay create '.$checkoutSession->id);
                $pay_url = $checkoutSession->url;
                $payment->transaction_id = $checkoutSession->id;
                $payment->save();
                if($redirect){
                    redirect($pay_url)->cookie('payment_token', encrypt($payment->id.','.$checkoutSession->id), 60)->send();
                }else{
                    throw new ApiException(['code'=>0,'msg'=>'success','data'=>['redirect'=>$pay_url]],cookie('payment_token',$payment->id.','.$checkoutSession->id, 60));
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
        $payment_token = Cookie::get('payment_token');
        if(!$payment_token){
            $payment_token = decrypt($_COOKIE["payment_token"]);
        }
        if($payment_token){
            list($payment_id,$transaction_id) = explode(',',$payment_token);
            $this->log->debug('payment_stripe return start',request()->all());
            $payment = Payment::where(['id'=>$payment_id,'method_id'=>$method_id])->first();
            if(!empty($payment) && $transaction_id==$payment->transaction_id){
                if($payment->status==1){
                    $stripe = new StripeClient($this->sk);
                    $sessions = $stripe->checkout->sessions->retrieve($transaction_id,[]);
                    $this->log->debug('payment_stripe return show');
                    $this->log->debug($sessions);
                    if(isset($sessions) && $sessions->status=='complete'){
                        $payment->status=2;
                        $payment->notify_type='return';
                        $payment->cred_id=$sessions->payment_intent;
                        if($payment->save() && $payment->notify_func){
                            $this->callBack($payment->notify_func,$payment,true);
                        }
                        $this->callBack($payment->success_url,$payment);
                    }else{
                        $this->log->debug('payment_stripe return complete error');
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
            throw new ApiException(['code'=>-2,'msg'=>'payment_token error']);
        }
    }

    function notify(){
        \Stripe\Stripe::setApiKey($this->sk);
        $this->log->debug('payment_stripe notify start');
        $payload = @file_get_contents('php://input');
        $this->log->debug($payload);
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $this->log->debug($sig_header);
        $endpoint_secret = $this->es;
        $this->log->debug($endpoint_secret);
        $event = null;
        try {
            $event = Webhook::constructEvent(
                $payload, $sig_header,$endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            $this->log->debug('payment_stripe notify error '.$e->getMessage());
            throw new ApiException(['code'=>1,'msg'=>'fail']);
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            $this->log->debug('payment_stripe notify Invalid signature '.$e->getMessage());
            throw new ApiException(['code'=>2,'msg'=>'fail']);
        }
        $this->log->debug('payment_stripe notify event ');
        $this->log->debug($event);
        $session = $event->data->object;
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->log->debug('payment_stripe notify completed ');
                if($session->payment_status == 'paid') {
                    $payment_id = $session->client_reference_id;
                    $payment = Payment::where(['id'=>$payment_id])->first();
                    if(!empty($payment)){
                        if($payment->status>1){
                            $this->log->debug('payment_stripe notify completed status>1');
                        }else if($payment->status==1 && $payment->transaction_id==$session->id){
                            $this->log->debug('payment_stripe notify completed status==1');
                            $payment->status=2;
                            $payment->notify_type='notify';
                            $payment->cred_id=$session->payment_intent;
                            if($payment->save() && $payment->notify_func){
                                $this->callBack($payment->notify_func,$payment,true);
                            }
                        }else{
                            $this->log->debug('payment_stripe notify completed status');
                        }
                    }
                }
                break;
            case 'checkout.session.async_payment_succeeded':
                $this->log->debug('payment_stripe notify async_payment_succeeded ');
                break;
            case 'checkout.session.async_payment_failed':
                $this->log->debug('payment_stripe notify async_payment_failed ');
                break;
        }
        throw new ApiException('');
    }

    public function show($payment)
    {
        $stripe = new StripeClient($this->sk);
        return $stripe->checkout->sessions->retrieve($payment->transaction_id,[]);
    }

    public function refund($payment,$refund){
        $stripe = new StripeClient($this->sk);
        $refund_res = $stripe->refunds->create(['payment_intent' => $payment->cred_id, 'amount' => $refund->amount]);
        $this->log->debug('payment_paypal refund res');
        if(isset($refund_res->id)){
            $refund->cred_id = $refund_res->id;
            $refund->cred_status = $refund_res->status;
            if($refund_res->status=='succeeded'){
                $refund->status = 2;
            }
            $refund->save();
        }else{
            throw new ApiException(['code'=>3,'msg'=>'refund res error']);
        }
    }
}
