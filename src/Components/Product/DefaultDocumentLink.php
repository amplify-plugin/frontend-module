<?php

namespace Amplify\Frontend\Components\Product;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\System\Backend\Models\DocumentType;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class DefaultDocumentLink
 */
class DefaultDocumentLink extends BaseComponent
{
    public function __construct(public ?DocumentType $document = null)
    {
        parent::__construct();
    }

    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        return $this->document instanceof DocumentType;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $filePath = $this->document->document_file_path ?? $this->document->file_path;

        return view('widget::product.default-document-link', compact('filePath'));
    }
}
