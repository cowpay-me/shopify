<?php

namespace App\Jobs;

use Log;
use App\Payments;
use App\Configurations;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Payments as Orders;
use hash;

class RefundsCreateJob implements ShouldQueue {

    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * Shop's myshopify domain
     *
     * @var string
     */
    public $shopDomain;

    /**
     * The webhook data
     *
     * @var object
     */
    public $data;

    /**
     * Create a new job instance.
     *
     * @param string $shopDomain The shop's myshopify domain
     * @param object $data    The webhook data (JSON decoded)
     *
     * @return void
     */
    public function __construct($shopDomain, $data) {
        $this->shopDomain = $shopDomain;
        $this->data = $data;
        Log::info('Refund job');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        $refund_data = $this->data;
        //$refund_data = '{"id":642928214154,"order_id":2480585212060,"created_at":"2020-04-13T06:34:41-04:00","note":"not delivered at correct address","user_id":45701496970,"processed_at":"2020-04-13T06:34:41-04:00","restock":true,"admin_graphql_api_id":"gid:\/\/shopify\/Refund\/642928214154","refund_line_items":[{"id":143853617290,"quantity":1,"line_item_id":4704161792138,"location_id":39833698442,"restock_type":"return","subtotal":5,"total_tax":0,"subtotal_set":{"shop_money":{"amount":"5.00","currency_code":"USD"},"presentment_money":{"amount":"5.00","currency_code":"USD"}},"total_tax_set":{"shop_money":{"amount":"0.00","currency_code":"USD"},"presentment_money":{"amount":"0.00","currency_code":"USD"}},"line_item":{"id":4704161792138,"variant_id":32198243221642,"title":"short t-shirt","quantity":1,"sku":"545120","variant_title":"","vendor":"neutronpay-dev2","fulfillment_service":"manual","product_id":4554511515786,"requires_shipping":true,"taxable":true,"gift_card":false,"name":"short t-shirt","variant_inventory_management":"shopify","properties":[],"product_exists":true,"fulfillable_quantity":0,"grams":0,"price":"5.00","total_discount":"0.00","fulfillment_status":"fulfilled","price_set":{"shop_money":{"amount":"5.00","currency_code":"USD"},"presentment_money":{"amount":"5.00","currency_code":"USD"}},"total_discount_set":{"shop_money":{"amount":"0.00","currency_code":"USD"},"presentment_money":{"amount":"0.00","currency_code":"USD"}},"discount_allocations":[],"admin_graphql_api_id":"gid:\/\/shopify\/LineItem\/4704161792138","tax_lines":[],"origin_location":{"id":1756511043722,"country_code":"US","province_code":"CA","name":"neutronpay-dev2","address1":"11822 Moen St","address2":"","city":"Anaheim","zip":"92804"}}}],"transactions":[{"id":2675289129098,"order_id":2144397361290,"kind":"refund","gateway":"FawryPay","status":"success","message":"Refunded 5.00 from manual gateway","created_at":"2020-04-13T06:34:40-04:00","test":false,"authorization":null,"location_id":null,"user_id":45701496970,"parent_id":2675286409354,"processed_at":"2020-04-13T06:34:40-04:00","device_id":null,"receipt":{},"error_code":null,"source_name":"1830279","amount":"5.00","currency":"USD","admin_graphql_api_id":"gid:\/\/shopify\/OrderTransaction\/2675289129098"}],"order_adjustments":[{"id":88912003210,"order_id":2144397361290,"refund_id":642928214154,"amount":"5.00","tax_amount":"0.00","kind":"refund_discrepancy","reason":"Refund discrepancy","amount_set":{"shop_money":{"amount":"5.00","currency_code":"USD"},"presentment_money":{"amount":"5.00","currency_code":"USD"}},"tax_amount_set":{"shop_money":{"amount":"0.00","currency_code":"USD"},"presentment_money":{"amount":"0.00","currency_code":"USD"}}},{"id":88912035978,"order_id":2144397361290,"refund_id":642928214154,"amount":"-5.00","tax_amount":"0.00","kind":"shipping_refund","reason":"Shipping refund","amount_set":{"shop_money":{"amount":"-5.00","currency_code":"USD"},"presentment_money":{"amount":"-5.00","currency_code":"USD"}},"tax_amount_set":{"shop_money":{"amount":"0.00","currency_code":"USD"},"presentment_money":{"amount":"0.00","currency_code":"USD"}}}]}';
        //$refund_data = json_decode($refund_data);
        $shop_domain = $this->shopDomain;
        Log::info("refund_data =" . json_encode($refund_data));
        $order_id = $refund_data->order_id;
        $transactions = $refund_data->transactions;
        $total_amount = 0;
        foreach ($transactions as $transaction) {
            $total_amount += $transaction->amount;
        }
        $amount = floatval($total_amount);
        $refund_amount = number_format($amount, 2);
        $shop = $this->findShop($shop_domain);
        $config = Configurations::where(['meta_key' => $shop_domain . '_fawrypay_detail'])->first();
        $meta_value = unserialize($config->meta_value);
        if (isset($meta_value['sandbox']) && $meta_value['sandbox'] == '1')
            $sandbox = TRUE;
        else
            $sandbox = FALSE;
        $fawry_ref_no = $this->CheckOrderStatus($order_id,$shop->id);
        Log::info("refund_data->note =" . $refund_data->note);
        $merchant_code = $sandbox ? $meta_value['sandbox_account_number'] : $meta_value['live_account_number'];
        $secure_hash_key = $sandbox ? $meta_value['sandbox_hash_key'] : $meta_value['live_hash_key'];
        $api_url = $sandbox ? env('REFUND_SANDBOX_URL') : env('REFUND_PRODUCTION_URL');
        if (!empty($refund_data->note)) {
            $signature = hash('sha256', $merchant_code . $fawry_ref_no . $refund_amount . $refund_data->note . $secure_hash_key);
        } else {
            $signature = hash('sha256', $merchant_code . $fawry_ref_no . $refund_amount . $secure_hash_key);
        }
        Log::info("Secure hash key =" . $secure_hash_key);
        Log::info("Refund API Url =" . $api_url);
         $postData = [
            "merchantCode" => $merchant_code,
            "referenceNumber" => $fawry_ref_no,
            "refundAmount" => $refund_amount,
            "reason" => $refund_data->note,
            "signature" => $signature
        ];
       // $post_data = "merchantCode=$merchant_code&referenceNumber=$fawry_ref_no&refundAmount=$refund_amount&reason=$refund_data->note&signature=$signature";
        Log::info("refund_data post=" . json_encode($postData));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $server_output = curl_exec($ch);
        $decoded = json_decode($server_output); 
        Log::info("Refund api response =" . json_encode($decoded));
        if ($decoded->statusCode == 9903) {
            return response()->json(['success' => false], 200);
        }
    }

    private function findShop($shop) {
        $config = User::where(['name' => $shop])->first();
        if ($config !== null) {
            return $config;
        }
    }

    private function CheckOrderStatus($order_id,$store_id) {
        $payment = Orders::where(['order_id' => $order_id])->where(['store_id' => $store_id])->first();
        if ($payment == null) {
            Log::info("Order for refund not found in our database");
            return response()->json(['success' => false], 200);
        }
        if (isset($payment->payment_status)) {
            if ($payment->payment_status == 'PAID') {
                $fawry_ref_no = $payment->txid;
                return $fawry_ref_no;
            } else {
                Log::info("Refund cannot be done for UNPAID order.");
                ///return response()->json(['success' => false], 200);
            }
        }
    }

}

