<?php

namespace Amplify\Frontend\Helpers;

use Amplify\System\Backend\Models\State;
use Amplify\System\Cms\Models\Page;
use Illuminate\Support\Facades\Route;

class CustomerHelper
{
    public static function afterLoggedRedirectTo($data = [])
    {
        $contact = customer(true);

        return match (true) {
            !empty($contact->redirect_route) && $contact->redirect_route !== 'previous-url'
            => url($contact->redirect_route),

            $contact->redirect_route === 'previous-url' && !empty($data['previous_url'])
            => $data['previous_url'],

            default
            => frontendHomeURL()
        };
    }

    public static function redirecteableUrls(): array
    {
        $urls = [];

        $excludedUrls = config('amplify.basic.excluded_redirect_urls', []);

        Page::published()->get()->each(function ($page) use (&$urls, $excludedUrls) {
            $pageType = collect(config('amplify.cms.page_types', []))
                ->firstWhere('code', $page->page_type);

            if ($pageType === null || $page->page_type === 'static_page') {
                return;
            }

            if (($pageType['url']['type'] ?? null) === 'route') {
                $route = Route::getRoutes()->getByName($pageType['url']['name']);

                if ($route === null) {
                    return;
                }

                // exclude page types that require a parameter in the uri
                foreach (get_uri_parameter($route->uri()) as $param) {
                    if (!str_contains($param, '?')) {
                        return;
                    }
                }
            }

            // exclude urls listed in the excluded_redirect_urls configuration
            foreach ($excludedUrls as $excludedUrl) {
                if (preg_match('/' . str_replace('/', '\/', $excludedUrl) . '/', $page->full_url)) {
                    return;
                }
            }

            $urls[$page->full_url] = $page->name;
        });

        ksort($urls);

        $urls['previous-url'] = 'Return to last page';

        return $urls;
    }

    // New Method to Fetch States by Country Code
    public static function fetchStates($countryCode)
    {
        return State::enabled()
            ->select('states.iso2', 'states.name', 'states.id')
            ->join('countries as c', function ($join) use ($countryCode) {
                return $join->on('states.country_id', '=', 'c.id')
                    ->where('c.iso2', '=', $countryCode);
            })->orderBy('states.name')
            ->get();
    }
}
