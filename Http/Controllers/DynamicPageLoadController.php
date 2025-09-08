<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\System\Backend\Models\Site;
use Amplify\System\Cms\Models\Page;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class DynamicPageLoadController extends Controller
{
    use \Amplify\Frontend\Traits\HasDynamicPage;

    private ?Page $page = null;

    private ?Site $site = null;

    public function __construct(Request $request)
    {
        if (! App::runningInConsole()) {
            $this->handleDynamicMiddleware($request); // Add dynamic middleware for dynamic-page route.
            if (! $this->page) {
                abort(404, 'Page not found!');
            }

            $request->merge(['DynamicPageModel' => $this->page]);
        }
    }

    /**
     * @throws BindingResolutionException
     * @throws \ErrorException
     */
    public function __invoke(?string $slug = null): string
    {
        if (! $this->page) {
            abort(404, 'Page Not Found');
        }

        store()->dynamicPageModel = $this->page;

        if ($this->site) {
            setDynamicSiteSlugToCache($slug);
            setSiteSearchConfigToCache($this->site->search_configuration);
        } else {
            setDynamicSiteSlugToCache(null);
            setSiteSearchConfigToCache([], true);
        }

        return $this->render();
    }

    private function handleDynamicMiddleware(Request $request): void
    {
        if (optional($request->route())->getName() === 'dynamic-page') {
            $siteUrl = "{$request->getSchemeAndHttpHost()}/{$request->route()->slug}";
            $this->site = Site::where([['url', '=', $siteUrl]])->first();

            if ($this->site) {
                /* Rendering page content */
                if (empty($request->route()->param1) && empty($request->route()->param2)) {
                    $this->page = Page::find($this->site->front_page_id); // Get home page.
                } elseif (! empty($request->route()->param1) && empty($request->route()->param2)) {
                    if ($request->route()->param1 === 'shop') {
                        $this->page =
                            Page::find($this->site->shop_page_id);
                    } // Get shop page.
                    else {
                        $this->page =
                            Page::published()->where([
                                'template_id' => $this->site->template_id, 'slug' => $request->route()->param1,
                            ])->first();
                    } // Get other page.
                } elseif (! empty($request->route()->param1) && ! empty($request->route()->param2)) {
                    /* Get single product page */
                    if ($request->has('has_sku')) {
                        if ($request->has_sku == true) {
                            Session::put('has_sku', true);
                        }
                        if (
                            $request->has('seopath')
                            && ! empty($request->seopath)
                        ) {
                            Session::put('seopath', $request->seopath);
                        }
                    }
                    $this->page = Page::published()->find($this->site->product_page_id);
                }
            } else {
                $this->page = Page::published()->where('slug', $request->route()->slug)->first();
            }
        } else {
            try {
                $this->generatePageInstance($request->route());
            } catch (\Throwable $th) {
                // throw $th;
            }
        }

        if ($this->page) {
            $this->middleware($this->page->middleware);
        }
    }

    private function generatePageInstance(Route $route): void
    {
        $this->page = match ($route->getName()) {
            'frontend.index', 'frontend.home' => Page::published()->find(config('amplify.frontend.home_id')),
            'frontend.shop' => Page::published()->find(config('amplify.frontend.shop_id')),
            'frontend.order_list.details', 'frontend.orders.show' => Page::published()->find(config('amplify.frontend.order_detail_id')),
            'frontend.quotation.details' => Page::published()->find(config('amplify.frontend.quotation_detail_id')),
            'frontend.draft-order.details' => Page::published()->find(config('amplify.frontend.draft_order_detail_id')),
            'frontend.orders' => Page::published()->find(config('amplify.frontend.order_id')),
            'frontend.draft-orders' => Page::published()->find(config('amplify.frontend.draft_order_id')),
            'frontend.quotations' => Page::published()->find(config('amplify.frontend.quotation_id')),
            'frontend.customer_lists' => Page::published()->find(config('amplify.frontend.favourite_id')),
            'frontend.customer_lists.details' => Page::published()->find(config('amplify.frontend.favourite_detail_id')),
            'frontend.customer_login' => Page::published()->find(config('amplify.frontend.login_id')),
            'frontend.customer_registration' => Page::published()->find(config('amplify.frontend.registration_id')),
            'frontend.customer_forgot_password' => Page::published()->find(config('amplify.frontend.forgot_password_id')),
            'frontend.invoice.details' => Page::published()->find(config('amplify.frontend.invoice_detail_id')),
            'frontend.order-rule' => Page::find(config('amplify.frontend.order_rule_id')),
            'frontend.singleProduct' => Page::published()->find(config('amplify.frontend.single_product_id')),
            default => Page::published()->where('slug', $route->slug)->first(),
        };
    }
}
