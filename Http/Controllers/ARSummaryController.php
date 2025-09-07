<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use App\Http\Controllers\Controller;

class ARSummaryController extends Controller
{
    use HasDynamicPage;

    public function __invoke()
    {
        abort_unless(customer(true)->can('account-summary.allow-account-summary'), 403);
        $this->loadPageByType('account_summary');

        return $this->render();
    }
}
