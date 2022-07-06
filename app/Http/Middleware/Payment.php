<?php 

namespace App\Http\Middleware; 

use Closure; 
use Exception; 
use App\Configurations; 
use App\Payments; 
use App\User; 
use Illuminate\Support\Facades\Auth; 

class Payment {
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $validation = $this->validateRequest($request);
        if ($validation !== true) {
            return $validation;
        }
        return $next($request);
    }
    /**
     * Checks we have a valid request.
     *
     * @param \Illuminate\Http\Request $request The request object.
     *
     * @return bool|\Illuminate\Http\RedirectResponse
     */
    protected function validateRequest($request) {
        $order_id = $request['order_id'];
        $api_version = env('SHOPIFY_API_VERSION');
        $shop_domain = $request['shop'];
        $shop = $this->findShop($shop_domain);
        $config = Configurations::where(['meta_key' => $shop_domain . '_fawrypay_detail'])->first();
        $meta_value = unserialize($config->meta_value);
        //dd($shop);
        try {
            $shop->api()->setApiKey($meta_value['app_key']);
            $shop->api()->setApiSecret($meta_value['app_secret']);
            $order = $shop->api()->rest('GET', "admin/api/$api_version/orders/$order_id.json");
            if ($order->errors) {
                throw new Exception('You are trying to process invalid order.');
            }
        } catch (Exception $e) {
            throw new Exception('You are not authorized to access this page.');
        }
        $request->session()->put('order_id', $order_id);
        $config = Configurations::where(['meta_key' => $shop_domain . '_fawrypay_detail'])->first();
        if ($config == null) {
            throw new Exception('Fawry Pay is not setup correctly, please contact store owner.');
        }
        $payment = Payments::where(['order_id' => $order_id, 'processed' => 1])->first();
        if ($payment !== null && !$order->errors) {
            return redirect($order->body->order->order_status_url);
        }
        // Everything is fine!
        return true;
    }
    protected function findShop($shop) {
        $config = User::where(['name' => $shop])->first();
        if ($config !== null) {
            return $config;
        }
    }
}

