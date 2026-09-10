<?php

namespace Amplify\Frontend\Services;

use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Backend\Models\RecentlyViewedProduct;
use Amplify\System\Backend\Services\RecentlyViewedAnalyticsService;
use Illuminate\Support\Collection;

class RecentlyViewedProductService
{
    public function __construct(
        protected PurchasedTogetherProductService $productLoader,
        protected RecentlyViewedAnalyticsService $analytics,
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

        $this->analytics->recordView($contact, $productId);

        $this->trimHistory($contact);
    }

    /**
     * @param  array<int|string|null>  $productIds
     */
    public function markAddedToCart(Contact $contact, array $productIds): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $this->analytics->markAddedToCart($contact, $this->sanitizeProductIds($productIds));
    }

    /**
     * @param  array<int|string|null>  $productIds
     */
    public function markQuoted(Contact $contact, array $productIds): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $this->analytics->markQuoted($contact, $this->sanitizeProductIds($productIds));
    }

    /**
     * @param  array<int|string|null>  $productIds
     */
    public function markOrdered(Contact $contact, array $productIds): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $this->analytics->markOrdered($contact, $this->sanitizeProductIds($productIds));
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
        $sessionId = (string) session()->getId();

        foreach ($merged as $index => $productId) {
            $existing = RecentlyViewedProduct::query()
                ->where('contact_id', $contact->id)
                ->where('product_id', $productId)
                ->first();

            if ($existing === null) {
                RecentlyViewedProduct::query()->create([
                    'customer_id' => $contact->customer_id,
                    'contact_id' => $contact->id,
                    'product_id' => $productId,
                    'session' => $sessionId,
                    'repeat' => 1,
                    'viewed_at' => $timestamp->copy()->subSeconds($index),
                ]);

                continue;
            }

            $existing->update([
                'customer_id' => $contact->customer_id,
                'viewed_at' => $timestamp->copy()->subSeconds($index),
                'session' => $sessionId,
            ]);
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
