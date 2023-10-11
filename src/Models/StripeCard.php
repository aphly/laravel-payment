<?php

namespace Aphly\LaravelPayment\Models;

use Aphly\Laravel\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeCard
{

    public $log;

    public $environment = '';
    public $pk = '';
    public $sk = '';
    public $es = '';

    function __construct(){
        $this->log = Log::channel('payment');
        $method = PaymentMethod::where('name','stripeCard')->with('params')->first();
        if(!empty($method)){
            foreach ($method->params as $val){
                $key = $val->key;
                $this->$key = $val->val;
            }
        }
    }

    public function sync($payment)
    {
        $this->log->debug('payment_stripeCard sync start');
        if(!empty($payment)){
            if($payment->status==0){
                $stripe = new StripeClient($this->sk);
                $sessions = $stripe->paymentIntents->retrieve($payment->transaction_id,[]);
                $this->log->debug('payment_stripeCard sync show');
                $this->log->debug($sessions);
                if(isset($sessions) && $sessions->status=='succeeded'){
                    $payment->status=1;
                    $payment->notify_type='sync';
                    $payment->cred_id=$sessions->id;
                    if($payment->save() && $payment->notify_func){
                        $this->log->debug('payment_stripeCard sync ok');
                        $this->callBack($payment->notify_func,$payment);
                    }
                    throw new ApiException(['code'=>0,'msg'=>'success']);
                }else{
                    $this->log->debug('payment_stripeCard sync complete error');
                    throw new ApiException(['code'=>1,'msg'=>'payment_stripeCard sync complete error']);
                }
            }else if($payment->status>0){
                $this->log->debug('payment_stripeCard sync status > 0');
                throw new ApiException(['code'=>2,'msg'=>'payment_stripeCard sync status > 0']);
            }else{
                throw new ApiException(['code'=>3,'msg'=>'payment_stripeCard sync fail']);
            }
        }else{
            throw new ApiException(['code'=>4,'msg'=>'fail']);
        }
    }

    public function callBack($classfunc,$payment)
    {
        if($classfunc && $payment){
            list($class,$func) = explode('@',$classfunc);
            if (class_exists($class) && method_exists($class,$func)){
                $this->log->debug('payment_stripeCard notify callBack '.$classfunc);
                (new $class)->{$func}($payment);
            }
        }
    }

    function return(){
        $payment_intent = session('card_payment_intent','');
        $card_payment_id = session('card_payment_id');
        if(!$payment_intent || !$card_payment_id){
            $this->log->debug('payment_stripeCard return fail '.$payment_intent.' '.$card_payment_id);
            throw new ApiException(['code'=>1,'msg'=>'fail']);
        }
        $this->log->debug('payment_stripeCard return beginTransaction');
        DB::beginTransaction();
        try {
            $payment = Payment::where(['id'=>$card_payment_id])->lockForUpdate()->first();
            if(!empty($payment) && $payment_intent==$payment->transaction_id){
                if($payment->status==0){
                    $stripe = new StripeClient($this->sk);
                    $sessions = $stripe->paymentIntents->retrieve($payment_intent,[]);
                    $this->log->debug('payment_stripeCard return show');
                    $this->log->debug($sessions);
                    if(!empty($sessions)){
                        if($sessions->status=='succeeded'){
                            $payment->status=1;
                            $payment->notify_type='return';
                            $payment->cred_id=$sessions->id;
                            if($payment->save() && $payment->notify_func){
                                $this->log->debug('payment_stripeCard return ok');
                                $this->clear();
                                $this->callBack($payment->notify_func,$payment);
                            }
                            throw new ApiException(['code'=>99,'msg'=>'success','data'=>['payment'=>$payment]]);
                        }else if($sessions->status=='processing'){
                            $this->log->debug('payment_stripeCard return processing');
                            $this->clear();
                            throw new ApiException(['code'=>99,'msg'=>'success','data'=>['payment'=>$payment]]);
                        }else{
                            $this->log->debug('payment_stripeCard return complete error');
                            throw new ApiException(['code'=>98,'msg'=>'fail','data'=>['payment'=>$payment]]);
                        }
                    }else{
                        $this->log->debug('payment_stripeCard return complete error');
                        throw new ApiException(['code'=>98,'msg'=>'fail','data'=>['payment'=>$payment]]);
                    }
                }else if($payment->status>0){
                    $this->log->debug('payment_stripeCard return status > 0');
                    throw new ApiException(['code'=>99,'msg'=>'success','data'=>['payment'=>$payment]]);
                }else{
                    throw new ApiException(['code'=>98,'msg'=>'fail','data'=>['payment'=>$payment]]);
                }
            }else{
                $this->log->debug('payment_stripeCard return fail ');
                throw new ApiException(['code'=>1,'msg'=>'fail']);
            }
        }catch (ApiException $e) {
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
    }

    function notify(){
        \Stripe\Stripe::setApiKey($this->sk);
        $this->log->debug('payment_stripeCard notify start');
        $payload = @file_get_contents('php://input');
        $this->log->debug($payload);
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $this->log->debug($sig_header);
        $endpoint_secret = $this->es;
        $this->log->debug($endpoint_secret);
        try {
            $event = Webhook::constructEvent(
                $payload, $sig_header,$endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            $this->log->debug('payment_stripeCard notify error '.$e->getMessage());
            throw new ApiException(['code'=>1,'msg'=>'fail']);
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            $this->log->debug('payment_stripeCard notify Invalid signature '.$e->getMessage());
            throw new ApiException(['code'=>2,'msg'=>'fail']);
        }
        $this->log->debug('payment_stripeCard notify event ');
        $this->log->debug($event);
        $session = $event->data->object;
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->log->debug('payment_stripeCard notify succeeded ');
                if($session->status == 'succeeded') {
                    $transaction_id = $session->id;
                    $payment = Payment::where(['transaction_id'=>$transaction_id,'method_id'=>3])->first();
                    $this->log->debug('payment_stripeCard notify beginTransaction');
                    DB::beginTransaction();
                    try{
                        $payment = Payment::where(['id'=>$payment->id])->lockForUpdate()->first();
                        if(!empty($payment) && $payment->transaction_id==$transaction_id){
                            if($payment->status>0){
                                $this->log->debug('payment_stripeCard notify completed status>0');
                            }else if($payment->status==0){
                                $this->log->debug('payment_stripeCard notify completed status==1');
                                $payment->status=1;
                                $payment->notify_type='notify';
                                $payment->cred_id=$session->id;
                                if($payment->save() && $payment->notify_func){
                                    $this->log->debug('payment_stripeCard notify ok');
                                    $this->callBack($payment->notify_func,$payment);
                                }
                            }else{
                                $this->log->debug('payment_stripeCard notify completed status');
                            }
                        }
                    }catch (ApiException $e){
                        DB::rollBack();
                        throw $e;
                    }
                    DB::commit();
                }else{
                    $this->log->debug('payment_stripeCard notify status:'.$session->status);
                }
                break;
            default:
                $this->log->debug('Received unknown event type ' . $event->type);
        }
        throw new ApiException('');
    }

    public function show($payment)
    {
        $stripe = new StripeClient($this->sk);
        return $stripe->paymentIntents->retrieve($payment->transaction_id,[]);
    }

    public function refund($payment,$refund){
        $stripe = new StripeClient($this->sk);
        $amount = intval($refund->amount*100);
        $refund_res = $stripe->refunds->create(['payment_intent' => $payment->cred_id, 'amount' => $amount]);
        $this->log->debug('payment_stripeCard refund res');
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

    public function create($amount,$currency){
        $clientSecret = session('clientSecret');
        $card_amount = session('card_amount');
        $card_currency = session('card_currency');
        if($clientSecret && $amount==$card_amount && $card_currency==$currency){
            throw new ApiException(['code'=>0,'msg'=>'success','data'=>['clientSecret' => $clientSecret]]);
        }else{
            if($amount && $currency){
                $stripe = new StripeClient($this->sk);
                $paymentIntent = $stripe->paymentIntents->create([
                    'amount' => intval($amount*100),
                    'currency' => $currency,
                    'automatic_payment_methods' => [
                        'enabled' => true,
                    ],
                ]);
                $clientSecret = $paymentIntent->client_secret;
                session(['clientSecret'=>$clientSecret,'card_amount'=>$amount,'card_currency'=>$currency,'card_payment_intent'=>$paymentIntent->id]);
                throw new ApiException(['code'=>0,'msg'=>'success','data'=>['clientSecret' => $clientSecret]]);
            }
            throw new ApiException(['code'=>1,'msg'=>'fail']);
        }
    }

    public function clear(){
        session()->forget(['clientSecret','card_amount','card_currency','card_payment_id']);
    }
}
