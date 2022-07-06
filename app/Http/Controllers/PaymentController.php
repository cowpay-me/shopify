<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Configurations;
use App\Payments;
use Log;
use App\User;
use hash;

class PaymentController extends Controller {

    public function processing(Request $request) {
        $order_id = $request['order_id'];
        $api_version = env('SHOPIFY_API_VERSION');
        $shop_domain = $request['shop'];
        $shop = $this->findShop($shop_domain);
        $config = Configurations::where(['meta_key' => $shop_domain . '_fawrypay_detail'])->first();
        $meta_value = unserialize($config->meta_value);
        if (isset($meta_value['sandbox']) && $meta_value['sandbox'] == '1')
            $sandbox = TRUE;
        else
            $sandbox = FALSE;
        $order_data = $shop->api()->rest('GET', "/admin/api/$api_version/orders/$order_id.json");
        $order = $order_data->body->order;

        $merchant_code = $sandbox ? $meta_value['sandbox_account_number'] : $meta_value['live_account_number'];
        $secure_hash_key = $sandbox ? $meta_value['sandbox_hash_key'] : $meta_value['live_hash_key'];
        $api_url = $sandbox ? env('PAYMENT_STATUS_SANDBOX_URL') : env('PAYMENT_STATUS_PRODUCTION_URL');
        $script_src = $sandbox ? env('FAWRYPAY_SANDBOX_SCRIPT') : env('FAWRYPAY_PRODUCTION_SCRIPT');
        $signature = hash('sha256', $merchant_code . $order_id . $secure_hash_key);

        $line_item_arr = array();
        $line = array();
        $items = '';
        foreach ($order->line_items as $line_item) {
            $line['productSKU'] = ($line_item->sku !== '') ? $line_item->sku : $line_item->product_id;
            $line['description'] = $line_item->title;
            $line['price'] = $line_item->price;
            $line['quantity'] = $line_item->quantity;
            $line_item_arr[] = $line;
            $items .= ($line_item->sku !== '') ? $line_item->sku . $line_item->quantity . $line_item->price : $line_item->product_id . $line_item->quantity . $line_item->price;
        }

        $merchant_ref_no = $order_id;
        $customer_profile_id = $order->customer->id;
        $signature = hash('sha256', $merchant_code . $merchant_ref_no . $order->customer->email . $items . $secure_hash_key);
        $data = [
            'merchant_code' => $merchant_code,
            'order_id' => $order_id,
            'line_items' => json_encode($line_item_arr),
            'base_url' => url('/'),
            'order' => $order,
            'merchant_ref_no' => $merchant_ref_no,
            'signature' => $signature,
            'script_src' => $script_src
        ];

        $payments = Payments::where(['order_id' => $order_id])->first();
        if ($payments == null) {
            $payments = new Payments();
            $payments->order_id = $order_id;
            $payments->email = $order->customer->email;
            $payments->store_id = $shop->id;
            $payments->order_status_url = $order->order_status_url;
            $payments->save();
												   
        }
        if($payments->processed)
            return redirect($order->order_status_url);
        else
            return view('pay-button')->with($data);
    }

    public function processed(Request $request) {
        $order_id = $request['order_id'];
        $payment = Payments::where(['order_id' => $order_id])->first();
        if ($payment !== null) {
            return response()->json(['success' => 'true', 'processed' => $payment->processed, 'message' => 'Processed'], 200);
        }
        return response()->json(['success' => 'false', 'processed' => 0, 'message' => 'Thank you'], 200);
    }

    private function findShop($shop) {
        $config = User::where(['name' => $shop])->first();
        if ($config !== null) {
            return $config;
        }
    }

