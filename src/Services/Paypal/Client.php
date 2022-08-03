<?php

namespace Aphly\LaravelPayment\Services\Paypal;

use Illuminate\Support\Facades\Http;

class Client
{
    const VERSION = '/v2/';
    const SANDBOX_URL = 'https://api-m.sandbox.paypal.com';
    const LIVE_URL = 'https://api-m.paypal.com';

    public $environment = '';
    public $client_id = 'AeUNXihK0N-R7lFPTp8hQ3e-v2lpnfYQfct2jRPb-25P6B2-NNS-xhbFDkFkfbJbDUJqfM7WoB5syu5-';
    public $secret = 'EMP-lHKO5g1R-2nxmzhmc5sw_cDhyoCPgjIC45nKY1P-viR9hRzN37DpKallBOCTfakKI8jwffBIZVIW';

    public function generateBaseUrl(): string {
        return ($this->environment === 'LIVE' ? self::LIVE_URL : self::SANDBOX_URL) . self::VERSION;
    }

    public function token(){
       return Http::withBasicAuth($this->client_id,$this->secret)->withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded'
        ])->baseUrl($this->generateBaseUrl())->post('v1/oauth2/token',[
            'grant_type'=>'client_credentials'
        ]);
    }

    public function make(){
        return Http::withBasicAuth($this->client_id,$this->secret)
            ->asJson()
            ->baseUrl($this->generateBaseUrl());
    }

}
