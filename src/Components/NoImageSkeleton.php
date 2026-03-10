<?php

namespace Amplify\Frontend\Components;

use Amplify\Frontend\Abstracts\BaseComponent;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class NoImageSkeleton
 */
class NoImageSkeleton extends BaseComponent
{
    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $filepath = public_path(config('amplify.frontend.fallback_image_path'));

        if (! file_exists($filepath)) {
            $filepath = public_path('assets/img/No-Image-Placeholder-min.png');
        }

        $imagesrc = 'data:image/png;base64, '.base64_encode(file_get_contents($filepath));

        return view('widget::no-image-skeleton', compact('imagesrc'));
    }
}
