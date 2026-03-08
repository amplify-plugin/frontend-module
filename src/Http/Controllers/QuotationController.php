<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use App\Http\Controllers\Controller;

class QuotationController extends Controller
{
    use HasDynamicPage;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->loadPageByType('quotation');

        if (! customer(true)->can('quote.view')) {
            abort(403);
        }

        return $this->render();
    }

    /**
     * Display the specified resource.
     *
     * @throws \ErrorException
     */
    public function show(string $id)
    {
        $this->loadPageByType('quotation_detail');

        if (! customer(true)->can('quote.view')) {
            abort(403);
        }

        store('quotationWrapper');

        return $this->render();
    }
}