    public function successCharge(Request $request) {
    
        $charge_response = json_decode($request['chargeResponse']);
        $order_id = $charge_response->merchantRefNumber;
        $fawry_ref_no = $charge_response->fawryRefNumber;
        $payments = Payments::where(['order_id' => $order_id])->first();
        if ($payments == null)
            return response()->json(['status' => 'false', 'message' => 'Payment not exists']);
        $store_id = $payments->store_id;
        $order_status_url = $payments->order_status_url;
        $config = Configurations::where(['store_id' => $store_id])->first();
        if ($config == null)
            return response()->json(['status' => 'false', 'message' => 'FawryPay payment gateway keys are not added.']);
        $meta_value = unserialize($config->meta_value);
        if (isset($meta_value['sandbox']) && $meta_value['sandbox'] == '1') {
            $sandbox = TRUE;
        } else {
            $sandbox = FALSE;
        }
        $merchant_code = $sandbox ? $meta_value['sandbox_account_number'] : $meta_value['live_account_number'];
        $hash_key = $sandbox ? $meta_value['sandbox_hash_key'] : $meta_value['live_hash_key'];
        $api_url = $sandbox ? env('PAYMENT_STATUS_SANDBOX_URL') : env('PAYMENT_STATUS_PRODUCTION_URL');
        $signature = hash('sha256', $merchant_code . $order_id . $hash_key);
        $decoded_result = $this->GetPaymentStatus($merchant_code, $order_id, $signature, $api_url);
        if ($decoded_result->statusCode == 200) {
            $update_data = [
                'payment_method' => $decoded_result->paymentMethod,
                'customer_id' => $decoded_result->merchantRefNumber,
                'processed' => 1,
            ];
            Payments::where('order_id', $order_id)->update($update_data);
            $payments = Payments::where('order_id', $order_id)->first();
            $shopify_order = $this->manageShopifyOrder($payments, $order_id, $decoded_result->paymentStatus, $store_id, $decoded_result->paymentAmount, $decoded_result->referenceNumber);
            return redirect($order_status_url);
        } else {
            return response()->json(['status' => 'false', 'message' => json_encode($decoded_result)]);
        }
    }

    public function FailedCharge(Request $request) {

        Log::info('Failed charge:- ' . $request['merchantRefNum']);

        $order_id = $request['merchantRefNum'];
        $payments = Payments::where(['order_id' => $order_id])->first();

        if ($payments == null)
            return response()->json(['status' => 'false', 'message' => 'Payment not exists']);

        $store_id = $payments->store_id;
        $order_status_url = $payments->order_status_url;

        $api_version = env('SHOPIFY_API_VERSION');
        Log::info('manage shopify order');
        $user_shop = User::where(['id' => $store_id])->first();
        
        $transaction = ["transaction" => ["kind" => "void"]];
        $response = $user_shop->api()->request("POST", "/admin/api/$api_version/orders/$order_id/transactions.json", $transaction);

        $body = ["reason" => "declined", 'restock' => true, "email" => "true", "note" => "Payment declined by customer."];
        $user_shop->api()->request("POST", "/admin/api/$api_version/orders/$order_id/cancel.json", $body);
        
        $payments->payment_status = 'CANCELLED';
        $payments->processed = 1;
        $payments->update();
        
        return redirect($order_status_url);
    }

