<?php

namespace Amplify\Frontend\Services;

use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Backend\Models\RecentlyViewedProduct;
use Illuminate\Support\Collection;

class RecentlyViewedProductService
{
    public function __construct(
        protected PurchasedTogetherProductService $productLoader,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('amplify.recently_viewed.enabled', true);
    }

    public function maxItems(): int
    {
        return max(1, (int) config('amplify.recently_viewed.max_items', 20));
    }

    public function record(Product|int $product, Contact $contact): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $productId = $product instanceof Product ? (int) $product->getKey() : (int) $product;

        if (! $this->isViewableProductId($productId)) {
            return;
        }

        RecentlyViewedProduct::query()->updateOrCreate(
            [
                'contact_id' => $contact->id,
                'product_id' => $productId,
            ],
            [
                'customer_id' => $contact->customer_id,
                'last_viewed_at' => now(),
            ],
        );

        $this->trimHistory($contact);
    }

    /**
     * @return array<int>
     */
    public function getProductIds(Contact $contact, ?int $limit = null): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        $limit ??= $this->maxItems();

        return RecentlyViewedProduct::query()
            ->where('contact_id', $contact->id)
            ->where('customer_id', $contact->customer_id)
            ->orderByDesc('viewed_at')
            ->limit($limit)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  array<int>  $productIds
     */
    public function loadProducts(array $productIds): Collection
    {
        $productIds = $this->sanitizeProductIds($productIds);

        if ($productIds === []) {
            return collect();
        }

        return $this->productLoader->loadProducts($productIds);
    }

    public function remove(int $productId, Contact $contact): bool
    {
        return (bool) RecentlyViewedProduct::query()
            ->where('contact_id', $contact->id)
            ->where('customer_id', $contact->customer_id)
            ->where('product_id', $productId)
            ->delete();
    }

    public function clear(Contact $contact): int
    {
        return RecentlyViewedProduct::query()
            ->where('contact_id', $contact->id)
            ->where('customer_id', $contact->customer_id)
            ->delete();
    }

    /**
     * @param  array<int>  $guestProductIds
     */
    public function mergeGuestHistory(Contact $contact, array $guestProductIds): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $guestProductIds = $this->sanitizeProductIds($guestProductIds);

        if ($guestProductIds === []) {
            return;
        }

        $existingIds = $this->getProductIds($contact);
        $merged = $guestProductIds;

        foreach ($existingIds as $existingId) {
            if (! in_array($existingId, $merged, true)) {
                $merged[] = $existingId;
            }
        }

        $merged = array_slice($merged, 0, $this->maxItems());
        $timestamp = now();

        foreach ($merged as $index => $productId) {
            RecentlyViewedProduct::query()->updateOrCreate(
                [
                    'contact_id' => $contact->id,
                    'product_id' => $productId,
                ],
                [
                    'customer_id' => $contact->customer_id,
                    'last_viewed_at' => $timestamp->copy()->subSeconds($index),
                ],
            );
        }

        RecentlyViewedProduct::query()
            ->where('contact_id', $contact->id)
            ->where('customer_id', $contact->customer_id)
            ->whereNotIn('product_id', $merged)
            ->delete();
    }

    protected function trimHistory(Contact $contact): void
    {
        $keepIds = $this->getProductIds($contact);

        if ($keepIds === []) {
            return;
        }

        RecentlyViewedProduct::query()
            ->where('contact_id', $contact->id)
            ->where('customer_id', $contact->customer_id)
            ->whereNotIn('product_id', $keepIds)
            ->delete();
    }

    protected function isViewableProductId(int $productId): bool
    {
        return Product::query()
            ->whereKey($productId)
            ->whereNotIn('status', ['draft', 'archived'])
            ->exists();
    }

    /**
     * @param  array<int|string|null>  $productIds
     * @return array<int>
     */
    public function sanitizeProductIds(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map(
            fn ($id) => (int) $id,
            $productIds,
        ))));

        if ($productIds === []) {
            return [];
        }

        $validIds = Product::query()
            ->whereIn('id', $productIds)
            ->whereNotIn('status', ['draft', 'archived'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $validLookup = array_flip($validIds);

        return array_values(array_filter(
            $productIds,
            fn (int $id) => isset($validLookup[$id]),
        ));
    }
}
