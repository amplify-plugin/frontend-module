<?php

namespace Amplify\Frontend\Services;

use Amplify\System\Backend\Models\Cart;
use Amplify\System\Backend\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pipeline\Pipeline;

class PurchasedTogetherContextResolver
{
    public const CONTEXT_PRODUCT = 'product';

    public const CONTEXT_CART = 'cart';

    /**
     * Resolve whether suggestions should be based on the current product or cart items.
     */
    public function resolveContext(?int $explicitProductId = null, array $explicitCartProductIds = []): ?string
    {
        if ($explicitProductId) {
            return self::CONTEXT_PRODUCT;
        }

        if ($explicitCartProductIds !== []) {
            return self::CONTEXT_CART;
        }

        if ($this->isCartContext()) {
            return self::CONTEXT_CART;
        }

        if ($this->isProductDetailContext()) {
            return self::CONTEXT_PRODUCT;
        }

        return null;
    }

    public function resolveProductId(?int $explicitProductId = null): ?int
    {
        if ($explicitProductId) {
            return $explicitProductId;
        }

        if ($product = $this->getStoredProductModel()) {
            return (int) $product->id;
        }

        if ($productId = $this->resolveProductIdFromProductPaginate()) {
            return $productId;
        }

        if ($productId = $this->resolveProductIdFromRoute()) {
            return $productId;
        }

        if ($this->isProductDetailContext()) {
            $product = store()->productModel;

            return $product instanceof Product ? (int) $product->id : null;
        }

        return null;
    }

    /**
     * @return array<int>
     */
    public function resolveCartProductIds(array $explicitCartProductIds = []): array
    {
        if ($explicitCartProductIds !== []) {
            return array_values(array_unique(array_map('intval', $explicitCartProductIds)));
        }

        $cart = getCart();

        if (! $cart instanceof Cart) {
            return [];
        }

        $cart->loadMissing('cartItems');

        $productIds = $cart->cartItems
            ->pluck('product_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($productIds !== []) {
            return $productIds;
        }

        $productCodes = $cart->cartItems
            ->pluck('product_code')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($productCodes === []) {
            return [];
        }

        return Product::query()
            ->whereIn('product_code', $productCodes)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    protected function isProductDetailContext(): bool
    {
        if (request()->routeIs('frontend.shop.show')) {
            return true;
        }

        if ($this->getStoredProductModel()) {
            return true;
        }

        return $this->currentPageType() === 'single_product';
    }

    protected function isCartContext(): bool
    {
        if (request()->routeIs('frontend.carts.index', 'frontend.checkout')) {
            return true;
        }

        return in_array($this->currentPageType(), ['cart', 'cart_page', 'checkout'], true);
    }

    protected function currentPageType(): ?string
    {
        $attributes = store()->all();

        if (isset($attributes['dynamicPageModel']) && $attributes['dynamicPageModel']?->page_type) {
            return $attributes['dynamicPageModel']->page_type;
        }

        return store()->dynamicPageModel?->page_type;
    }

    protected function getStoredProductModel(): ?Product
    {
        $attributes = store()->all();

        if (array_key_exists('productModel', $attributes)) {
            $product = $attributes['productModel'];

            return $product instanceof Product ? $product : null;
        }

        if (! $this->isProductDetailContext()) {
            return null;
        }

        $product = store()->productModel;

        return $product instanceof Product ? $product : null;
    }

    protected function resolveProductIdFromRoute(): ?int
    {
        $parameter = request()->route('identifier');

        if ($parameter === null || $parameter === '') {
            return null;
        }

        if (config('amplify.frontend.easyask_single_product_index') === 'product_code') {
            $parameter = product_code_url_map($parameter, true);
        }

        $columns = array_values(array_unique(array_filter([
            config('amplify.frontend.easyask_single_product_index', 'id'),
            'id',
            'product_slug',
            'product_code',
        ])));

        foreach ($columns as $column) {
            $product = $this->findProductByColumn($column, $parameter);

            if ($product) {
                return (int) $product->id;
            }
        }

        return null;
    }

    protected function resolveProductIdFromProductPaginate(): ?int
    {
        $paginate = store()->all()['productPaginate'] ?? null;

        if ($paginate === null) {
            return null;
        }

        $first = collect($paginate)->first();

        if ($first instanceof Product) {
            return (int) $first->id;
        }

        if (is_object($first) && isset($first->Product_Id)) {
            return (int) $first->Product_Id;
        }

        return null;
    }

    protected function findProductByColumn(string $column, mixed $value): ?Product
    {
        $query = Product::query();

        return app(Pipeline::class)
            ->send($query)
            ->through(config('amplify.product_detail_pipeline', []))
            ->then(function (Builder $query) use ($column, $value) {
                return $query->where($column, $value)->first();
            });
    }
}
