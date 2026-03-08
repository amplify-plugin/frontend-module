<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\System\Support\Language;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;

class LocalizationController extends Controller
{
    private array $languages;

    public function __construct()
    {
        $this->languages = (new Language)->pluck('code')->toArray();
    }

    public function exportLocaleLang(): JsonResponse
    {
        // Checking is language available otherwise continue with fallback_local.
        $locale = in_array(Session::get('locale_lang'), $this->languages) ? Session::get('locale_lang') : config('app.fallback_locale');

        // Getting all language files under $local directory and get responses.
        $files = glob(resource_path('lang/'.$locale.'/*.php'));
        $strings = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');
            $strings[$name] = require $file;
        }

        $jsonStrings = (array) json_decode(@file_get_contents(resource_path('lang/'.$locale.'.json')), true);
        $strings = $strings + $jsonStrings;

        // Return localization response as json.
        return Response::json($strings, 200);
    }

    public function switchLanguage($locale): RedirectResponse
    {
        if (in_array($locale, $this->languages)) {
            Session::put('locale_lang', $locale);
            Session::save();
        }

        return back();
    }
}
