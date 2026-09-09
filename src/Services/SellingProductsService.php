<?php

namespace Amplify\Frontend\Services;

use Amplify\System\Backend\Models\CustomerOrder;
use Amplify\System\Backend\Models\CustomerOrderLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SellingProductsService
{
    public const DATE_RANGE_LABELS = [
        'last_7_days' => 'Last 7 Days',
        'last_30_days' => 'Last 30 Days',
        'last_60_days' => 'Last 60 Days',
        'last_90_days' => 'Last 90 Days',
        'this_year' => 'This Year',
        'custom' => 'Custom Date Range',
    ];

    public function isEnabled(): bool
    {
        return (bool) config('amplify.selling_products.enabled', true);
    }

    public function productsLimit(): int
    {
        $limit = (int) config('amplify.selling_products.products_limit', 10);

        return in_array($limit, [5, 10, 20], true) ? $limit : 10;
    }

    public function rankBy(): string
    {
        $rankBy = strtolower((string) config('amplify.selling_products.rank_by', 'revenue'));

        return in_array($rankBy, ['revenue', 'quantity'], true) ? $rankBy : 'revenue';
    }

    public function dateRangeKey(): string
    {
        $key = (string) config('amplify.selling_products.date_range', 'last_60_days');

        return array_key_exists($key, self::DATE_RANGE_LABELS) ? $key : 'last_60_days';
    }

    public function dateRangeLabel(): string
    {
        $key = $this->dateRangeKey();

        if ($key === 'custom') {
            [$start, $end] = $this->resolveDateBounds();

            if ($start && $end) {
                return $start->format('M j, Y').' – '.$end->format('M j, Y');
            }
        }

        return self::DATE_RANGE_LABELS[$key] ?? self::DATE_RANGE_LABELS['last_60_days'];
    }

    /**
     * Top-selling products for a customer within the configured window.
     *
     * Returns an empty collection when the widget is disabled so callers
     * do not execute unnecessary queries.
     *
     * @return Collection<int, object{
     *     product_code: string,
     *     unit_code: string|null,
     *     product_name: string|null,
     *     product_id: int|null,
     *     product_slug: string|null,
     *     units_sold: float|int,
     *     revenue: float,
     *     order_count: int
     * }>
     */
    public function getTopSellingProducts(?int $customerId, ?int $limit = null): Collection
    {
        if (! $this->isEnabled()) {
            return collect();
        }

        if (! $customerId) {
            return collect();
        }

        $limit ??= $this->productsLimit();
        [$start, $end] = $this->resolveDateBounds();

        $statuses = array_values(array_filter((array) config('amplify.selling_products.eligible_order_statuses', [])));
        $orderType = config('amplify.selling_products.order_type', CustomerOrder::IS_ORDER_TYPE);
        $rankBy = $this->rankBy();
        $orderColumn = $rankBy === 'quantity' ? 'units_sold' : 'revenue';

        $query = CustomerOrderLine::query()
            ->from('customer_order_lines as lines')
            ->join('customer_orders as orders', 'orders.id', '=', 'lines.customer_order_id')
            ->where('orders.customer_id', $customerId)
            ->where('orders.order_type', $orderType)
            ->when($statuses !== [], fn ($q) => $q->whereIn('orders.order_status', $statuses))
            ->when($start, fn ($q) => $q->where('orders.created_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('orders.created_at', '<=', $end))
            ->whereNotNull('lines.product_code')
            ->where('lines.product_code', '!=', '')
            ->select([
                'lines.product_code',
                'lines.unit_code',
                DB::raw('SUM(lines.qty) as units_sold'),
                DB::raw('ROUND(SUM(lines.qty * lines.customer_price), 2) as revenue'),
                DB::raw('COUNT(DISTINCT lines.customer_order_id) as order_count'),
            ])
            ->groupBy('lines.product_code', 'lines.unit_code')
            ->orderByDesc($orderColumn)
            ->orderBy('lines.product_code')
            ->limit($limit);

        $rows = $query->get();

        $products = \Amplify\System\Backend\Models\Product::query()
            ->whereIn('product_code', $rows->pluck('product_code')->filter()->unique()->all())
            ->get()
            ->keyBy('product_code');

        return $rows->map(function ($row) use ($products) {
            $product = $products->get($row->product_code);

            $row->units_sold = (float) $row->units_sold;
            $row->revenue = round((float) $row->revenue, 2);
            $row->order_count = (int) $row->order_count;
            $row->product = $product;
            $row->product_id = $product?->id;
            $row->product_name = $product?->product_name;
            $row->product_slug = $product?->product_slug;

            return $row;
        });
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public function resolveDateBounds(?string $dateRange = null, ?string $customStart = null, ?string $customEnd = null): array
    {
        $dateRange ??= $this->dateRangeKey();
        $timezone = config('app.timezone');
        $now = Carbon::now($timezone);

        return match ($dateRange) {
            'last_7_days' => [$now->copy()->subDays(7)->startOfDay(), $now->copy()->endOfDay()],
            'last_30_days' => [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay()],
            'last_60_days' => [$now->copy()->subDays(60)->startOfDay(), $now->copy()->endOfDay()],
            'last_90_days' => [$now->copy()->subDays(90)->startOfDay(), $now->copy()->endOfDay()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
            'custom' => $this->resolveCustomBounds(
                $customStart ?? config('amplify.selling_products.custom_start_date'),
                $customEnd ?? config('amplify.selling_products.custom_end_date'),
                $timezone
            ),
            default => [$now->copy()->subDays(60)->startOfDay(), $now->copy()->endOfDay()],
        };
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    protected function resolveCustomBounds(mixed $start, mixed $end, string $timezone): array
    {
        $startDate = $this->parseDate($start, $timezone)?->startOfDay();
        $endDate = $this->parseDate($end, $timezone)?->endOfDay();

        if (! $startDate && ! $endDate) {
            $now = Carbon::now($timezone);

            return [$now->copy()->subDays(60)->startOfDay(), $now->copy()->endOfDay()];
        }

        return [$startDate, $endDate];
    }

    protected function parseDate(mixed $value, string $timezone): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value, $timezone);
        } catch (\Throwable) {
            return null;
        }
    }
}
