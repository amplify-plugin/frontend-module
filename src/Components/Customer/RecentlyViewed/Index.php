<?php

namespace Amplify\Frontend\Components\Customer\RecentlyViewed;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\Frontend\Services\RecentlyViewedProductService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class Index extends BaseComponent
{
    public Collection $products;

    public function __construct(
        public bool $showTitle = true,
        public string $title = 'Recently Viewed Products',
        public int $productsLimit = 20,
        public bool $showCartBtn = true,
        public string $cartButtonLabel = 'Add To Cart',
        public string $detailButtonLabel = 'View Details',
        public bool $showTopDiscountBadge = false,
        public bool $showOrderList = false,
        public string $orderListLabel = 'Order List',
        public bool $displayProductCode = true,
        public bool $displayShortDescription = false,
        public bool $displayManufacturer = false,
        public bool $showGuestPrice = false,
        public bool $showPrice = true,
        public bool $allowRemove = true,
        public bool $allowClear = true,
        public string $layout = 'card',
    ) {
        parent::__construct();

        $this->products = collect();
        $this->productsLimit = min($this->productsLimit, (int) config('amplify.recently_viewed.max_items', 20));
        $this->layout = in_array($this->layout, ['card', 'minimal'], true) ? $this->layout : 'card';
        $this->prepareData();
    }

    public function htmlAttributes(): string
    {
        $this->attributes = $this->attributes->class([
            'recently-viewed-layout-'.$this->layout,
        ]);

        return parent::htmlAttributes();
    }

    public function shouldRender(): bool
    {
        return config('amplify.recently_viewed.enabled', true);
    }

    public function render(): View|Closure|string
    {
        return view('widget::customer.recently-viewed.index');
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
        $productIds = $service->getProductIds(customer(true), $this->productsLimit);
        $this->products = $service->loadProducts($productIds);
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
            'allowRemove' => $this->allowRemove,
            'allowClear' => $this->allowClear,
        ]);
    }

    public function isMasterProduct($product): bool
    {
        return ! empty($product->has_sku) && empty($product->parent_id);
    }
}
