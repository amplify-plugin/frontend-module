<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NoticeIndexController extends Controller
{
    use HasDynamicPage;

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $this->loadPageByType('notice');

        return $this->render();
    }
}
