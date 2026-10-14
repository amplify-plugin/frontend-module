<?php

namespace Amplify\Frontend\Components;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\System\Backend\Models\Category;
use Amplify\System\Cms\Models\Page;
use Amplify\System\Sayt\Classes\BreadCrumbTrail;
use Closure;
use ErrorException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * @class Breadcrumb
 */
class Breadcrumb extends BaseComponent
{
    public Collection $breadcrumbs;

    protected ?Page $page;

    protected mixed $navigateNode;

    /**
     * Create a new component instance.
     *
     * @throws ErrorException
     */
    public function __construct(public string $title = '', public bool $error = false, public bool $hideTitle = false)
    {
        parent::__construct();

        $this->breadcrumbs = new Collection;

        $this->navigateNode = null;

        $this->page = store('dynamicPageModel');
    }

    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        if ($this->error) {
            return true;
        }

        if ($this->page instanceof Page) {
            return (bool)$this->page->has_breadcrumb;
        }

        return true;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @throws ErrorException
     */
    public function render(): View|Closure|string
    {

        if ($this->error) {
            $this->push('Error ' . $this->title, url()->current());
            $this->push('Home', $this->homeUrl());
        } else {
            match ($this->page->page_type) {
                'shop' => $this->loadShopBreadcrumbs(),
                'single_product' => $this->loadProductBreadcrumbs(),
                default => $this->loadDynamicPageBreadcrumbs($this->page)
            };
        }

        $this->breadcrumbs = $this->breadcrumbs->reverse();

        return view('widget::breadcrumb', [
            'pageTitle' => $this->title,
        ]);
    }

    protected function push(string $title, string $url): void
    {
        $node = new \stdClass;

        $node->title = $title;
        $node->url = $url;

        $this->breadcrumbs->push($node);
    }

    /**
     * @return string
     */
    public function homeUrl()
    {
        return getIsDynamicSiteFromCache()
            ? request()->getSchemeAndHttpHost() . '/' . getDynamicSiteSlugFromCache()
            : config('amplify.cms.brand_logo_url', route('frontend.index'));
    }

    protected function loadDynamicPageBreadcrumbs(?Page $page = null): void
    {
        $this->push(($page->breadcrumb_title != null ? $page->breadcrumb_title : $page->name), url($page->slug));

        if (!empty($page->parent)) {
            $this->loadDynamicPageBreadcrumbs($page->parent);
        }
    }

    /**
     * Return The Displayable Page Tile
     *
     * @return string
     *
     * @throws ErrorException
     */
    public function displayPageTitle()
    {
        if ($this->error) {
            return $this->errorTitle();
        }

        if (strlen($this->title) > 0) {
            return $this->title;
        }

        if ($this->navigateNode != null) {
            if ($this->page->page_type == 'shop') {
                return $this->navigateNode->getLabel() . ' <strong><em>\'' . ucwords($this->navigateNode->getEnglishName()) . '\'</em></strong>';
            } else {
                return store('pageTitle', 'Product Detail');
            }
        }

        return $this->breadcrumbs->last()?->title ?? 'No Name';
    }

    protected function errorTitle(): string
    {
        return match ($this->title) {
            '400' => 'Bad Request',
            '401' => 'Unauthorized',
            '402' => 'Payment Required',
            '403' => 'Access Forbidden',
            '404' => 'Not Found',
            '405' => 'Method Not Allowed',
            '408' => 'Request Time-out',
            '419' => 'Page Expired',
            '429' => 'Too Many Requests',
            '500' => 'Internal Server Error',
            '503' => 'Service Unavailable',
            default => "Error {$this->title}"
        };
    }

    /**
     * @throws ErrorException
     */
    protected function loadShopBreadcrumbs(): void
    {
        $easyAskResult = store()->eaProductsData;

        $breadcrumbTrails = $easyAskResult?->getBreadCrumbTrail() ?? new BreadCrumbTrail(null);

        $navigateNodes = $breadcrumbTrails->getSearchPath();

        $this->processEANodes($navigateNodes);
    }

    /**
     * @throws ErrorException
     */
    protected function loadProductBreadcrumbs(): void
    {
        $easyAskResult = store()->eaProductDetail;

        $breadcrumbTrails = $easyAskResult?->getBreadCrumbTrail() ?? new BreadCrumbTrail(null);

        $rootCatePath = \Sayt::getDefaultCatPath();

        [$first, $second] = explode('/', $rootCatePath, 2);

        $categories = Category::categoryTree($easyAskResult->getSuggestedCategoryID());

        $categories->shift();

        $trails = [
            [
                "value" => "All Products",
                "path" => "All Products",
                "purePath" => "All Products",
                "seoPath" => "",
                "navNodePathType" => 1,
                "englishName" => "All Products",
            ],
            [
                "value" => $first,
                "path" => "All Products////{$first}",
                "purePath" => "All Products////{$first}",
                "seoPath" => $first,
                "navNodePathType" => 1,
                "englishName" => $first,
            ],
            [
                "value" => $second,
                "path" => "All Products////{$first}////{$second}",
                "purePath" => "All Products////{$first}////{$second}",
                "seoPath" => "{$first}/{$second}",
                "navNodePathType" => 1,
                "englishName" => $second,
            ],
        ];

        $path = "All Products////{$first}////{$second}";
        $seo = "{$first}/{$second}";

        foreach ($categories as $category) {
            $sluggable = $category->sluggable();
            $method = $sluggable['category_slug']['method'];
            $path = "{$path}////{$category->category_name}";
            $seo = "{$seo}/" . $method($category->category_name, '-');

            $trails[] = [
                "value" => $category->category_name,
                "path" => $path,
                "purePath" => $path,
                "seoPath" => $seo,
                "navNodePathType" => 1,
                "englishName" => $category->category_name,
            ];
        }

        $categoryBreadcrumbTrail = new BreadCrumbTrail(json_decode(json_encode([
            "fullPath" => $breadcrumbTrails->getFullPath(),
            "pureCategoryPath" => $breadcrumbTrails->getPureCategoryPath(),
            "navPathNodeList" => $trails,
        ])));

        $this->processEANodes($categoryBreadcrumbTrail->getSearchPath());
    }

    protected function processEANodes(array $navigateNodes = []): void
    {
        foreach (array_reverse($navigateNodes) as $breadcrumb) {
            $this->push($breadcrumb->getLabel(), frontendShopURL([
                $breadcrumb->getSEOPath(),
                'view' => active_shop_view(),
                'per_page' => results_per_page(),
                'sort_by' => request('sort_by', ''),
            ]));
        }

        $this->push('Home', $this->homeUrl());
    }

    public function htmlAttributes(): string
    {
        $this->attributes = $this->attributes->class('page-title');

        return parent::htmlAttributes();
    }
}
