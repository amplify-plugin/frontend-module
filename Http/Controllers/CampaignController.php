<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Marketing\Models\Campaign;
use App\Http\Controllers\Controller;

class CampaignController extends Controller
{
    use HasDynamicPage;

    /**
     * @throws \ErrorException
     */
    public function index()
    {
        $this->loadPageByType('campaign');

        return $this->render();
    }

    public function show(string $campaign_code)
    {
        $campaign = store()->campaign = Campaign::with(['page', 'bannerZone'])->where('slug', $campaign_code)->firstOrFail();

        if ($campaign->login_required && ! customer_check()) {
            return redirect()->route('frontend.login');
        }

        if ($campaign->page) {

            store()->dynamicPageModel = $campaign->page;

        } else {
            $this->loadPageByType('campaign_details');
        }

        return $this->render();
    }
}
