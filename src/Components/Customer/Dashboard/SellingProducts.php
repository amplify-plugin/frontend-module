<?php

namespace Amplify\Frontend\Components\Customer\Dashboard;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\Frontend\Services\SellingProductsService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * @class SellingProducts
 */
class SellingProducts extends BaseComponent
{
    /**
     * @var array
     */
    public $options;

    public Collection $products;

    public string $dateRangeLabel = '';

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $title = 'Selling Products',
        public ?int $productsLimit = null,
    ) {
        parent::__construct();

        $this->products = collect();
        $this->prepareData();
    }

    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        $service = app(SellingProductsService::class);

        if (! $service->isEnabled()) {
            return false;
        }

        if (! customer_check()) {
            return false;
        }

        return customer(true)->can('dashboard.allow-dashboard');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('widget::customer.dashboard.selling-products');
    }

    public function prepareData(): void
    {
        $service = app(SellingProductsService::class);

        if (! $service->isEnabled() || ! customer_check()) {
            return;
        }

        $contact = customer(true);

        if (! $contact || ! $contact->can('dashboard.allow-dashboard')) {
            return;
        }

        $limit = $this->productsLimit ?: null;

        $this->dateRangeLabel = $service->dateRangeLabel();
        $this->products = $service->getTopSellingProducts(
            $contact->customer_id ? (int) $contact->customer_id : null,
            $limit
        );
    }
}
