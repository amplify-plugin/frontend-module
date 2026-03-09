<?php

namespace Amplify\Frontend\Components\Customer\Order\Rules;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\System\OrderRule\Models\CustomerOrderRule;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Config;

/**
 * @class Index
 */
class Index extends BaseComponent
{
    /**
     * @var array
     */
    public $options;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->options = Config::get('amplify.widget.'.__CLASS__, []);

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
        $order_rule = CustomerOrderRule::with('orderRule')
            ->where('customer_id', customer()->id)
            ->orderBy('id', 'DESC')->get();

        return view('widget::customer.order.rules.index', [
            'order_rule' => $order_rule,
        ]);
    }
}
