<?php

namespace Amplify\Frontend\Components\Auth;

use Amplify\Frontend\Abstracts\BaseComponent;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class Registration
 */
class Registration extends BaseComponent
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        private string $title = '',
        private string $subtitle = '',
        public string $headerClass = 'nav nav-tabs nav-fill registration-tabs',

    ) {
        parent::__construct();

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
        return view('widget::auth.registration');
    }

    public function displayableTitle()
    {
        if ($this->title == '') {
            $titleAttribute = collect($this->options['@attributes'])->firstWhere('name', '=', 'title');

            return $titleAttribute['value'];
        }

        return trans($this->title);
    }

    public function displayableSubTitle()
    {
        if ($this->subtitle == '') {
            $titleAttribute = collect($this->options['@attributes'])->firstWhere('name', '=', 'subtitle');

            return $titleAttribute['value'];
        }

        return trans($this->subtitle);
    }

    public function registerButtonTitle()
    {
        if ($this->buttonTitle == '') {
            $titleAttribute = collect($this->options['@attributes'])->firstWhere('name', '=', 'button-title');

            return $titleAttribute['value'];
        }

        return trans($this->buttonTitle);
    }

}
