<?php

namespace Amplify\Frontend\Components;

use Amplify\Frontend\Abstracts\BaseComponent;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class Checkout
 */
class Checkout extends BaseComponent
{
    public function __construct(public bool $createFavouriteFromCart = true,
        public bool $allowRequestQuote = true,
        public bool $allowDraftOrder = false,
        public string $backToUrl = 'home'
    ) {
        parent::__construct();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        //        $class = match (config('amplify.client_code')) {
        //            'ACT' => \Amplify\Frontend\Components\Client\CalTool\Checkout::class,
        //            'RHS' => \Amplify\Frontend\Components\Client\Rhsparts\Checkout::class,
        //            default => \Amplify\Frontend\Components\Checkout::class,
        //        };
        //
        //        $this->component = new $class;
        //
        //        $this->component->attributes = $this->attributes;

        return view('widget::checkout');
    }

    public function backToShoppingUrl(): string
    {
        if ($this->backToUrl == 'home') {
            return frontendHomeURL();
        }

        return frontendShopURL();
    }
}
