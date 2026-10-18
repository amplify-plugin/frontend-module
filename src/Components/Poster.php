<?php

namespace Amplify\Frontend\Components;

use Amplify\Frontend\Abstracts\BaseComponent;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class Poster
 */
class Poster extends BaseComponent
{
    public mixed $bannerDetails;

    /**
     * Create a new component instance.
     */
    public function __construct(public string $zone, public mixed $code)
    {
        parent::__construct();

        $this->prepareData();
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
        return view('widget::poster');
    }

    private function prepareData(): void
    {
        $remoteResults = store()->eaProductsData;

        if (!$this->code && $remoteResults->hasDisplayBanner($this->zone)) {

            $banners = $remoteResults->getDisplayBanner($this->zone);

            if (!empty($banners)) {
                $this->bannerDetails = $banners[array_key_first($banners)] ?? null;
            }
        }
    }
}
