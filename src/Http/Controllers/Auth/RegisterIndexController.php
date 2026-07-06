<?php

namespace Amplify\Frontend\Http\Controllers\Auth;

use Amplify\Frontend\Traits\HasDynamicPage;
use Illuminate\Routing\Controller;


class RegisterIndexController extends Controller
{
    use HasDynamicPage;

    /**
     * Display the registration view.
     *
     * @throws \ErrorException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function __invoke(): string
    {
        $this->loadPageByType('registration');

        return $this->render();
    }
}
