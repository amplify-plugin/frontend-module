<?php

namespace Amplify\Frontend\Components\Notice;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\System\Backend\Models\Notice;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @class Index
 */
class Index extends BaseComponent
{
    /**
     * @var LengthAwarePaginator
     */
    public $notices;

    public function __construct()
    {
        parent::__construct();

        $this->notices = Notice::where('enabled', true)
            ->when(request()->filled('id'), function ($query) {
                return $query->where('id', request('id'));
            })->whereDate('started_at', '<=', now())
            ->whereDate('ended_at', '>=', now())
            ->paginate(15);
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
        return view('widget::notice.index');
    }
}
