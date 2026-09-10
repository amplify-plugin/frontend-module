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
//            'checkout' => 'choose-warehouse,choose-shipto,credit-card-payment,payment-on-accounts',
            'contact' => 'l,v,c,u,d,impersonate',
//            'dashboard' => 'allow-dashboard',
            'order-list' => 'l,v,c,u,d',
            'invoices' => 'l,dt,pay',
//            'login-management' => 'manage-logins,impersonate',
            'message' => 'messaging',
            'order' => 'l,dt',
//            'order-approval' => 'approve',
//            'order-processing-rules' => 'manage-rules',
//            'order-rejected' => 'l,v',
            'past-items' => 'l,history',
//            'profile' => 'change-start-page',
//            'quote' => 'v,rfq',
//            'reports' => 'summary',
//            'role' => 'v,manage',
//            'saved-carts' => 'l',
            'address' => 'l,v,c,u,d,setDefault',
            'shop' => 'browse,search,search-in-result,in-stock-filter,brands,banner,categories',
//            'switch-account' => 'switch-account',
//            'ticket' => 'tickets',
            'cart' => 'add,v,remove,checkout,submit-quote',
            'product-compare' => 'manage,dt',
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

if (!function_exists('hasAccessOrFail')) {
    function hasAccessOrFail(...$permissions): void
    {
        abort_unless(customer(true)->canAny($permissions), 403,
            __('Unauthorized access - you do not have the necessary permissions to see this page.'));
    }
}

if (!function_exists('safe_rich_html')) {
    /**
     * Repair truncated / unclosed HTML so it cannot leak into the rest of the page.
     */
    function safe_rich_html(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        // Drop a truncated opening tag at the end (no closing '>'), e.g. <a href="https://...
        $html = preg_replace('/<[^>]*$/s', '', $html) ?? $html;
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $wrapped = '<div id="safe-rich-html-root">'.$html.'</div>';
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8">'.$wrapped,
            LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return $html;
        }

        $root = $document->getElementById('safe-rich-html-root');
        if (! $root instanceof DOMElement) {
            return $html;
        }

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }
}
