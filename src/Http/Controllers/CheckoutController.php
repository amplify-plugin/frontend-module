<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use App\Http\Controllers\Controller;

class CheckoutController extends Controller
{
    use HasDynamicPage;

    public function __construct()
    {
        if (! config('amplify.frontend.guest_checkout')) {
            $this->middleware('customers');
        }
    }

    /**
     * @throws \ErrorException
     */
    public function __invoke(): string
    {
        $this->loadPageByType('checkout');

        return $this->render();

    }
}
