@extends('layouts.default')
@section('content')

<script src= "<?= $script_src; ?>"></script> 

<!--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">-->

<div class="container configurations-index"style="display:none;">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">FawryPay Payment</h5>
            <p class="card-subtitle mb-2 text-muted"></p>
        </div> 

        <div class="card-body">
            <input type="button" onclick="FawryPay.checkout(chargeRequest, '<?= $base_url ?>/success-charge', '<?= $base_url ?>/failed-charge');"  alt="Pay" class="btn btn-primary" id="xsrrs" value ="Pay"/> 
        </div>
    </div>
</div> 

@endsection

@section('scripts')
<script>
    var chargeRequest = {};
    chargeRequest.language = 'en-gb';
    chargeRequest.merchantCode = "<?= $merchant_code ?>";
    chargeRequest.merchantRefNumber = "<?= $merchant_ref_no ?>";
    chargeRequest.customer = {}
    chargeRequest.customer.name = "<?= $order->customer->first_name . ' ' . $order->customer->last_name ?>";
    chargeRequest.customer.mobile = "<?= str_replace("+20","0", $order->customer->phone) ?>";
    chargeRequest.customer.email = "<?= $order->customer->email ?>";
    chargeRequest.customer.customerProfileId = "<?= $order->customer->email ?>";
    chargeRequest.order = {};
    chargeRequest.order.description = "<?= $order->note ?>";
    chargeRequest.order.expiry = '';
    chargeRequest.customer.address = {};
    chargeRequest.customer.address.receiverName = "<?= $order->shipping_address->name ?>";
    chargeRequest.customer.address.governorate = "<?= $order->shipping_address->city ?>";
    chargeRequest.customer.address.city = "<?= $order->shipping_address->city ?>";
    chargeRequest.customer.address.area = "<?= $order->shipping_address->zip ?>";
    chargeRequest.customer.address.address = "<?= $order->shipping_address->address1 ?>";
    var lineItems = '<?= $line_items ?>';
    var line_items = JSON.parse(lineItems);
    chargeRequest.order.orderItems = line_items;
    chargeRequest.signature = "<?= $signature ?>";
    console.log(chargeRequest);
    setTimeout(function () {
        document.getElementById("xsrrs").click();
    }, 3000);
</script>
@endsection
