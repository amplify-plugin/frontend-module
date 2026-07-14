<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class HomeController extends Controller
{
    use HasDynamicPage;

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $this->loadPageByType('home');

        return $this->render();
    }
}
