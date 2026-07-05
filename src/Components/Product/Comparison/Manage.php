<?php

namespace Amplify\Frontend\Components\Product\Comparison;

use Amplify\System\Sayt\Classes\ItemRow;
use Closure;
use Illuminate\Contracts\View\View;
use Amplify\Frontend\Abstracts\BaseComponent;

/**
 * @class Button
 * @package Amplify\Frontend\Components\Product\Comparison
 *
 */
class Manage extends BaseComponent
{
    public function __construct(public mixed  $product,
                                public string $element = 'button')
    {
        parent::__construct();
    }

    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        if (customer_check() && !customer()->can('product-compare.manage')) {
            return false;
        }

        return true;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('widget::product.comparison.manage');
    }

    public function htmlAttributes(): string
    {
        $productId = $this->product instanceof ItemRow ? $this->product->Amplify_Id : $this->product->id;

        $this->attributes = $this->attributes->merge(['onclick' => "Amplify.compareProducts(this, {$productId}, 'add');"]);

        return parent::htmlAttributes();
    }
}
