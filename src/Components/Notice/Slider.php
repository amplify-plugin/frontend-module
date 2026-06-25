<?php

namespace Amplify\Frontend\Components\Notice;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\System\Backend\Models\Notice;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * @class Slider
 */
class Slider extends BaseComponent
{
    /**
     * @var Collection
     */
    public $notices;

    public string $id;
    public function __construct()
    {
        parent::__construct();

        $this->notices = Notice::whereDate('started_at', '<=', now())
            ->whereDate('ended_at', '>=', now())
            ->where('enabled', true)
            ->get();

        $this->id = Str::uuid()->toString();
    }

    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        return $this->notices->isNotEmpty();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('widget::notice.slider');
    }
}
