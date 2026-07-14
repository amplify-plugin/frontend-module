<?php

namespace Amplify\Frontend\Components\Product\Comparison;

use Amplify\Frontend\Abstracts\BaseComponent;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class Index
 * @package Amplify\Frontend\Components\Product\Comparison
 *
 */
class Index extends BaseComponent
{
    public mixed $items;

    public function __construct()
    {
        $this->items = request()->session()->get('compareProducts');

        parent::__construct();
    }

    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        if (customer_check() && !customer()->can('product-compare.details')) {
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
