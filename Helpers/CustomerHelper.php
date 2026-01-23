<?php

namespace Amplify\Frontend\Helpers;

use Amplify\System\Backend\Models\State;
use Illuminate\Support\Facades\Route;

class CustomerHelper
{
    public static function afterLoggedRedirectTo($data = [])
    {
        $contact = customer(true);

        if (! empty($data['previous_url']) && ! str_contains($data['previous_url'], 'admin')) {
            return $data['previous_url'];
        }

        if (! empty($contact->redirect_route)) {
            return url($contact->redirect_route);
        }


        return route('frontend.dashboard');
    }

    public static function redirecteableUrls()
    {
        $urls = [];

        $routes = Route::getRoutes()->getRoutesByMethod()['GET'];

        $exclude_urls = config('amplify.basic.excluded_redirect_urls', []);

        foreach ($routes as $route) {
            if (str_starts_with($route->getName(), 'frontend') && ! str_contains($route->uri(), '{') && ! in_array($route->uri(), $exclude_urls)) {
                $urls[$route->uri()] = ($route->uri() == '/') ? 'Home' : ucwords(str_replace(['/', '-'], [' ', ' '], $route->uri()));
            }
        }

        ksort($urls);

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
