<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Models\Webinar;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EventController extends Controller
{
    use HasDynamicPage;

    /**
     * @throws \ErrorException
     */
    public function index(Request $request): string
    {
        $this->loadPageByType('event');
        push_css('css/widget/event-list.css', 'custom-style');

        store()->webinarPaginate = Webinar::fetchWebinarPagination($request->all());

        return $this->render();
    }

    /**
     * @throws \ErrorException
     */
    public function show(string $event_code): string
    {
        $webinar = store()->webinar = Webinar::with(['page', 'bannerZone'])->where('slug', $event_code)->firstOrFail();
        push_css('css/widget/event-list.css', 'custom-style');

        if ($webinar->page) {

            store()->dynamicPageModel = $webinar->page;

        } else {
            $this->loadPageByType('event_details');
        }

        return $this->render();
    }
}
