<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Log;
use App\Configurations;
use Illuminate\Support\Facades\View;

class ConfigurationController extends Controller {

    public function index(Request $request) {
        $shop = Auth::user();
        $domain = $shop->getDomain()->toNative();
        $config = [
            'enable_live' => '', 'live_account_number' => '', 'live_hash_key' => '',
            'sandbox' => '', 'sandbox_account_number' => '', 'sandbox_hash_key' => '',
            'app_name' => '', 'app_key' => '', 'app_secret' => '',
        ];
        $meta_key = $domain . '_fawrypay_detail';
        $settings = Configurations::where(['meta_key' => $meta_key])->first();
        if ($settings !== null) {
            $config = unserialize($settings->meta_value);
            $config['shop'] = $shop->shopify_domain;
            return View::make('welcome', ['settings' => $config, 'shop' => $shop]);
        } else {
            return View::make('welcome', ['settings' => '', 'shop' => $shop]);
        }
    }

    public function setConfig(Request $request) {
        $shop = Auth::user();
        $domain = $shop->getDomain()->toNative();
        if ($request['enable_live']) {
            $request->validate([
                'live_account_number' => 'required',
                'live_hash_key' => 'required',
                'app_name' => 'required',
                'app_key' => 'required',
                'app_secret' => 'required'
            ]);
        }

        if ($request['sandbox']) {
            $request->validate([
                'sandbox_account_number' => 'required',
                'sandbox_hash_key' => 'required',
                'app_name' => 'required',
                'app_key' => 'required',
                'app_secret' => 'required'
            ]);
        }

        if (!$request['sandbox'] && !$request['enable_live']) {
            $request->validate([
                'sandbox' => 'required',
                'sandbox_account_number' => 'required',
                'sandbox_hash_key' => 'required',
                'app_name' => 'required',
                'app_key' => 'required',
                'app_secret' => 'required'
            ]);
        }


        $data = array(
            "enable_live" => $request['enable_live'],
            "sandbox" => $request['sandbox'],
            "sandbox_account_number" => $request['sandbox_account_number'],
            "sandbox_hash_key" => $request['sandbox_hash_key'],
            "live_account_number" => $request['live_account_number'],
            "live_hash_key" => $request['live_hash_key'],
            "app_name" => $request['app_name'],
            "app_key" => $request['app_key'],
            "app_secret" => $request['app_secret']
        );
        $meta_value = serialize($data);
        $meta_key = $domain . '_fawrypay_detail';
        $settings = Configurations::updateOrCreate(['meta_key' => $meta_key], [
                    'store_id' => $shop->id,
                    'meta_value' => $meta_value,
                    'meta_key' => $meta_key,
        ]);
        $data['shop'] = $domain;
        $settings->touch();
        return View::make('welcome', ['shop' => $shop, 'settings' => $data]);
    }

}

