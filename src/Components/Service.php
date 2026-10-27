<?php

namespace Amplify\Frontend\Components;

use Amplify\Frontend\Abstracts\BaseComponent;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class Service
 */
class Service extends BaseComponent
{
    /**
     * Create a new component instance.
     */
    public $serviceData;

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
        return view('widget::service');
    }
}
