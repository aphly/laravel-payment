<?php

namespace Aphly\LaravelPayment\Services\Paypal;

use Illuminate\Support\Facades\Http;

class Client
{
    const VERSION = '/v2/';
    const SANDBOX_URL = 'https://api-m.sandbox.paypal.com';
    const LIVE_URL = 'https://api-m.paypal.com';

    public $environment = '';
    public $client_id = 'aasdad';
    public $secret = 'zxczxczxc';

    public function generateBaseUrl(): string {
        return ($this->environment === 'LIVE' ? self::LIVE_URL : self::SANDBOX_URL) . self::VERSION;
    }

    public function token(){
       return Http::withBasicAuth($this->client_id,$this->secret)->withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded'
        ])->post('v1/oauth2/token',[
            'grant_type'=>'client_credentials'
        ]);
    }

    public function make(){
        return Http::withBasicAuth($this->client_id,$this->secret)
            ->asJson()
            ->baseUrl($this->generateBaseUrl());
    }

}
