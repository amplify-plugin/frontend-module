<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use App\Http\Controllers\Controller;

class BrandIndexController extends Controller
{
    use HasDynamicPage;

    /**
     * Handle the incoming request.
     *
     * @throws \ErrorException
     */
    public function __invoke(?string $query = null)
    {
        abort_unless(! customer_check() || customer(true)->can('brand.list'), 403);

        $this->loadPageByType('brand');

        return $this->render();
    }
}