    private function GetPaymentStatus($merchant_code, $order_id, $signature, $api_url) {
        Log::info('merchantRefNumber-' . $order_id);
        Log::info('merchantCode-' . $merchant_code);
        Log::info('signature-' . $signature);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "$api_url?merchantCode=$merchant_code&merchantRefNumber=$order_id&signature=$signature",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(),
        ));
        $response = curl_exec($curl);
		$err = curl_error($curl);
		Log::info('error-' . $err);
							 
        $decoded_result = json_decode($response);
        Log::info('decoded_result-' . json_encode($decoded_result));
        return $decoded_result;
    }

    protected function manageShopifyOrder($payments, $order_id, $paymentStatus, $store_id, $amount, $fawry_ref_no) {
        $api_version = env('SHOPIFY_API_VERSION');
        Log::info('manage shopify order');
        $user_shop = User::where(['id' => $store_id])->first();
        if ($paymentStatus == 'PAID' || $paymentStatus == 'DELIVERED') {
            $transaction = ["transaction" => ["kind" => "capture"]];
            $response = $user_shop->api()->rest("POST", "/admin/api/$api_version/orders/$order_id/transactions.json", $transaction);
            Log::info('transaction api response -' . json_encode($response));
            $payments->txid = $fawry_ref_no;
        }
        if ($paymentStatus == 'CANCELLED' || $paymentStatus == 'EXPIRED' || $paymentStatus == 'FAILED') {
            $transaction = ["transaction" => ["kind" => "void"]];
            $response = $user_shop->api()->request("POST", "/admin/api/$api_version/orders/$order_id/transactions.json", $transaction);
            $body = ["reason" => "declined", 'restock' => true, "email" => "true", "note" => "Payment declined by customer."];
            $user_shop->api()->request("POST", "/admin/api/$api_version/orders/$order_id/cancel.json", $body);
        }

        $order_data = ["order" => ["note" => 'FawryPay order number: '.$fawry_ref_no]]; 
        $response = $user_shop->api()->request("PUT", "/admin/api/$api_version/orders/$order_id.json", $order_data);
        Log::info('Order note API response -' . json_encode($response));

        $payments->payment_status = $paymentStatus;
        $payments->amount = $amount;
        $payments->update();
        Log::info('manage shopify order Payments' . json_encode($payments));
        return response()->json(['success' => 'success'], 200);
    }

//    public function GetRefund(Request $request) {
//        $merchant_code = '1tSa6uxz2nTl878/el08Sg==';
//        $secure_hash_key = '9988fb28599c490fbdbd4254fcfacf54';
//        $api_url = 'https://atfawry.fawrystaging.com/ECommerceWeb/Fawry/payments/refund';
//        $ref_no = '943643956';
//        $total_amount = '5.00';
//        $reason = 'testing';
//        $signature = hash('sha256', $merchant_code . $ref_no . $total_amount . $reason . $secure_hash_key);
//        //$post_data = "merchantCode=$merchant_code&referenceNumber=$ref_no&refundAmount=$total_amount&reason=$reason&signature=$signature";
////        $post_data = '{
////	"merchantCode": "1tSa6uxz2nTl878/el08Sg==",
////	"referenceNumber": "943605660",
////	"refundAmount": "2.00",
////	"reason": "bad quality",
////	"signature":"ca28dd65f942effdec6b44fa75295f434949de21fd0f01efc65e9e47bb85c9d2"
////}';
//        $ch = curl_init();
//        $headers = array(
//            "Content-Type: application/json",
//        );
//        $postData = [
//            "merchantCode" => $merchant_code,
//            "referenceNumber" => $ref_no,
//            "refundAmount" => $total_amount,
//            "reason" => $reason,
//            "signature" => $signature
//        ];
//        curl_setopt($ch, CURLOPT_URL, 'https://atfawry.fawrystaging.com/ECommerceWeb/Fawry/payments/refund');
//        curl_setopt($ch, CURLOPT_POST, 1);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
//        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
//        //  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//        $result = curl_exec($ch);
//        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//        dd($statusCode);
//    }

    public function PaymentNotification(Request $request) {
        Log::info('Notification Request:- ' . $request);
        if (isset($request['MerchantRefNo'])) {
            $order_id = $request['MerchantRefNo'];
            $signature = $request['MessageSignature'];
            $payments = Payments::where(['order_id' => $order_id])->first();
            if ($payments == null)
                return response()->json(['success' => 'No order found in our database with this Order id'], 200);
            $store_id = $payments->store_id;
            $this->manageShopifyOrder($payments, $order_id, $request['OrderStatus'], $store_id, $request['Amount'], $request['FawryRefNo']);
        }
    }

}

