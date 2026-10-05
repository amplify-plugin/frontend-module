<?php

namespace Amplify\Frontend\Components\Product\Comparison;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\Frontend\Services\ProductCompareService;
use Amplify\System\Sayt\Classes\ItemRow;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class Button
 * @package Amplify\Frontend\Components\Product\Comparison
 */
class Manage extends BaseComponent
{
    public ?int $productId = null;

    public bool $selected = false;

    public function __construct(public mixed $product, public string $element = 'button')
    {
        parent::__construct();

        if (! $this->shouldRender()) {
            return;
        }

        $this->productId = $this->resolveProductId($product);

        if ($this->productId) {
            $this->selected = app(ProductCompareService::class)->contains($this->productId);
        }
    }

    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        if (! app(ProductCompareService::class)->enabled()) {
            return false;
        }

        if (customer_check() && ! customer(true)->can('product-compare.manage')) {
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
        if ($this->productId) {
            $this->attributes = $this->attributes->merge([
                'data-compare-product' => $this->productId,
                'data-compare-state' => $this->selected ? 'compared' : 'idle',
                'onclick' => "Amplify.compareProducts(this, {$this->productId}, 'add'); return false;",
            ]);
        }

        if ($this->selected) {
            $this->attributes = $this->attributes->class(['is-compared']);
        }

        return parent::htmlAttributes();
    }

    private function resolveProductId(mixed $product): ?int
    {
        $productId = $product instanceof ItemRow
            ? ($product->Product_Id ?: $product->Amplify_Id)
            : ($product->id ?? $product->Product_Id ?? null);

        return is_numeric($productId) ? (int) $productId : null;
    }
}
