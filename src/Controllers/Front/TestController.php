<?php

namespace Aphly\LaravelPayment\Controllers\Front;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\LaravelPayment\Models\Method;
use Aphly\LaravelPayment\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Webhook;

class TestController extends Controller
{
    public $log = '';
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
        parent::__construct();
    }

    function index(Request $request){
        if($request->isMethod('post')){
            $stripe = new StripeClient($this->sk);
            $checkoutSession = $stripe->checkout->sessions->create([
                'success_url' =>  'http://test2.com/checkout/success?id=1',
                'cancel_url' => 'http://test2.com/test/index',
                'payment_method_types' => ['card'],
                'mode' => 'payment',
                'customer_email'=>'121099327@qq.com',
                'client_reference_id'=>'id=1',
                'line_items' => [
                    [
                        'quantity' => 1,
                        'price_data' => [
                            'currency' => 'usd',
                            'unit_amount' => 400,
                            'product_data' => [
                                'name' => 'Payment',
                                'description' => 'Payment',
                            ],
                        ],
                    ],
                ],
            ]);
            $this->log->debug('payment_stripe start');
            $this->log->debug($checkoutSession);
            $pay_url = $checkoutSession->url;
            redirect($pay_url)->cookie('payment_token', encrypt('1,'.$checkoutSession->payment_intent), 60)->send();
        }else{
            $res['title'] = 'xxx';
            return $this->makeView('laravel-payment::front.test.form',['res'=>$res]);
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
        $this->log->debug('payment_stripe notify event ',json_decode($event,true));
        $session = $event->data->object;
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->log->debug('payment_stripe notify completed ',json_decode($session,true));
                break;
            case 'checkout.session.async_payment_succeeded':
                $this->log->debug('payment_stripe notify async_payment_succeeded ',json_decode($session,true));
                break;
            case 'checkout.session.async_payment_failed':
                $this->log->debug('payment_stripe notify async_payment_failed ',json_decode($session,true));
                break;
        }

        //$payment = Payment::where(['transaction_id'=>$order_id,'id'=>$invoice_id])->first();

        return '';
    }

    function show(){
        $stripe = new StripeClient($this->sk);
//        $payment_intent = $stripe->paymentIntents->retrieve(
//            'pi_3LfJsaB2u33uLmOK1Y8m6KvO',[]
//        );
//        dd($payment_intent);
        $sessions = $stripe->checkout->sessions->retrieve(
            'cs_test_a19NdETDGmQoQj9ozdZzEEvEqh1KXcOUs4iF3dbMrbiFXzJXWm7UlDfCp1',
            []
        );
        dd($sessions);
    }
}
