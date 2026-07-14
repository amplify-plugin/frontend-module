<?php

namespace Amplify\Frontend\Http\Controllers\Order;

use Amplify\Frontend\Jobs\OrderExportJob;
use Amplify\Frontend\Traits\DynamicPageLoad\OrderTrait;
use Amplify\Frontend\Traits\HasDynamicPage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ExportController extends Controller
{
    use OrderTrait;

    use HasDynamicPage;

    public function __invoke(Request $request)
    {
        hasAccessOrFail('order.export');

        $entries = $request->query('entries');

        $threshold = $request->query('threshold', 10);

        $filters = $request->input('filters', []);

        $filename = 'orders.xlsx';

        if ($entries > $threshold) {
            OrderExportJob::dispatch($entries, $filters);

            return $this->apiResponse(true, __('Your order export is being processed. You will receive a email once it is ready for download.'));
        }

        try {

            $orders = $this->getOrders($filters);

            return response(\view('system::report.order', $orders)->render(), 200, [
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Throwable $e) {

            report($e);

            return $this->apiResponse(false, __('Failed to export orders. Please try again later.'));
        }

    }
}
