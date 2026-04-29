<?php

if (!function_exists('product_not_avail_message')) {
    function product_not_avail_message()
    {
        return trans(config('amplify.frontend.product_not_available_text', 'Call for Pricing'));
    }
}

if (!function_exists('product_out_stock_message')) {
    function product_out_stock_message()
    {
        return trans(config('amplify.frontend.product_available_text', 'Available in 3-5 days'));
    }
}

if (!function_exists('frontend_permissions')) {
    function frontend_permissions(): array
    {
        return [
                'account-summary' => 'allow-account-summary',
                'checkout' => 'choose-warehouse,choose-shipto,credit-card-payment,payment-on-accounts',
                'contact-management' => 'l,v,a,u,r',
                'dashboard' => 'allow-dashboard',
                'favorites' => 'manage-global-list,use-global-list,manage-personal-list',
                'invoices' => 'v,pay',
                'login-management' => 'manage-logins,impersonate',
                'message' => 'messaging',
                'order' => 'v,c,add-to-cart',
                'order-approval' => 'approve',
                'order-processing-rules' => 'manage-rules',
                'order-rejected' => 'l,v',
                'past-items' => 'past-items-list, past-items-history',
                'profile' => 'change-start-page',
                'quote' => 'v,rfq',
                'reports' => 'summary',
                'role' => 'v,manage',
                'saved-carts' => 'l',
                'ship-to-addresses' => 'l,v,a,u,r',
                'shop' => 'add-to-cart,browse',
                'switch-account' => 'switch-account',
                'ticket' => 'tickets',
            ];
    }
}
