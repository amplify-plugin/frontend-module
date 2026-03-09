<?php

namespace Amplify\Frontend\Components\Product;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Sayt\Classes\ItemRow;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class Name
 */
class Name extends BaseComponent
{
    public function __construct(public ItemRow|Product $product, public mixed $loop = null, public string $element = 'p', public int $maxLine = 1)
    {
        parent::__construct();
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
        $eaResult = store()->eaProductsData;

        $currentSeoPath = $eaResult?->getCurrentSeoPath() ?? '';

        $productName = ($this->product instanceof ItemRow)
            ? $this->product->Product_Name
            : $this->product->product_name;

        return view('widget::product.name', compact('productName', 'currentSeoPath'));
    }
}
