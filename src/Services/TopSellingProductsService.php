<?php

namespace Amplify\Frontend\Services;

use Amplify\System\Backend\Models\CustomerOrder;
use Amplify\System\Backend\Models\CustomerOrderLine;
use Amplify\System\Backend\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TopSellingProductsService
{
    public const DATE_RANGE_LABELS = [
        'last_7_days' => 'Last 7 Days',
        'last_30_days' => 'Last 30 Days',
        'last_60_days' => 'Last 60 Days',
        'last_90_days' => 'Last 90 Days',
        'this_year' => 'This Year',
        'custom' => 'Custom Date Range',
    ];

    public function __construct(
        protected PurchasedTogetherProductService $productLoader,
    ) {}

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
     * Ranked product IDs for the selling-products carousel (site-wide sales).
     *
     * Query results are cached. Default TTL is 1 hour (3600 seconds).
     * Pass $cacheTtl to override; use 0 to bypass the cache.
     *
     * @return array<int>
     */
    public function getTopSellingProductIds(?int $limit = null, ?int $excludeProductId = null, ?int $cacheTtl = null): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        $limit ??= $this->productsLimit();
        $cacheTtl ??= $this->cacheTtl();

        $cacheKey = $this->buildCacheKey($limit);

        $resolver = fn () => $this->queryTopSellingProductIds($limit);

        $productIds = $cacheTtl > 0
            ? Cache::remember($cacheKey, $cacheTtl, $resolver)
            : $resolver();

        $productIds = array_values(array_map('intval', (array) $productIds));

        if ($excludeProductId) {
            $productIds = array_values(array_filter(
                $productIds,
                fn (int $id) => $id !== (int) $excludeProductId,
            ));
        }

        return array_slice($productIds, 0, $limit);
    }

    public function cacheTtl(): int
    {
        $ttl = (int) config('amplify.selling_products.cache_ttl', HOUR);

        return max(0, $ttl);
    }

    /**
     * @return array<int>
     */
    protected function queryTopSellingProductIds(int $limit): array
    {
        $fetchLimit = max($limit * 3, $limit);
        [$start, $end] = $this->resolveDateBounds();

        $statuses = array_values(array_filter((array) config('amplify.selling_products.eligible_order_statuses', [])));
        $orderType = config('amplify.selling_products.order_type', CustomerOrder::IS_ORDER_TYPE);
        $rankBy = $this->rankBy();
        $orderColumn = $rankBy === 'quantity' ? 'units_sold' : 'revenue';

        $rows = CustomerOrderLine::query()
            ->from('customer_order_lines as lines')
            ->join('customer_orders as orders', 'orders.id', '=', 'lines.customer_order_id')
            ->where('orders.order_type', $orderType)
            ->when($statuses !== [], fn ($q) => $q->whereIn('orders.order_status', $statuses))
            ->when($start, fn ($q) => $q->where('orders.created_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('orders.created_at', '<=', $end))
            ->whereNotNull('lines.product_code')
            ->where('lines.product_code', '!=', '')
            ->select([
                'lines.product_code',
                DB::raw('MAX(lines.product_id) as product_id'),
                DB::raw('SUM(lines.qty) as units_sold'),
                DB::raw('ROUND(SUM(lines.qty * lines.customer_price), 2) as revenue'),
            ])
            ->groupBy('lines.product_code')
            ->orderByDesc($orderColumn)
            ->limit($fetchLimit)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $productsByCode = Product::query()
            ->whereIn('product_code', $rows->pluck('product_code')->unique()->all())
            ->whereNotIn('status', ['draft', 'archived'])
            ->get()
            ->keyBy('product_code');

        $productIds = [];

        foreach ($rows as $row) {
            $product = $productsByCode->get($row->product_code);
            $productId = $product?->id ? (int) $product->id : (int) ($row->product_id ?: 0);

            if ($productId <= 0) {
                continue;
            }

            if (in_array($productId, $productIds, true)) {
                continue;
            }

            $productIds[] = $productId;

            if (count($productIds) >= $limit) {
                break;
            }
        }

        return $productIds;
    }

    protected function buildCacheKey(int $limit): string
    {
        [$start, $end] = $this->resolveDateBounds();

        $payload = [
            'limit' => $limit,
            'rank_by' => $this->rankBy(),
            'date_range' => $this->dateRangeKey(),
            'start' => $start?->toDateTimeString(),
            'end' => $end?->toDateTimeString(),
            'order_type' => config('amplify.selling_products.order_type', CustomerOrder::IS_ORDER_TYPE),
            'statuses' => array_values(array_filter((array) config('amplify.selling_products.eligible_order_statuses', []))),
        ];

        return 'amplify.top_selling_products.'.md5(json_encode($payload));
    }

    /**
     * @param  array<int>  $productIds
     */
    public function loadProducts(array $productIds): Collection
    {
        return $this->productLoader->loadProducts($productIds);
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
