<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use HasDynamicPage;

    /**
     * Handle the incoming request.
     *
     * @throws \ErrorException
     */
    public function __invoke(Request $request): string
    {
        $this->loadPageByType('dashboard');
        if (! customer(true)->can('dashboard.allow-dashboard')) {
            abort(403);
        }

        return $this->render();

    }
}
