<?php

namespace Amplify\Frontend\Components;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\System\Backend\Models\Manufacturer;
use Amplify\System\Helpers\UtilityHelper;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * @class ManufactureSlider
 */
class ManufactureSlider extends BaseComponent
{
    public $show_title;

    private int $limit;

    public Collection $manufactureData;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public bool   $showTitle = true,
        public string $title = 'Manufacturers',
        public int    $items = 10,
        public bool   $showName = true,
        public bool   $showFeatured = true,
        public string $sortOrder = 'asc',
        public bool   $showNavigation = true,
        private int   $sliderItemGap = 15
    )
    {

        parent::__construct();

        $this->options = Config::get('amplify.widget.' . __CLASS__, []);
        $this->show_title = UtilityHelper::typeCast($showTitle, 'bool');
        $this->limit = UtilityHelper::typeCast($items, 'integer');
        $this->manufactureData = new Collection;

        $this->prepareData();
    }

    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        return !$this->manufactureData->isEmpty();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('widget::manufacture-slider', [
            'heading' => $this->title,
        ]);
    }

    private function prepareData(): void
    {
        $this->manufactureData = Cache::remember('site-manufacture-data', HOUR, function () {
            return Manufacturer::when($this->showFeatured, function ($query) {
                return $query->where('featured', $this->showFeatured);
            })->orderBy('created_at', $this->sortOrder)
                ->limit($this->limit)
                ->get()
                ->map(function ($item) {
                    $item->image = assets_image($item->image);
                    return $item;
                });
        });
    }

    public function carouselOptions(): string
    {
        return json_encode([
            'lazyLoad' => true,
            'animateIn' => 'fadeIn',
            'animateOut' => 'fadeOut',
            'dots' => false,
            'loop' > true,
            'autoplay' => true,
            'autoplayTimeout' => 4000,
            'nav' => $this->showNavigation,
            'margin' => $this->sliderItemGap,
            'responsive' => [
                '0' => ['items' => 2],
                '470' => ['items' => 3],
                '630' => ['items' => 4],
                '991' => ['items' => 5],
                '1200' => ['items' => 6],
            ],
        ]);
    }
}
