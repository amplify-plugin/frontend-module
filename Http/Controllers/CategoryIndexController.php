<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Container\BindingResolutionException;

class CategoryIndexController extends Controller
{
    use HasDynamicPage;

    /**
     * Handle the incoming request.
     *
     * @throws \ErrorException
     * @throws BindingResolutionException
     */
    public function __invoke(?string $query = null)
    {

        abort_unless(! customer_check() || customer(true)->can('category.list'), 403);

        $this->loadPageByType('shop_category');

        store()->eaCategory;

        return $this->render();
    }
}
