<?php

namespace Aphly\LaravelPayment\Services\Paypal;

class Order
{
    public $client;

    function __construct(){
        $this->client = new Client;
    }

    public function getLinkByRel($links,string $rel) {
        foreach ($links as $val){
            if($val['rel']==$rel){
                return $val['href'];
            }
        }
        return '';
    }

    public function create(
        array $purchaseUnits,
        string $intent = 'CAPTURE',
        array $applicationContext = []
    ){
        $response = $this->client->http('checkout/orders','post', array_filter([
            'intent' => $intent,
            'purchase_units' => $purchaseUnits,
            'application_context' => $applicationContext,
        ]));
        return $response->json();
    }

    public function show(string $orderId) {
        $response = $this->client->http('checkout/orders/' . $orderId);
        return $response->json();
    }

    public function capture(string $orderId){
        $response = $this->client->http('checkout/orders/' . $orderId.'/capture','post');
        return $response->json();
    }

    public function refund(string $captureId, float $amount, string $currency = 'GBP', string $reason = '', string $invoiceId = ''){
        $response = $this->client->http('payments/captures/' . $captureId . '/refund','post', array_filter([
            'amount' => [
                'value' => $amount,
                'currency_code' => $currency,
            ],
            'note_to_payer' => $reason,
            'invoice_id' => $invoiceId,
        ]));
        return $response->json();
    }
}
