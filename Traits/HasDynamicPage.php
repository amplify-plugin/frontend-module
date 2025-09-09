<?php

namespace Amplify\Frontend\Traits;

use Amplify\System\Cms\Models\Page;
use Amplify\System\Facades\AssetsFacade;
use Amplify\System\Support\AssetsLoader;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\View\View;

trait HasDynamicPage
{
    /**
     * This program will set the dynamic page configured
     * to display on this request
     * N.B: For page id/type declaration
     *
     * @param  null  $id
     *
     * @throws \ErrorException
     *
     * @see plugins/Cms/Config/cms.php
     */
    public function loadPageById($id = null): void
    {
        if ($id == null) {
            abort('500', 'Page Id is missing or does not exists.');
        }

        $page = Page::published()->find($id);

        if (! $page) {
            abort(404, 'Page Not Found');
        }

        store()->dynamicPageModel = $page;
    }

    /**
     * This program will set the dynamic page configured
     * to display on this request
     * N.B: For page type declaration
     *
     * @param  null  $type
     *
     * @throws \ErrorException
     *
     * @see plugins/Cms/Config/cms.php
     */
    public function loadPageByType($type = null): void
    {
        if ($type == null) {
            abort('500', 'Page Type is missing or does not exists.');
        }

        $page = Page::published()->wherePageType($type)->first();

        if (! $page) {
            abort(404, 'Page Not Found');
        }

        store()->dynamicPageModel = $page;
    }

    /**
     * This program will set the dynamic page guessed
     * from slug to display on this request
     * N.B: For page type declaration
     *
     * @param  string|null  $slug
     *
     * @throws \ErrorException
     *
     * @see plugins/Cms/Config/cms.php
     * @see plugins/Frontend/Config/frontend.php
     */
    public function loadPageBySlug($slug = null): void
    {

        if ($slug == null) {
            abort('500', 'Page Slug is missing or does not exists.');
        }

        $page = Page::published()->whereSlug($slug)->first();

        if (! $page) {
            abort(404, 'Page Not Found');
        }

        if ($page->page_type != 'static_page') {
            abort(403, 'Reserved Page cannot be accessed directly.');
        }

        store()->dynamicPageModel = $page;
    }

    /**
     * @throws BindingResolutionException
     * @throws \ErrorException
     */
    public function render(): string
    {
        $this->preloadDefaultAssets();

        $page = store('dynamicPageModel');

        push_css($page->styles ?? '', 'internal-style');

        $component = new class($this->wrapPageContent($page->content ?? '')) extends \Illuminate\View\Component
        {
            protected $template;

            public function __construct($template)
            {
                $this->template = $template;
            }

            public function render()
            {
                return $this->template;
            }
        };

        $view = Container::getInstance()
            ->make(ViewFactory::class)
            ->make($component->resolveView(), [
                'meta_data' => $page->meta_tags ?? [],
            ]);

        $this->injectAllAssets($view);

        return $view->render();

        //        return tap($view->render(), function () use ($view) {
        //            @unlink($view->getPath());
        //        });
    }

    private function injectAllAssets(View &$view): void
    {
        $assets = AssetsFacade::collection();

        foreach (array_keys($assets[AssetsLoader::TYPE_CSS]) as $group) {
            $this->pushToStack($view, AssetsLoader::TYPE_CSS, $group);
        }

        foreach (array_keys($assets[AssetsLoader::TYPE_JS]) as $group) {
            $this->pushToStack($view, AssetsLoader::TYPE_JS, $group);
        }

        foreach (array_keys($assets[AssetsLoader::TYPE_HTML]) as $group) {
            $this->pushToStack($view, AssetsLoader::TYPE_HTML, $group);
        }

    }

    private function pushToStack(View &$view, string $prefix, string $group): void
    {
        $name = ($group == AssetsLoader::DEFAULT_GROUP) ? "{$prefix}-{$group}" : $group;
        $view->getFactory()->startPush($name, trim(AssetsFacade::{$prefix}($group)).PHP_EOL);
        if (! empty($content)) {
            $view->getFactory()->stopPush();
        }
    }

    private function preloadDefaultAssets(): void
    {
        foreach (config('amplify.frontend.styles', []) as $group => $styles) {
            foreach ($styles ?? [] as $style) {
                push_css($style, $group);
            }
        }

        push_css([
            theme_asset('css/vendor.min.css'),
            theme_asset('css/styles.min.css'),
        ], 'template-style');

        push_css([
            theme_asset('css/custom.css'),
        ], 'custom-style');

        foreach (config('amplify.frontend.scripts', []) as $group => $scripts) {
            foreach ($scripts ?? [] as $script) {
                push_js($script, $group);
            }
        }

        push_js([
            theme_asset('js/vendor.min.js'),
        ], 'plugin-script');

        push_js([
            theme_asset('js/scripts.min.js'),
        ], 'template-script');

        push_js([
            theme_asset('js/custom.js'),
        ], 'custom-script');
    }

    private function wrapPageContent(string $content): string
    {
        $layout = theme_view('index');

        return <<<HTML
@extends('{$layout}')
@section('content')
    {$content}
@endsection
HTML;

    }
}
