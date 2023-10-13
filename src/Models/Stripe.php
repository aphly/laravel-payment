<?php

namespace Aphly\LaravelPayment\Models;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\Laravel\Libs\Math;
use Illuminate\Support\Facades\DB;
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
                            'unit_amount' => Math::mul($payment->amount, 100,0),
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
                if($payment->save()){
                    session(['payment_token' => $payment->id.','.$checkoutSession->id]);
                    if($redirect){
                        redirect($pay_url)->send();
                    }else{
                        throw new ApiException(['code'=>0,'msg'=>'success','data'=>['redirect'=>$pay_url]]);
                    }
                }else{
                    throw new ApiException(['code'=>3,'msg'=>'payment save error']);
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
                $this->log->debug('payment_stripe notify callBack '.$classfunc);
                (new $class)->{$func}($payment);
            }
        }
    }

    public function sync($payment)
    {
        $this->log->debug('payment_stripe sync start');
        if(!empty($payment)){
            if($payment->status==0){
                $stripe = new StripeClient($this->sk);
                $sessions = $stripe->checkout->sessions->retrieve($payment->transaction_id,[]);
                $this->log->debug('payment_stripe sync show');
                $this->log->debug($sessions);
                if(isset($sessions) && $sessions->status=='complete'){
                    $payment->status=1;
                    $payment->notify_type='sync';
                    $payment->cred_id=$sessions->payment_intent;
                    if($payment->save() && $payment->notify_func){
                        $this->log->debug('payment_stripe sync ok');
                        $this->callBack($payment->notify_func,$payment);
                    }
                    throw new ApiException(['code'=>0,'msg'=>'success']);
                }else{
                    $this->log->debug('payment_stripe sync complete error');
                    throw new ApiException(['code'=>1,'msg'=>'payment_stripe sync complete error']);
                }
            }else if($payment->status>0){
                $this->log->debug('payment_stripe sync status > 0');
                throw new ApiException(['code'=>2,'msg'=>'payment_stripe sync status > 0']);
            }else{
                throw new ApiException(['code'=>3,'msg'=>'payment_stripe sync fail']);
            }
        }else{
            throw new ApiException(['code'=>4,'msg'=>'fail']);
        }
    }

    public function return($method_id)
    {
        $payment_token = session('payment_token');
        if($payment_token){
            list($payment_id,$transaction_id) = explode(',',$payment_token);
            $this->log->debug('payment_stripe return beginTransaction start',request()->all());
            DB::beginTransaction();
            try{
                $payment = Payment::where(['id'=>$payment_id])->lockForUpdate()->first();
                if(!empty($payment) && $transaction_id==$payment->transaction_id){
                    if($payment->status==0){
                        $stripe = new StripeClient($this->sk);
                        $sessions = $stripe->checkout->sessions->retrieve($transaction_id,[]);
                        $this->log->debug('payment_stripe return show');
                        $this->log->debug($sessions);
                        if(isset($sessions) && $sessions->status=='complete'){
                            $payment->status=1;
                            $payment->notify_type='return';
                            $payment->cred_id=$sessions->payment_intent;
                            if($payment->save() && $payment->notify_func){
                                $this->log->debug('payment_stripe return ok');
                                $this->callBack($payment->notify_func,$payment);
                            }
                            throw new ApiException(['code'=>99,'msg'=>'success','data'=>['payment'=>$payment]]);
                        }else{
                            $this->log->debug('payment_stripe return complete error');
                            throw new ApiException(['code'=>98,'msg'=>'fail','data'=>['payment'=>$payment]]);
                        }
                    }else if($payment->status>0){
                        $this->log->debug('payment_stripe return status > 0');
                        throw new ApiException(['code'=>99,'msg'=>'success','data'=>['payment'=>$payment]]);
                    }else{
                        throw new ApiException(['code'=>98,'msg'=>'fail','data'=>['payment'=>$payment]]);
                    }
                }else{
                    $this->log->debug('payment_stripe return fail '.$transaction_id.'  '.$payment->transaction_id);
                    throw new ApiException(['code'=>1,'msg'=>'fail']);
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
            throw new ApiException(['code'=>-2,'msg'=>'payment_token error']);
        }
    }

    function notify(){
        \Stripe\Stripe::setApiKey($this->sk);
        $this->log->debug('payment_stripe notify start');
        $payload = @file_get_contents('php://input');
        //$this->log->debug($payload);
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $this->log->debug($sig_header);
        $endpoint_secret = $this->es;
        $this->log->debug($endpoint_secret);
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
                $this->log->debug('payment_stripe notify beginTransaction start ');
                if($session->payment_status == 'paid') {
                    $payment_id = $session->client_reference_id;
                    DB::beginTransaction();
                    try{
                        $payment = Payment::where(['id'=>$payment_id])->lockForUpdate()->first();
                        if(!empty($payment)){
                            if($payment->status>0){
                                $this->log->debug('payment_stripe notify completed status>0');
                            }else if($payment->status==0 && $payment->transaction_id==$session->id){
                                $this->log->debug('payment_stripe notify completed status==1');
                                $payment->status=1;
                                $payment->notify_type='notify';
                                $payment->cred_id=$session->payment_intent;
                                if($payment->save() && $payment->notify_func){
                                    $this->log->debug('payment_stripe notify ok');
                                    $this->callBack($payment->notify_func,$payment);
                                }
                            }else{
                                $this->log->debug('payment_stripe notify completed status');
                            }
                        }
                    }catch (ApiException $e){
                        DB::rollBack();
                        throw $e;
                    }
                    DB::commit();
                }
                break;
            case 'checkout.session.async_payment_succeeded':
                $this->log->debug('payment_stripe notify async_payment_succeeded ');
                break;
            case 'checkout.session.async_payment_failed':
                $this->log->debug('payment_stripe notify async_payment_failed ');
                break;
            default:
                $this->log->debug('Received unknown event type ' . $event->type);
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
        $amount = Math::mul($refund->amount, 100,0);
        $refund_res = $stripe->refunds->create(['payment_intent' => $payment->cred_id, 'amount' => $amount]);
        $this->log->debug('payment_stripe refund res');
        if(isset($refund_res->id)){
            $refund->cred_id = $refund_res->id;
            $refund->cred_status = $refund_res->status;
            if($refund_res->status=='succeeded'){
                $refund->status = 1;
            }
            $refund->save();
        }else{
            throw new ApiException(['code'=>3,'msg'=>'refund res error']);
        }
    }


}
