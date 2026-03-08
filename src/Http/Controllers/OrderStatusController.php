<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderStatusController extends Controller
{
    use HasDynamicPage;

    /**
     * Handle the incoming request.
     *
     * @throws \ErrorException
     */
    public function __invoke(Request $request): string
    {
        $this->loadPageByType('order_status');
        // if(!customer(true)->can('authority.allow-dashboard')){
        //     abort(403);
        // }

        return $this->render();

    }
}
