<?php

if (!function_exists('product_not_avail_message')) {
    function product_not_avail_message() {
        return config('amplify.frontend.product_not_available_text', 'Call for Pricing');
    }
}

if (!function_exists('product_out_stock_message')) {
    function product_out_stock_message() {
        return config('amplify.frontend.product_available_text', 'Available in 3-5 days');
    }
}
