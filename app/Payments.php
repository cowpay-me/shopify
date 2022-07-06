<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payments extends Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payments';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $fillable = ['order_id', 'email', 'txid', 'tx_action', 'customer_id', 'amount', 'neutronpay_txn_fee', 'neutronpay_order_id', 'amount_paid', 'signature', 'status', 'refund_response', 'processed', 'order_status_url', 'shop_id'];

    public function shop() {
        return $this->belongsTo('App\Shop', 'shop_id');
    }

}
