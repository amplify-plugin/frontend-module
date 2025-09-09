<?php

return [
    'excluded_page_types' => ['static_page', 'shop_by_catalog'],
    'shop_page_default_view' => 'list',
    'fallback_image_path' => 'assets/img/No-Image-Placeholder-min.png',
    'easyask_single_product_index' => 'id',
    'mega_menu_max_height' => '310px',
    'enable_language' => false,
    'enable_exchange_reward' => true,
    'exchange_reward_client_token' => '078bcc74',
    'exchange_reward_secret' => '5016e7d76f9c3111bf0d01773f4791ea',
    'exchange_reward_baseurl' => 'https://safetyproducts.online-rewards.com/rpc/',
    'mobile_screen_menu' => 'mobile-menu',
    'user_account_top_menu' => 'account-menu',
    'user_account_sidebar_menu' => 'account-sidebar',
    'site_primary_menu' => 'primary-menu',
    'guest_default' => null,
    'guest_checkout_warehouse' => null,
    'guest_checkout' => false,
    'force_shipping_address_customer_id' => null,
    'guest_add_to_cart' => false,
    'product_available_text' => 'Available',
    'product_not_available_text' => 'Available in 3-5 days',
    'right_sidebar' => true,
    'styles' => [
        'plugin-style' => [
            // sayt search
            'vendor/easyask-sayt/css/sayt.css',
        ],
        'custom-style' => [
            'css/global.css',
        ],
        'internal-style' => [

        ],
    ],
    'scripts' => [
        'head-script' => [
            'js/modernizr.min.js',
        ],
        'plugin-script' => [

        ],
        'template-script' => [

        ],
        'custom-script' => [
            'js/frontend-utility.js',
        ],
        'internal-script' => [

        ],
        'footer-script' => [
        ],
    ],
];
