<?php

namespace Aphly\LaravelPayment\Services\Paypal;

use Aphly\Laravel\Exceptions\ApiException;
use Aphly\LaravelPayment\Models\PaymentMethod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Client
{
    const VERSION = '/v2/';
    const SANDBOX_URL = 'https://api-m.sandbox.paypal.com';

    const LIVE_URL = 'https://api-m.paypal.com';

    public $environment = '';
    public $client_id = '';
    public $secret = '';
    public $webhookId = '';

    public $output = '';
    public $code = 0;

    public $log;

    function __construct()
    {
        $method = PaymentMethod::where('name','paypal')->with('params')->first();
        if(!empty($method)){
            foreach ($method->params as $val){
                $key = $val->key;
                $this->$key = $val->val;
            }
        }
        $this->log = Log::channel('payment');
    }

    public function generateBaseUrl($v=true): string {
        return ($this->environment == 'LIVE' ? self::LIVE_URL : self::SANDBOX_URL) . ($v?self::VERSION:'');
    }

    public function token(){
        if($this->client_id && $this->secret){
            return Cache::remember('paypal_token',7200, function () {
                $res = Http::connectTimeout(20)->withBasicAuth($this->client_id,$this->secret)
                    ->asForm()->baseUrl($this->generateBaseUrl(false))->post('v1/oauth2/token',[
                    'grant_type'=>'client_credentials'
                ])->json();
                Log::channel('payment')->debug('paypal client_id:'.$this->client_id);
                Log::channel('payment')->debug('paypal secret:'.$this->secret);
                Log::channel('payment')->debug('paypal token_res:',$res);
                if(empty($res['access_token'])){
                    throw new ApiException(['code'=>1,'msg'=>'paypal token_res fail']);
                }
                return $res['access_token'];
            });
        }else{
            throw new ApiException(['code'=>2,'msg'=>'Paypal client_id and secret error']);
        }
    }

    public function http($url,$method='get',$data='',$headers=[]){
        return $this->http_full($this->generateBaseUrl().$url,$method,$data,$headers);
    }

    public function http_full($url,$method='get',$data='',$headers=[]){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
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
        $this->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $this;
    }

    public function json(){
        return json_decode($this->output,true);
    }

    public function body(){
        return $this->output;
    }

    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (strpos($k, 'HTTP_') === 0) {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k, 5)))));
                $headers[$key] = $v;
            }
        }
        return $headers;
    }

    public function verifySignature(){
        $rawBody = file_get_contents('php://input');
        $headers = $this->getallheaders();
        $this->log->debug('payment_paypal verifySignature start webhookId:'.$this->webhookId);
        $this->log->debug($rawBody);
        $this->log->debug(json_encode($headers));
        $verifyBody = [
            'auth_algo'         => $headers['Paypal-Auth-Algo']??'',
            'cert_url'           => $headers['Paypal-Cert-Url']??'',
            'transmission_id'    => $headers['Paypal-Transmission-Id']??'',
            'transmission_sig'   => $headers['Paypal-Transmission-Sig']??'',
            'transmission_time'  => $headers['Paypal-Transmission-Time']??'',
            'webhook_id'         => $this->webhookId,
            'webhook_event'      => json_decode($rawBody, true)
        ];
        $this->log->debug(json_encode($verifyBody));
        $this->http_full($this->generateBaseUrl(false).'/v1/notifications/verify-webhook-signature',
            'post',$verifyBody);
        $this->log->debug($this->output);
        $json = $this->json();
        if($this->code === 200 && $json['verification_status'] === 'SUCCESS'){
            return $rawBody;
        }else{
            return false;
        }
    }

}
