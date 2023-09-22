<?php

namespace Aphly\LaravelPayment\Services\Paypal;

use Aphly\LaravelPayment\Models\PaymentMethod;
use Aphly\LaravelPayment\Models\PaymentMethodParams;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Client
{
    const VERSION = '/v2/';
    const SANDBOX_URL = 'https://api-m.sandbox.paypal.com';

    const LIVE_URL = 'https://api-m.paypal.com';

    public $environment = '';
    public $client_id = '';
    public $secret = '';

    public $output = '';

    function __construct()
    {
        $method = PaymentMethod::where('name','paypal')->with('params')->first();
        if(!empty($method)){
            foreach ($method->params as $val){
                $key = $val->key;
                $this->$key = $val->val;
            }
        }
    }

    public function generateBaseUrl($v=true): string {
        return ($this->environment === 'LIVE' ? self::LIVE_URL : self::SANDBOX_URL) . ($v?self::VERSION:'');
    }

    public function token(){
        if($this->client_id && $this->secret){
            return Cache::remember('paypal_token',7200, function () {
                $res = Http::connectTimeout(20)->withBasicAuth($this->client_id,$this->secret)->asForm()->baseUrl($this->generateBaseUrl(false))->post('v1/oauth2/token',[
                    'grant_type'=>'client_credentials'
                ])->json();
                return $res['access_token'];
            });
        }
    }

    public function http($url,$method='get',$data='',$headers=[]){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->generateBaseUrl().$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if(!$headers){
            $headers[]="Content-Type:application/json";
            $headers[]="Authorization: Bearer ".$this->token();
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if($method=='post'){
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        if($data){
            curl_setopt($ch, CURLOPT_POSTFIELDS , json_encode($data));
        }
        $this->output = curl_exec($ch);
        curl_close($ch);
        return $this;
    }

    public function json(){
        return json_decode($this->output,true);
    }

    public function body(){
        return $this->output;
    }
}
