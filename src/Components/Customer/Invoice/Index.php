<?php

namespace Amplify\Frontend\Components\Customer\Invoice;

use Amplify\ErpApi\ErpApiService;
use Amplify\ErpApi\Facades\ErpApi;
use Amplify\ErpApi\Wrappers\Invoice;
use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\System\Helpers\UtilityHelper;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class Index
 */
class Index extends BaseComponent
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public bool $showContactDetail = false,
        public bool $showInvoiceSuffix = false,
        public string $serialLabel = 'Invoice No.',
        public string $dateLabel = 'Invoice Date',
        public string $purchaseOrderLabel = 'Customer Ref.',
        public string $amountLabel = 'Amount',
        public string $balanceLabel = 'Balance',
        public string $invoicePdfLabel = 'Download PDF',
        public string $shipSignPdfLabel = 'Ship Sign PDF',
        public string $statusLabel = 'Status',
        public string $typeLabel = 'Type',
        public string $dueDateLabel = 'Due Date',
        public string $daysOpenLabel = 'Days Open',
        public string $separator = '-',

    ) {
        parent::__construct();
    }

    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        return customer(true)->can('invoices.list');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $accountSummary = ErpApi::getCustomerARSummary();

        $to = request()->has('created_end_date') ? request('created_end_date') : now(config('app.timezone'))->format('Y-m-d');
        $from = request()->has('created_start_date') ? request('created_start_date') : now(config('app.timezone'))->subDays(7)->format('Y-m-d');
        $invoiceStatus = request()->has('invoice_status') ? request('invoice_status') : ErpApiService::INVOICE_STATUS_OPEN;

        if ($invoiceStatus == 'ALL') {
            $invoiceStatus = null;
        }

        $invoiceSummary = ErpApi::getInvoiceList([
            'invoice_status' => $invoiceStatus,
            'from_entry_date' => $from,
            'to_entry_date' => $to,
        ]);

        $columns = [
            'InvoiceNumber' => (strlen($this->serialLabel) != 0),
            'InvoiceDate' => (strlen($this->dateLabel) != 0),
            'CustomerPONumber' => (strlen($this->purchaseOrderLabel) != 0),
            'InvoiceAmount' => (strlen($this->amountLabel) != 0),
            'InvoiceBalance' => (strlen($this->balanceLabel) != 0),
            'InvoicePDF' => (strlen($this->invoicePdfLabel) != 0),
            'ShipSignPDF' => (strlen($this->shipSignPdfLabel) != 0),
            'InvoiceStatus' => (strlen($this->statusLabel) != 0),
            'InvoiceType' => (strlen($this->typeLabel) != 0),
            'DueDate' => (strlen($this->dueDateLabel) != 0),
            'DaysOpen' => (strlen($this->daysOpenLabel) != 0),
        ];

        return view('widget::customer.invoice.index', compact('accountSummary', 'invoiceSummary', 'columns'));
    }

    public function formatInvoiceNumber(Invoice $invoice): string
    {
        if (! $this->showInvoiceSuffix) {
            return $invoice->InvoiceNumber;
        }

        return "{$invoice->InvoiceNumber}{$this->separator}{$invoice->InvoiceSuffix}";

    }
}
