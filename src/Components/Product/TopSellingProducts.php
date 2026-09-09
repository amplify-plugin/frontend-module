<?php

namespace Amplify\Frontend\Components\Product;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\Frontend\Services\TopSellingProductsService;
use Amplify\System\Backend\Models\Product;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * @class TopSellingProducts
 */
class TopSellingProducts extends BaseComponent
{
    public Collection $products;

    public function __construct(
        public bool $showTitle = true,
        public string $title = 'Top Selling Products',
        public int $productsLimit = 10,
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
        public ?int $cacheTtl = null,
    ) {
        parent::__construct();

        $this->products = collect();
        $this->productsLimit = $this->normalizeProductsLimit($this->productsLimit);
        $this->layout = $this->normalizeLayout($this->layout);
        $this->cacheTtl = $this->normalizeCacheTtl($this->cacheTtl);
        $this->prepareData();
    }

    public function htmlAttributes(): string
    {
        $this->attributes = $this->attributes->class([
            'top-selling-products-layout-'.$this->layout,
        ]);

        return parent::htmlAttributes();
    }

    public function isMinimalLayout(): bool
    {
        return $this->layout === 'minimal';
    }

    public function shouldRender(): bool
    {
        $service = app(TopSellingProductsService::class);

        return $service->isEnabled() && $this->products->isNotEmpty();
    }

    public function render(): View|Closure|string
    {
        return view('widget::product.top-selling-products');
    }

    public function prepareData(): void
    {
        $service = app(TopSellingProductsService::class);

        if (! $service->isEnabled()) {
            return;
        }

        $excludeProductId = $this->resolveExcludeProductId();

        $productIds = $service->getTopSellingProductIds(
            $this->productsLimit,
            $excludeProductId,
            $this->cacheTtl
        );

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

    public function isMasterProduct(Product $product): bool
    {
        return ! empty($product->has_sku) && empty($product->parent_id);
    }

    protected function resolveExcludeProductId(): ?int
    {
        $excludeProductId = (int) ($this->excludeProductId ?: 0);

        if ($excludeProductId > 0) {
            return $excludeProductId;
        }

        if (! request()->route('identifier')) {
            return null;
        }

        $productModel = store()->productModel ?? null;

        if ($productModel instanceof Product) {
            $excludeProductId = (int) ($productModel->getKey() ?: 0);
        } elseif ($productModel instanceof \Amplify\System\Sayt\Classes\ItemRow) {
            $excludeProductId = (int) ($productModel->Amplify_Id ?? 0);
        } elseif (is_object($productModel)) {
            $excludeProductId = (int) (data_get($productModel, 'id') ?: data_get($productModel, 'Amplify_Id') ?: 0);
        }

        return $excludeProductId > 0 ? $excludeProductId : null;
    }


    protected function normalizeCacheTtl(mixed $ttl): int
    {
        if ($ttl === null || $ttl === '') {
            return app(TopSellingProductsService::class)->cacheTtl();
        }

        return max(0, (int) $ttl);
    }

    protected function normalizeProductsLimit(mixed $limit): int
    {
        $limit = (int) $limit;

        if (in_array($limit, [5, 10, 20], true)) {
            return $limit;
        }

        return app(TopSellingProductsService::class)->productsLimit();
    }

    protected function normalizeLayout(mixed $layout): string
    {
        $layout = strtolower(trim((string) ($layout ?: 'card')));

        return in_array($layout, ['card', 'minimal'], true) ? $layout : 'card';
    }
}
