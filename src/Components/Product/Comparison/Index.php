<?php

namespace Amplify\Frontend\Components\Product\Comparison;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\Frontend\Services\ProductCompareService;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class Index
 * @package Amplify\Frontend\Components\Product\Comparison
 */
class Index extends BaseComponent
{
    public array $products = [];

    public array $rows = [];

    public function __construct()
    {
        parent::__construct();

        if (! $this->shouldRender()) {
            return;
        }

        $comparison = app(ProductCompareService::class)->comparison();

        $this->products = $comparison['products'];
        $this->rows = $comparison['rows'];
    }

    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        if (! app(ProductCompareService::class)->enabled()) {
            return false;
        }

        if (customer_check() && ! customer(true)->can('product-compare.details')) {
            return false;
        }

        return true;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('widget::product.comparison.index');
    }
}
