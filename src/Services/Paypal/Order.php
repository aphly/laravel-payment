<?php

namespace Aphly\LaravelPayment\Services\Paypal;

class Order
{
    public $client;

    function __construct(){
        $this->client = (new Client)->make();
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
        $response = $this->client->post('checkout/orders', array_filter([
            'intent' => $intent,
            'purchase_units' => $purchaseUnits,
            'application_context' => $applicationContext,
        ]));
        $res_arr = $response->json();
        return $this->getLinkByRel($res_arr['links'],'approve');
    }

    public function show(string $orderId) {
        $response = $this->client->get('checkout/orders/' . $orderId);
        return $response;
    }

    public function show1(string $captureId){
        $capture = $this->client->get('payments/captures/' . $captureId);
        return $capture;
    }

    public function refund(string $captureId, float $amount, string $currency = 'GBP', string $reason = '', string $invoiceId = ''){
        $capture = $this->client->post('payments/captures/' . $captureId . '/refund', array_filter([
            'amount' => [
                'value' => $amount,
                'currency_code' => $currency,
            ],
            'note_to_payer' => $reason,
            'invoice_id' => $invoiceId,
        ]));
        return $capture;
    }
}
