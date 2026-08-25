<?php

namespace Amplify\Frontend\Components\Product;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\Frontend\Services\RecentlyViewedProductService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * @class RecentlyViewed
 */
class RecentlyViewed extends BaseComponent
{
    public Collection $products;

    public function __construct(
        public bool $showTitle = true,
        public string $title = 'Recently Viewed',
        public string $seeAllLink = 'recent-viewed-products',
        public string $seeAllLabel = 'See All',
        public int $productsLimit = 20,
        public bool $showCartBtn = true,
        public string $cartButtonLabel = 'Add To Cart',
        public string $detailButtonLabel = 'View Details',
        public bool $smallButton = true,
        public bool $showTopDiscountBadge = false,
        public bool $showOrderList = false,
        public string $orderListLabel = 'Order List',
        public bool $showNavigation = true,
        public int $sliderItemGap = 15,
        public bool $displayProductCode = true,
        public bool $displayShortDescription = false,
        public bool $displayManufacturer = false,
        public bool $showGuestPrice = false,
        public bool $showPrice = true,
        public string $layout = 'card',
        public ?int $excludeProductId = null,
    ) {
        parent::__construct();

        $this->products = collect();
        $this->productsLimit = min($this->productsLimit, (int) config('amplify.recently_viewed.max_items', 20));
        $this->layout = $this->normalizeLayout($this->layout);
        $this->prepareData();
    }

    public function htmlAttributes(): string
    {
        $this->attributes = $this->attributes->class([
            'recently-viewed-layout-'.$this->layout,
        ]);

        return parent::htmlAttributes();
    }

    public function isMinimalLayout(): bool
    {
        return $this->layout === 'minimal';
    }

    public function hasSeeAllLink(): bool
    {
        return trim($this->seeAllLink) !== '';
    }

    public function seeAllUrl(): string
    {
        $link = trim($this->seeAllLink);

        if ($link === '') {
            return '';
        }

        if (preg_match('/^(https?:)?\/\//i', $link) === 1) {
            return $link;
        }

        return url(ltrim($link, '/'));
    }

    public function shouldRender(): bool
    {
        if (! config('amplify.recently_viewed.enabled', true)) {
            return false;
        }

        if (customer_check()) {
            return $this->products->isNotEmpty();
        }

        return true;
    }

    public function render(): View|Closure|string
    {
        //store()->offsetSet('productPaginate', $this->products);
        return view('widget::product.recently-viewed');
    }

    public function prepareData(): void
    {
        if (! config('amplify.recently_viewed.enabled', true)) {
            return;
        }

        if (! customer_check()) {
            return;
        }

        $service = app(RecentlyViewedProductService::class);
        $excludeProductId = (int) ($this->excludeProductId ?: 0);

        if (! $excludeProductId && request()->route('identifier')) {
            $productModel = store()->productModel ?? null;

            if ($productModel instanceof \Amplify\System\Backend\Models\Product) {
                $excludeProductId = (int) ($productModel->getKey() ?: 0);
            } elseif ($productModel instanceof \Amplify\System\Sayt\Classes\ItemRow) {
                $excludeProductId = (int) ($productModel->Amplify_Id ?? 0);
            } elseif (is_object($productModel)) {
                $excludeProductId = (int) (data_get($productModel, 'id') ?: data_get($productModel, 'Amplify_Id') ?: 0);
            }
        }

        $productIds = $service->getProductIds(customer(true), $this->productsLimit + ($excludeProductId ? 1 : 0));

        if ($excludeProductId) {
            $productIds = array_values(array_filter(
                $productIds,
                fn (int $id) => $id !== (int) $excludeProductId,
            ));
        }

        $productIds = array_slice($productIds, 0, $this->productsLimit);

        $this->products = $service->loadProducts($productIds);
    }

    public function carouselOptions(): string
    {
        $responsive = $this->isMinimalLayout()
            ? [
                '0' => ['items' => 1],
                '768' => ['items' => 2],
                '1200' => ['items' => 2],
            ]
            : [
                '0' => ['items' => 1],
                '576' => ['items' => 2],
                '768' => ['items' => 3],
                '991' => ['items' => 4],
                '1200' => ['items' => 5],
            ];

        return json_encode([
            'lazyLoad' => true,
            'animateIn' => 'fadeIn',
            'animateOut' => 'fadeOut',
            'dots' => true,
            'nav' => $this->showNavigation,
            'margin' => $this->sliderItemGap,
            'responsive' => $responsive,
        ]);
    }

    public function widgetSettings(): string
    {
        return json_encode([
            'layout' => $this->layout,
            'productsLimit' => $this->productsLimit,
            'showCartBtn' => $this->showCartBtn,
            'cartButtonLabel' => $this->cartButtonLabel,
            'detailButtonLabel' => $this->detailButtonLabel,
            'showPrice' => $this->showPrice,
            'showGuestPrice' => $this->showGuestPrice,
            'showTopDiscountBadge' => $this->showTopDiscountBadge,
            'showOrderList' => $this->showOrderList,
            'orderListLabel' => $this->orderListLabel,
            'displayProductCode' => $this->displayProductCode,
            'displayShortDescription' => $this->displayShortDescription,
            'displayManufacturer' => $this->displayManufacturer,
        ]);
    }

    public function isMasterProduct($product): bool
    {
        return ! empty($product->has_sku) && empty($product->parent_id);
    }

    protected function normalizeLayout(mixed $layout): string
    {
        $layout = strtolower(trim((string) ($layout ?: 'card')));

        return in_array($layout, ['card', 'minimal'], true) ? $layout : 'card';
    }
}
