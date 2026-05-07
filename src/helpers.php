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
            'shop' => 'add-to-cart,browse,search,search-in-result,in-stock-filter,brands,banner,categories',
            'switch-account' => 'switch-account',
            'ticket' => 'tickets',
        ];
    }
}

if (!function_exists('cart_count_badge')) {
    function cart_count_badge($cart)
    {
        $count = (config('amplify.frontend.cart_item_badge_style', 'items') == 'items')
            ? $cart->cartItems->count()
            : $cart->cartItems->sum('quantity');

        return match (true) {
            $count > 99 => '99+',
            $count < 1 => '',
            default => (string)$count,
        };
    }
}
