<?php

namespace Aphly\LaravelPayment\Services\Paypal;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Client
{
    const VERSION = '/v2/';
    const SANDBOX_URL = 'https://api-m.sandbox.paypal.com';

    const LIVE_URL = 'https://api-m.paypal.com';

    public $environment = '';
    public $client_id = 'AeUNXihK0N-R7lFPTp8hQ3e-v2lpnfYQfct2jRPb-25P6B2-NNS-xhbFDkFkfbJbDUJqfM7WoB5syu5-';
    public $secret = 'EMP-lHKO5g1R-2nxmzhmc5sw_cDhyoCPgjIC45nKY1P-viR9hRzN37DpKallBOCTfakKI8jwffBIZVIW';

    public function generateBaseUrl($v=true): string {
        return ($this->environment === 'LIVE' ? self::LIVE_URL : self::SANDBOX_URL) . ($v?self::VERSION:'');
    }

    public function token(){
        return Cache::remember('paypal_token',7200, function () {
            $res = Http::withBasicAuth($this->client_id,$this->secret)->asForm()->baseUrl($this->generateBaseUrl(false))->post('v1/oauth2/token',[
                'grant_type'=>'client_credentials'
            ])->json();
            return $res['access_token'];
        });
    }

    public function make(){
        return Http::withToken($this->token())
            ->asJson()
            //->baseUrl($this->generateBaseUrl());
            ->baseUrl('http://test2.com/v2');
    }

}
