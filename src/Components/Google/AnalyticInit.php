<?php

namespace Amplify\Frontend\Components\Google;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\System\Sayt\Classes\RemoteResults;
use Illuminate\Contracts\View\View;

/**
 * @class GoogleAnalytic
 */
class AnalyticInit extends BaseComponent
{
    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        return true;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|\Closure|string
    {
        $analytics_id = config('amplify.google.google_analytics_id', '');

        if ($analytics_id == null) {
            $analytics_id = '';
        }

        $analytics_url = config('amplify.google.google_analytics_url', '');

        if ($analytics_url == null) {
            $analytics_url = '';
        }

        $analytics_url = str_replace("?id={$analytics_id}", '', $analytics_url);

        $tag_manager_id = config('amplify.google.google_tag_manager_id');

        return view('widget::google.google-analytic', [
            'analytics_id' => $analytics_id,
            'analytics_url' => $analytics_url,
            'tag_manager_id' => $tag_manager_id,
        ]);
    }

    public function pageSchemaForGA()
    {
        $type = $this->determineGooglePageType();

        $data = [
            '@context' => 'https://schema.org',
            '@type' => $type,
            '@id' => $this->determineGooglePageId($type),
            'name' => config('app.name'),
            'url' => request()->url(),
            'logo' => config('amplify.cms.logo_path', '#'),
        ];

        if ($type != 'Organization') {
            $data['publisher']['@id'] = $this->determineGooglePageId('Organization');
        }

        if ($type == 'WebSite') {
            $data['potentialAction']['@type'] = 'SearchAction';
            $data['potentialAction']['target'] = frontendShopURL(['search', 'q' => '{search_term_string}']);
            $data['potentialAction']['query-input'] = 'required name=search_term_string';
        }

        if ($type == 'BreadcrumbList') {
            $count = 0;
            $data['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => ++$count,
                'name' => 'Home',
                'item' => frontendHomeURL(),
            ];
            $data['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => ++$count,
                'name' => \store()->dynamicPageModel->breadcrumb_title ?? \store()->dynamicPageModel->title,
                'item' => frontendHomeURL(),
            ];

        }

        if ($type == 'Product') {

            $product = \store('productModel');

            $data['name'] = $product->product_name ?? 'Not Found';
            $data['description'] = $product->short_description ?? 'Not Found';
            $data['sku'] = $product->product_code ?? 'Not Found';
            $data['mpn'] = $product->manufacturer ?? '';

            if ($product?->brand()?->exists()) {
                $data['brand']['@type'] = 'Brand';
                $data['brand']['name'] = $product->brand->title ?? '';
            } elseif (!empty($product->brand_name)) {
                $data['brand']['@type'] = 'Brand';
                $data['brand']['name'] = $product->brand_name ?? '';
            }

            if ($product?->manufacturerRelation()?->exists()) {
                $data['manufacturer']['@type'] = 'Organization';
                $data['manufacturer']['name'] = $product->manufacturerRelation->name ?? '';
            }

            $data['offers']['@type'] = 'Offer';
            $data['offers']['url'] = request()->url();
            $data['offers']['priceCurrency'] = config('amplify.basic.global_currency', 'USD');
            $data['offers']['availability'] = 'https://schema.org/InStock';
            $data['offers']['seller']['@type'] = 'Organization';
            $data['offers']['seller']['@id'] = $this->determineGooglePageId('Organization');
            $data['offers']['seller']['name'] = config('app.name');
        }

        return $data;

    }

    private function determineGooglePageType(): string
    {
        return match (request()->route()->getName()) {
            'frontend.index' => 'Organization',
            'frontend.shop.index' => 'WebSite',
            'frontend.shop.show' => 'Product',
            default => 'BreadcrumbList'
        };
    }

    private function determineGooglePageId(string $type): string
    {
        $baseUrl = trim(config('app.url'), '/');

        return match ($type) {
            'Organization' => "{$baseUrl}/#organization",
            'WebSite' => "{$baseUrl}/#website",
            'Product' => request()->url() . '/#product',
            default => request()->url() . '/#breadcrumb',
        };
    }

