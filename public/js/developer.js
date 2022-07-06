//// Checkbox to enable disable square payment
//function activateLive() {
//    var checkBox = document.getElementById("enable_live");
//    if (checkBox.checked == true) {
//        document.getElementById("enable_live").value = '1';
//    } else {
//        document.getElementById("enable_live").value = '0';
//    }
//}
//if ((jQuery('#enable_live').val() == '0') || (jQuery('#enable_live').val() == '') || (typeof jQuery('#enable_live').val() == 'undefined')) {
//    $("#enable_live").removeAttr("checked");
//} else if ((jQuery('#enable_live').val() == '1')) {
//    $("#enable_live").attr("checked", "checked");
//}
// 
//jQuery(document).ready(function ($) {
//    $("#config_save").on('click', function (event) {
//        event.preventDefault();
//        if ($('#enable_live').is(':checked') && $('#sandbox').is(':checked') && (isblankSquare_live() || isblankSquare_test())) {
//            alert("Fill all fields in Production & Sandbox section of Square Payment Settings.");
//        } else if (!$('#enable_live').is(':checked') && $('#sandbox').is(':checked') && (isblankSquare_test())) {
//            alert("Enter all fields in Sandbox section of Square Payment Settings.");
//        } else if ($('#enable_live').is(':checked') && !$('#sandbox').is(':checked') && (isblankSquare_live())) {
//            alert("Enter all fields in Production section of Square Payment Settings.");
//        } else if (!$('#enable_live').is(':checked') && !$('#sandbox').is(':checked')) {
//            $("#payment-config-form").submit();
//        } else {
//            $("#payment-config-form").submit();
//        }
//    });
//})
//
//function isblankSquare_test() {
//    if ((jQuery('#sandbox_account_number').val() == '') || (jQuery('#sandbox_hash_key').val() == '') ) {
//        return true;
//    } else {
//        return false;
//    }
//}
//function isblankSquare_live() {
//    if ((jQuery('#live_account_number').val() == '') || (jQuery('#live_hash_key').val() == '')) {
//        return true;
//    } else {
//        return false;
//    }
//}