<?php

namespace Amplify\Frontend\Components\Product;

use Amplify\Frontend\Abstracts\BaseComponent;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class ProductGallery
 */
class ProductGallery extends BaseComponent
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public $image,
        public $erpAdditionalImages = [],
        public $product = null,
    ) {
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
        return view('widget::product.product-gallery', [
            'productImage' => $this->image,
            'erpAdditionalImages' => $this->erpAdditionalImages,
        ]);
    }
}
