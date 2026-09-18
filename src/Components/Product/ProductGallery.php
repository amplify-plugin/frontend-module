<?php

namespace Amplify\Frontend\Components\Product;

use Amplify\Frontend\Abstracts\BaseComponent;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Js;

/**
 * @class ProductGallery
 */
class ProductGallery extends BaseComponent
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public        $image,
        public        $erpAdditionalImages = [],
        public        $product = null,
        public string $thumbnailPosition = 'bottom'
    )
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
        $extraItemCount = count($this->image?->additional ?? []);

        return view('widget::product.product-gallery', [
            'productImage' => $this->image,
            'extraItemCount' => $extraItemCount,
            'additionalItems' => $this->image?->additional ?? [],
            'erpItems' => $this->erpAdditionalImages ?? [],
        ]);
    }

    public function thumbnailCarouselConfig()
    {
        return json_encode([
            'items' => 3,
            'nav' => true,
            'dots' => true,
            'loop' => false,
            'autoplay' => false,
            'margin' => 8,
            'autoWidth' => true,
            'autoHeight' => true,
        ]);

    }
}
