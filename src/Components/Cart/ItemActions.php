<?php

namespace Amplify\Frontend\Components\Cart;

use Amplify\Frontend\Abstracts\BaseComponent;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class ItemActions
 */
class ItemActions extends BaseComponent
{
    public function __construct(public string $updateStyle = 'line')
    {
        parent::__construct();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('widget::cart.item-actions');
    }
}
