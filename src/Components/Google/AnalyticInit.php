<?php

namespace Amplify\Frontend\Components\Google;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\Frontend\Store\AnalyticsBus;
use Amplify\System\Backend\Models\Category;
use Amplify\System\Backend\Models\Product;
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
     * @throws \ErrorException
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

        if (!empty($tag_manager_id)) {
            $this->pageAnalyticDataForGA();
            logger()->debug("analytics processed");
        }

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

            if ($categoryId = store()->eaProductDetail?->getCategories()?->getSuggestedCategoryID()) {
                $categories = Category::categoryTree($categoryId);
                if (!empty($categories)) {
                    $data['category'] = $categories->pluck('category_name')->implode(' > ');
                }
            }

            /**
             * @var Product $product
             */
            $product = \store('productModel');

            $data['keywords'] = $product->meta_keywords ?? '';
            $data['name'] = $product->product_name ?? 'Not Found';
            $data['description'] = $product->short_description ?? 'Not Found';
            $data['sku'] = $product->product_code ?? 'Not Found';
            $data['mpn'] = $product->manufacturer ?? '';

            if (!empty($product->gtin_number)) {
                $data['gtin'] = $product->gtin_number ?? '';
            }

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

            if ($product?->productImage()?->exists()) {
                $data['image'] = [];

                $productImage = $product?->productImage;

                $data['image'][] = $productImage->main ?? null;
                $data['image'][] = $productImage->thumbnail ?? null;
                $data['image'] = array_merge($data['image'], $productImage->additional ?? []);

                $data['image'] = array_unique($data['image']);

                $data['image'] = array_filter($data['image'], fn($item) => !empty($item));

            }

            if ($product?->default_document_type) {

                $defaultDocument = $product?->default_document_type;

                $data['subjectOf']['@type'] = 'CreativeWork';
                $data['subjectOf']['name'] = $defaultDocument->name ?? 'Specifications';
                $data['subjectOf']['description'] = $defaultDocument->description ?? 'Product Specifications';
                $data['subjectOf']['url'] = $defaultDocument->file_path ?? '#';
                $data['subjectOf']['encodingFormat'] = 'application/' . $defaultDocument->media_type ?? 'pdf';

            }

            $data['offers']['@type'] = 'Offer';
            $data['offers']['url'] = request()->url();
            $data['offers']['priceCurrency'] = config('amplify.basic.global_currency', 'USD');
            $data['offers']['price'] = (string)$product->selling_price;
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
        /**
         * @var AnalyticsBus $analytics
         */
        $analytics = app('analytics');

        $analytics->put(payload: customer_check()
            ? ['sei_user_type' => 'logged_in', 'sei_user_id' => customer(true)->getKey(), 'sei_user_name' => customer(true)->name ?? 'Guest']
            : ['sei_user_type' => 'guest', 'sei_user_id' => 'public', 'sei_user_name' => 'Guest']);


        if (session()->has('loggedIn')) {
            $analytics->put('login', ['event' => 'login', 'method' => 'password', 'ecommerce' => null]);
        }

        if (session()->has('customerSignedUp')) {
            $analytics->put('sign_up', ['event' => 'sign_up', 'method' => 'registration', 'type' => 'new_retail_customer', 'ecommerce' => null]);
        }

        if (session()->has('contactSignedUp')) {
            $analytics->put('sign_up', ['event' => 'sign_up', 'method' => 'registration', 'type' => 'request_account', 'ecommerce' => null]);
        }

        if ($page = store('dynamicPageModel')) {

            match ($page->page_type) {
                'shop' => $this->shopAnalytics($analytics),
                'single_product' => $this->productAnalytics($analytics),
                'cart' => $this->cartAnalytics('view_cart', $analytics),
                'checkout' => $this->cartAnalytics('begin_checkout', $analytics),
                default => [],
            };
        }
    }

    private function shopAnalytics(&$analytics)
    {
        $event = [
            'event' => 'view_item_list',
            'ecommerce' => [
                'currency' => config('amplify.basic.global_currency', 'USD'),
                'items' => [],
            ]
        ];

        /**
         * @var RemoteResults $eaResponse
         */
        if ($eaResponse = store('eaProductsData')) {

            $searchStates = $eaResponse->getStateInfo();

            $currentState = end($searchStates);

            switch ($currentState->getType()) {
                //category
                case 1:
                    $cts = collect($searchStates)->filter(fn($item) => $item->getType() == 1);
                    $name = [];
                    foreach ($cts as $cs) {
                        $name[] = $cs->getValue();
                    }

                    $event['ecommerce']['item_list_name'] = 'Category: ' . implode(' > ', $name);
                    $event['ecommerce']['item_list_id'] = 'category_' . $eaResponse->getSuggestedCategoryID();
                    break;

                //attribute
                case 2:
                    $ats = collect($searchStates)->filter(fn($item) => $item->getType() == 2);
                    $name = [];
                    foreach ($ats as $at) {
                        $name[] = $at->getName() . ': ' . $at->getValue();
                    }

                    $event['ecommerce']['item_list_name'] = 'Category: ' . $eaResponse->getSuggestedCategoryTitle() . ' | ' . implode(' | ', $name);
                    $event['ecommerce']['item_list_id'] = 'category_' . $eaResponse->getSuggestedCategoryID() . '_filtered';
                    break;

                //search
                default:
                    if (request()->filled('q')) {
                        $event['search_term'] = request('q');
                        $event['ecommerce']['item_list_name'] = 'Search Results: ' . $event['search_term'];
                        $event['ecommerce']['item_list_id'] = 'search_results';
                    }
            }

            $currentPage = $eaResponse->getCurrentPage();
            $resultPerPage = $eaResponse->getResultsPerPage();

            if (!$eaResponse->noResultFound()) {

                $categoryArray = [];

                $categories = explode('////', $eaResponse->getBreadCrumbTrail()->getPureCategoryPath());

                array_shift($categories);

                foreach ($categories as $index => $category) {
                    $suffix = $index == 0 ? '' : $index + 1;
                    $categoryArray["item_category{$suffix}"] = $category;
                }

                foreach (store('productPaginate', []) as $index => $product) {

                    $item = [
                        'index' => (($currentPage - 1) * $resultPerPage) + $index + 1,
                        'item_id' => $product->Sku_ProductCode ?? $product->Product_Code,
                        'item_name' => $product->Product_Name,
                        'item_brand' => $product->Manufacturer,
                        'manufacturer' => $product->Manufacturer,
                        'manufacturer_part_number' => $product->MPN,
                        'uom' => $product->ERP?->UnitOfMeasure ?? $product->UoM,
                        'price' => floatval(number_format($product->ERP?->ListPrice ?? $product->Msrp?->toFloat(), 2)),
                        'customer_price' => floatval(number_format($product->ERP?->Price ?? $product->Price?->toFloat(), 2)),
                        'lead_time' => $product->ERP?->AverageLeadTime ?? null,
                        'pack_size' => floatval($product->ERP?->QuantityInterval ?? $product->qty_interval ?? 1),
                        'availability' => ($product->InStock ?? false) ? 'In Stock' : 'Out of Stock',
                        'quantity' => $product->min_order_qty ?? 1,
                    ];

                    $item['discount'] = abs($item['price'] - $item['customer_price']);

                    $event['ecommerce']['items'][] = array_merge($item, $categoryArray);
                }
            }
        }

        $analytics->put('view_item_list', $event);
    }

    private function productAnalytics(&$analytics): void
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
        if ($eaResponse = store('eaProductDetail')) {

            if (!$eaResponse->noResultFound()) {

                $product = collect(store('productPaginate', $eaResponse->getProducts()))->first();

                $categoryArray = [];

                if ($currentCategory = $eaResponse->getCategories()->getDetailedSuggestedIDs()) {
                    if (!empty($currentCategory)) {
                        $categories = Category::categoryTree($currentCategory);
                        foreach ($categories as $index => $category) {
                            $suffix = $index == 0 ? '' : $index + 1;
                            $categoryArray["item_category{$suffix}"] = $category->category_name;
                        }
                    }
                }

                $item = [
                    'item_id' => $product->Sku_ProductCode ?? $product->Product_Code,
                    'item_name' => $product->Product_Name,
                    'item_brand' => $product->Manufacturer,
                    'manufacturer' => $product->Manufacturer,
                    'manufacturer_part_number' => $product->MPN,
                    'uom' => $product->ERP?->UnitOfMeasure ?? $product->UoM,
                    'price' => floatval(number_format($product->ERP?->ListPrice ?? $product->Msrp?->toFloat(), 2)),
                    'customer_price' => floatval(number_format($product->ERP?->Price ?? $product->Price?->toFloat(), 2)),
                    'lead_time' => $product->ERP?->AverageLeadTime ?? null,
                    'pack_size' => floatval($product->ERP?->QuantityInterval ?? $product->qty_interval ?? 1),
                    'availability' => ($product->InStock ?? false) ? 'In Stock' : 'Out of Stock',
                    'quantity' => $product->min_order_qty ?? 1,
                    ...$categoryArray,
                ];

                $item['discount'] = abs($item['price'] - $item['customer_price']);

                $event['ecommerce']['value'] = !empty($item['price']) ? round($item['price'], 2) : null;

                $event['ecommerce']['items'][] = $item;
            }
        }

        $analytics->put('view_item', $event);
    }

    private function cartAnalytics($event, &$analytics): void
    {
        $cart = getCart();

        $analytics->put($event, [
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
        ]);
    }
}
