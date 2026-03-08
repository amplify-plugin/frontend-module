<?php

namespace Amplify\Frontend\Components\Product;

use Amplify\Frontend\Abstracts\BaseComponent;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * @class ProductSkuTable
 */
class ProductSkuTable extends BaseComponent
{
    private $Product;

    private Component $component;

    /**
     * Create a new component instance.
     */
    public function __construct(
        $product,
        public bool $qtyConfig = true,
        public bool $isFavButtonShow = false,
        public bool $displayProductCode = false,
        public ?string $seoPath = ''
    ) {
        parent::__construct();
        $this->Product = $product;
    }

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
    public function render(): View|Closure|string
    {
        $class = match (config('amplify.client_code')) {
            'RHS' => \Amplify\Frontend\Components\Client\Rhsparts\Product\ProductSkuTable::class,
            'ACT' => \Amplify\Frontend\Components\Client\CalTool\Product\ProductSkuTable::class,
            default => \Amplify\Frontend\Components\Client\Demo\Product\ProductSkuTable::class,
        };

        $this->component = new $class(
            $this->Product,
            $this->qtyConfig,
            $this->seoPath
        );
        $this->component->attributes = $this->attributes;

        return $this->component->render();
    }

    public function allowDisplayProductCode(): bool
    {
        return (bool) $this->displayProductCode;
    }

    public function allowFavourites(): bool
    {
        return (bool) $this->isFavButtonShow;
    }

    public function allowAddToCart()
    {
        return config('amplify.frontend.guest_checkout') || customer_check() || customer(true)?->can('shop.add-to-cart');
    }

    public function isShowMultipleWarehouse(): bool
    {
        return erp()->allowMultiWarehouse() && havePermissions(['checkout.choose-warehouse']);
    }
}
