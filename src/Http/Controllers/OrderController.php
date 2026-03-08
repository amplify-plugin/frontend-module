<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use HasDynamicPage;

    /**
     * Display a listing of the resource.
     *
     * @throws \ErrorException
     */
    public function index(): string
    {
        $this->loadPageByType('order');
        if (! customer(true)->can('order.view')) {
            abort(403);
        }

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
        $this->loadPageByType('order_detail');
        if (! customer(true)->can('order.view')) {
            abort(403);
        }

        return $this->render();
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

    /**
     * Approver contact approves an order
     */
    public function approve()
    {
        //
    }

    /**
     * Completed an order page
     */
    public function completed()
    {
        $this->loadPageByType('order_completed');

        return $this->render();
    }
}
