<?php

namespace Amplify\Frontend\Components\Product\Comparison;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\Frontend\Services\ProductCompareService;
use Closure;
use Illuminate\Contracts\View\View;

class Bar extends BaseComponent
{
    public array $items = [];

    public int $max = 4;

    public string $compareUrl = '#';

    public function shouldRender(): bool
    {
        return app(ProductCompareService::class)->availableFor('product-compare.manage');
    }

    public function render(): View|Closure|string
    {

        $compare = app(ProductCompareService::class);

        $this->items = $compare->summaries();
        $this->max = $compare->max();
        $this->compareUrl = $compare->pageUrl();

        return view('widget::product.comparison.bar');
    }
}
