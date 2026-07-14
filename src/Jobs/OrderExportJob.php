<?php

namespace Amplify\Frontend\Jobs;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Exports\OrderExport;
use Amplify\System\Backend\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class OrderExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public array   $filters = [],
                                public string  $filename,
                                public Contact $contact,
                                public string  $writer = 'Xlsx')
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $orders = ErpApi::getOrderList($this->filters);

        $export = Excel::raw(new OrderExport($orders), $this->writer);
    }
}
