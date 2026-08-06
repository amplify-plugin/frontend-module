<?php

namespace Amplify\Frontend\Components\Product;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\Frontend\Services\PurchasedTogetherContextResolver;
use Amplify\Frontend\Services\PurchasedTogetherProductService;
use Amplify\System\Backend\Models\Product;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * @class PurchasedTogether
 */
class PurchasedTogether extends BaseComponent
{
    public Collection $products;

    public ?string $context = null;

    public ?int $resolvedProductId = null;

    public function __construct(
        public bool $showTitle = true,
        public string $title = 'Frequently Purchased Together',
        public mixed $productId = null,
        public array $cartProductIds = [],
        public int $productsLimit = 8,
        public bool $showCartBtn = true,
        public string $cartButtonLabel = 'Add To Cart',
        public string $detailButtonLabel = 'View Details',
        public bool $smallButton = true,
        public bool $showTopDiscountBadge = false,
        public bool $showOrderList = false,
        public string $orderListLabel = 'Order List',
        public bool $showNavigation = true,
        public int $sliderItemGap = 20,
        public bool $displayProductCode = true,
        public bool $displayShortDescription = false,
        public bool $displayManufacturer = false,
        public bool $showGuestPrice = false,
        public bool $showPrice = true,
        public string $layout = 'card',
    ) {
        parent::__construct();

        $this->products = collect();
        $this->productId = $this->normalizeProductId($this->productId);
        $this->layout = $this->normalizeLayout($this->layout);
        $this->prepareData();
    }

    public function htmlAttributes(): string
    {
        $this->attributes = $this->attributes->class([
            'purchased-together-layout-' . $this->layout,
        ]);

        return parent::htmlAttributes();
    }

    public function isMinimalLayout(): bool
    {
        return $this->layout === 'minimal';
    }

    public function shouldRender(): bool
    {
        return config('amplify.purchased_together.enabled', true) && $this->products->isNotEmpty();
    }

    public function render(): View|Closure|string
    {
        return view('widget::product.purchased-together');
    }

    public function prepareData(): void
    {
        $contextResolver = app(PurchasedTogetherContextResolver::class);
        $service = app(PurchasedTogetherProductService::class);

        $this->context = $contextResolver->resolveContext($this->productId, $this->cartProductIds);
        $suggestedIds = [];

        if ($this->context === PurchasedTogetherContextResolver::CONTEXT_PRODUCT) {
            $this->resolvedProductId = $contextResolver->resolveProductId($this->productId);
            $this->productId = $this->resolvedProductId;

            if ($this->resolvedProductId) {
                $suggestedIds = $service->getSuggestedProductIds(
                    $this->resolvedProductId,
                    [],
                    $this->productsLimit
                );
            }
        } elseif ($this->context === PurchasedTogetherContextResolver::CONTEXT_CART) {
            $cartProductIds = $contextResolver->resolveCartProductIds($this->cartProductIds);

            if ($cartProductIds !== []) {
                $suggestedIds = $service->getSuggestedProductIds(
                    null,
                    $cartProductIds,
                    $this->productsLimit
                );
            }
        }

        $this->products = $service->loadProducts($suggestedIds);
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
                '1200' => ['items' => 4],
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

    protected function normalizeProductId(mixed $productId): ?int
    {
        if ($productId === null || $productId === '' || $productId === false) {
            return null;
        }

        $productId = (int) $productId;

        return $productId > 0 ? $productId : null;
    }

    protected function normalizeLayout(mixed $layout): string
    {
        $layout = strtolower(trim((string) ($layout ?: 'card')));

        return in_array($layout, ['card', 'minimal'], true) ? $layout : 'card';
    }
}
