<?php

namespace Amplify\Frontend\Services;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Backend\Models\ProductPurchasedTogether;
use Illuminate\Support\Collection;

class PurchasedTogetherProductService
{
    /**
     * @param  array<int>  $cartProductIds
     * @return array<int>
     */
    public function getSuggestedProductIds(?int $productId = null, array $cartProductIds = [], int $limit = 10): array
    {
        if (! config('amplify.purchased_together.enabled', true)) {
            return [];
        }

        $cartProductIds = array_values(array_unique(array_filter($cartProductIds)));

        if ($productId) {
            return $this->getForSingleProduct($productId, [$productId, ...$cartProductIds], $limit);
        }

        if ($cartProductIds !== []) {
            return $this->getForCart($cartProductIds, $limit);
        }

        return [];
    }

    /**
     * @param  array<int>  $productIds
     */
    public function loadProducts(array $productIds): Collection
    {
        if ($productIds === []) {
            return collect();
        }

        $products = Product::query()
            ->with(['productImage', 'manufacturerRelation'])
            ->whereIn('id', $productIds)
            ->whereNotIn('status', ['draft', 'archived'])
            ->get();

        $ordered = collect($productIds)
            ->map(fn (int $id) => $products->firstWhere('id', $id))
            ->filter();

        return $this->attachErpData($ordered);
    }

    /**
     * @param  array<int>  $excludeIds
     * @return array<int>
     */
    protected function getForSingleProduct(int $productId, array $excludeIds, int $limit): array
    {
        $pairs = ProductPurchasedTogether::query()
            ->where(function ($query) use ($productId) {
                $query->where('product_id_a', $productId)
                    ->orWhere('product_id_b', $productId);
            })
            ->orderByDesc('occurrence_count')
            ->limit(max($limit * 3, $limit))
            ->get();

        $suggestedIds = [];

        foreach ($pairs as $pair) {
            $suggestedId = (int) ($pair->product_id_a === $productId
                ? $pair->product_id_b
                : $pair->product_id_a);

            if (in_array($suggestedId, $excludeIds, true) || in_array($suggestedId, $suggestedIds, true)) {
                continue;
            }

            $suggestedIds[] = $suggestedId;

            if (count($suggestedIds) >= $limit) {
                break;
            }
        }

        return $suggestedIds;
    }

    /**
     * @param  array<int>  $cartProductIds
     * @return array<int>
     */
    protected function getForCart(array $cartProductIds, int $limit): array
    {
        $pairs = ProductPurchasedTogether::query()
            ->where(function ($query) use ($cartProductIds) {
                $query->whereIn('product_id_a', $cartProductIds)
                    ->orWhereIn('product_id_b', $cartProductIds);
            })
            ->orderByDesc('occurrence_count')
            ->get();

        $scores = [];

        foreach ($pairs as $pair) {
            $suggestedId = null;

            if (in_array($pair->product_id_a, $cartProductIds, true)) {
                $suggestedId = (int) $pair->product_id_b;
            } elseif (in_array($pair->product_id_b, $cartProductIds, true)) {
                $suggestedId = (int) $pair->product_id_a;
            }

            if (! $suggestedId || in_array($suggestedId, $cartProductIds, true)) {
                continue;
            }

            $scores[$suggestedId] = ($scores[$suggestedId] ?? 0) + $pair->occurrence_count;
        }

        arsort($scores);

        return array_slice(array_keys($scores), 0, $limit);
    }

    protected function attachErpData(Collection $products): Collection
    {
        if ($products->isEmpty()) {
            return $products;
        }

        if (! customer_check() && ! config('amplify.basic.enable_guest_pricing')) {
            return $products;
        }

        $warehouses = ErpApi::getWarehouses();
        $warehouseString = $warehouses->pluck('WarehouseNumber')->implode(',');

        $erpResponse = ErpApi::getProductPriceAvailability([
            'items' => $products->map(fn (Product $product) => [
                'item' => $product->product_code,
                'uom' => $product->uom ?? 'EA',
                'qty' => $product->min_order_qty ?? 1,
            ])->values()->all(),
            'warehouse' => $warehouseString,
        ]);

        $erpItems = [];

        foreach ($erpResponse as $erpItem) {
            $code = $erpItem['ItemNumber'] ?? null;

            if ($code) {
                $erpItems[$code] = $erpItem;
            }
        }

        $customer = ErpApi::getCustomerDetail();
        $warehouseCode = $customer->DefaultWarehouse ?: (customer_check()
            ? customer()?->warehouse?->code
            : config('amplify.frontend.guest_checkout_warehouse'));

        return $products->map(function (Product $product) use ($erpItems, $erpResponse, $warehouseCode) {
            $code = $product->product_code;
            $product->ERP = $erpItems[$code] ?? null;

            if ($warehouseCode) {
                $warehouseMatch = collect($erpResponse)
                    ->where('ItemNumber', $code)
                    ->where('WarehouseID', $warehouseCode)
                    ->first();

                if ($warehouseMatch) {
                    $product->ERP = $warehouseMatch;
                }
            }

            $product->total_quantity_available = collect($erpResponse)
                ->where('ItemNumber', $code)
                ->sum('QuantityAvailable');

            return $product;
        });
    }
}
