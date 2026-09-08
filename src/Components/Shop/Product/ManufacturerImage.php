<?php

namespace Amplify\Frontend\Components\Shop\Product;

use Amplify\Frontend\Abstracts\BaseComponent;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class ManufacturerImage
 */
class ManufacturerImage extends BaseComponent
{
    /**
     * Create a new component instance.
     */
    public function __construct(public mixed  $product,
                                public string $attribute = 'Manufacturer')
    {
        parent::__construct();
    }

    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        return ! empty($this->product->manufacturer);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('widget::product.manufacturer-image');
    }

    public function htmlAttributes(): string
    {
        $this->attributes = $this->attributes->merge([
            'href' => frontendShopURL("{$this->attribute}:{$this->product?->manufacturer?->code}"),
            'title' => $this->product->manufacturer->name,
            'class' => 'text-decoration-none'
        ]);

        return parent::htmlAttributes();
    }
}
