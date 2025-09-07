<?php

namespace Amplify\Frontend\Traits\DynamicPageLoad;

use App\Models\Webinar;

trait EventTrait
{
    public function getEventDetailsData(&$data, $param)
    {
        $data['webinar'] = Webinar::with('webinarType')->findOrFail($param);
    }
}