    /**
     * @throws \ErrorException
     */
    public function pageAnalyticDataForGA()
    {
        $data = [];

        $data[] = customer_check()
            ? ['sei_user_type' => 'logged_in', 'sei_user_id' => customer(true)->getKey(), 'sei_user_name' => customer(true)->name ?? 'Guest']
            : ['sei_user_type' => 'guest', 'sei_user_id' => 'public', 'sei_user_name' => 'Guest'];

        if (session()->has('loggedIn')) {
            $data[] = ['event' => 'login', 'method' => 'password', 'ecommerce' => null];
        }

        if (session()->has('customerSignedUp')) {
            $data[] = ['event' => 'sign_up', 'method' => 'registration', 'type' => 'new_retail_customer', 'ecommerce' => null];
        }

        if (session()->has('contactSignedUp')) {
            $data[] = ['event' => 'sign_up', 'method' => 'registration', 'type' => 'request_account', 'ecommerce' => null];
        }

        if ($page = store('dynamicPageModel')) {

            $data[] = match ($page->page_type) {
                'shop' => $this->shopAnalytics(),
                'single_product' => $this->productAnalytics(),
                'cart' => $this->cartAnalytics(),
                'checkout' => $this->cartAnalytics('begin_checkout'),
                default => [],
            };
        }

        return array_filter($data, fn($item) => !empty($item));
    }

    private function shopAnalytics(): array
    {
        $event = [
            'event' => 'view_item_list',
            'ecommerce' => [
                'item_list_name' => 'Search Results',
                'item_list_id' => 'search_results',
                'currency' => config('amplify.basic.global_currency', 'USD'),
                'items' => [],
            ]
        ];

        /**
         * @var RemoteResults $eaResponse
         */
        if ($eaResponse = store('eaProductsData')) {

            $currentPage = $eaResponse->getCurrentPage();
            $resultPerPage = $eaResponse->getResultsPerPage();

            if (!$eaResponse->noResultFound()) {
                foreach ($eaResponse->getProducts() as $index => $product) {
                    $event['ecommerce']['items'][] = [
                        'index' => (($currentPage - 1) * $resultPerPage) + $index + 1,
                        'item_id' => $product->Sku_ProductCode ?? $product->Product_Code,
                        'item_name' => $product->Product_Name,
                        'item_brand' => $product->Manufacturer,
                        'item_category' => null,
                    ];
                }
            }
        }

        return $event;
    }

    private function productAnalytics(): array
    {
        $event = [
            'event' => 'view_item',
            'ecommerce' => [
                'currency' => config('amplify.basic.global_currency', 'USD'),
                'items' => [],
            ]
        ];

        /**
         * @var RemoteResults $eaResponse
         */
        if ($eaResponse = store('eaProductsData')) {

            if (!$eaResponse->noResultFound()) {
                foreach ($eaResponse->getProducts() as $product) {

                    $price = $product->Price?->toFloat() ?? $product->Msrp?->toFloat() ?? null;

                    $event['ecommerce']['value'] = !empty($price) ? round($price, 2) : null;

                    $event['ecommerce']['items'][] = [
                        'item_id' => $product->Sku_ProductCode ?? $product->Product_Code,
                        'item_name' => $product->Product_Name,
                        'item_brand' => $product->Manufacturer,
                        'item_category' => null,
                    ];
                }
            }
        }

        return $event;
    }

    private function cartAnalytics($event = 'view_cart'): array
    {
        $cart = getCart();

        return [
            'event' => $event,
            'ecommerce' => [
                'currency' => config('amplify.basic.global_currency', 'USD'),
                'value' => round($cart->sub_total ?? 0, 2),
                'items' => $cart->cartItems->map(function ($cartItem) {
                    return [
                        'item_id' => $cartItem->product_code,
                        'item_name' => $cartItem->product_name,
                        'price' => round($cartItem->unitprice ?? 0, 2),
                        'quantity' => floatval($cartItem->quantity)
                    ];
                })->toArray(),
            ]
        ];
    }
}
