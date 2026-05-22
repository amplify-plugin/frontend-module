<?php

namespace Amplify\Frontend\Components;

use Amplify\Frontend\Abstracts\BaseComponent;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class DataTableWrapper
 */
class DataTableWrapper extends BaseComponent
{
    /**
     * Create a new component instance.
     */
    public function __construct(public ?string $id = null, public array $dataTableOptions = [])
    {
        parent::__construct();

    }

    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        return true;
    }

    private function mergeDefaultDtOptions(): void
    {
        $this->dataTableOptions = array_merge([
            'searching' => true,
            'language' => [
                'search' => '_INPUT_',
                'searchPlaceholder' => 'Search...',
            ],
            'lengthMenu' => getPaginationLengths(),
            'order' => [[0, 'desc']]
        ], $this->dataTableOptions);

        if (empty($this->dataTableOptions['dom'])) {
            $this->dataTableOptions['dom'] = (isset($this->dataTableOptions['searching']) && $this->dataTableOptions['searching'])
                ? '<f><"row"<"col-sm-12"tr>><"row mt-2"<"col-sm-12 col-md-5"l><"col-sm-12 col-md-7"p>>'
                : '<"row"<"col-sm-12"tr>><"row mt-2"<"col-sm-12 col-md-5"l><"col-sm-12 col-md-7"p>>';
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $this->mergeDefaultDtOptions();

        return view('widget::data-table-wrapper');
    }
}
