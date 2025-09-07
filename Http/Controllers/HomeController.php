<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    use HasDynamicPage;

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        abort_unless(! customer_check() || customer(true)->can('shop.browse'), 403);

        $this->loadPageByType('home');

        return $this->render();
    }
}
