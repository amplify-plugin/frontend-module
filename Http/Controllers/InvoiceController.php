<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\ErpApi\ErpApiService;
use Amplify\Frontend\Traits\HasDynamicPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use HasDynamicPage;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->loadPageByType('invoice');
        if (! customer(true)->can('invoices.view')) {
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
        $this->loadPageByType('invoice_detail');
        if (! customer(true)->can('invoices.view')) {
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
     * Download the specified resource.
     */
    public function download(string $type, string $id)
    {
        $documentType = match ($type) {
            'P' => ErpApiService::DOC_TYPE_SHIP_SIGN,
            'R' => ErpApiService::DOC_TYPE_RENTAL_INVOICE,
            default => ErpApiService::DOC_TYPE_INVOICE
        };
        try {
            $invoice = \ErpApi::getDocument(['document_number' => $id, 'document_type' => $documentType]);
            $serial = preg_replace('/(.+)-.{1}/', '$1', $id);
            if (empty($invoice->File) || ! is_file($invoice->File)) {
                throw new \Exception('Invoice not found');
            }

            return response()->download($invoice->File, "INV-{$serial}.pdf", ['Content-Type' => 'application/pdf']);
        } catch (\Exception $exception) {
            return back()->with('error', "Pdf not avaiable for {$id}");
        }
    }

    /**
     * Download the specified resource.
     */
    public function trackInvoice(string $invoice)
    {
        if (empty($invoice)) {
            return back()->with('error', 'Invoice number is required');
        }

        $errormessage = "Tracking number for {$invoice} not found";

        try {
            $trackingData = \ErpApi::getTrackShipment(['invoice_number' => $invoice]);

            if (empty($trackingData->TrackHref)) {
                return back()->with('error', $errormessage);
            }

            return redirect($trackingData->TrackHref);
        } catch (\Exception $exception) {
            return back()->with('error', $errormessage);
        }
    }
}
