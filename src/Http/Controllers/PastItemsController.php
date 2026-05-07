<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PastItemsController extends Controller
{
    use HasDynamicPage;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        hasAccessOrFail('past-items.list');

        $this->loadPageByType('past_items');

        return $this->render();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function history()
    {
        hasAccessOrFail('past-items.history');

        $this->loadPageByType('past_items_history');

        return $this->render();
    }
}
