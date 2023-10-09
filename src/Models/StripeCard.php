<?php

namespace Aphly\LaravelPayment\Models;

use Aphly\Laravel\Exceptions\ApiException;
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
        $method = PaymentMethod::where('name','stripe')->with('params')->first();
        if(!empty($method)){
            foreach ($method->params as $val){
                $key = $val->key;
                $this->$key = $val->val;
            }
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
        $amount = intval($refund->amount*100);
        $refund_res = $stripe->refunds->create(['payment_intent' => $payment->cred_id, 'amount' => $amount]);
        $this->log->debug('payment_paypal refund res');
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
                session(['clientSecret'=>$clientSecret,'card_amount'=>$amount,'card_currency'=>$currency]);
                throw new ApiException(['code'=>0,'msg'=>'success','data'=>['clientSecret' => $clientSecret]]);
            }
            throw new ApiException(['code'=>1,'msg'=>'fail']);
        }
    }

    public function clear(){
        session()->forget(['clientSecret','card_amount','card_currency']);
    }
}
