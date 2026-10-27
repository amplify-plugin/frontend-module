<?php

namespace Amplify\Frontend\Services;

use Amplify\System\Backend\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductCompareService
{
    public const SESSION_KEY = 'compareProducts';

    private const HIDDEN_STATUSES = [];

    /**
     * Shared across instances so the many compare buttons on a page reuse one query.
     */
    private static ?Collection $products = null;

    private static bool $productsDetailed = false;

    public function enabled(): bool
    {
        return (bool) config('amplify.frontend.product_compare_enabled', true);
    }

    public function availableFor(string $permission): bool
    {
        return $this->enabled() && (! customer_check() || customer(true)->can($permission));
    }

    public function max(): int
    {
        return max(1, (int) config('amplify.frontend.product_compare_max', 4));
    }

    public function pageSlug(): string
    {
        return trim((string) config('amplify.frontend.product_compare_page', '/product/compare'), '/');
    }

    public function pageUrl(): string
    {
        return url($this->pageSlug());
    }

    public function contains(int $productId): bool
    {
        return in_array($productId, $this->validIds(), true);
    }

    /**
     * @return array{success: bool, message: string, count: int, max: int, state: string, items: array<int, array<string, mixed>>}
     */
    public function list(): array
    {
        return $this->result(true, 'Your product comparison list', 'list');
    }

    /**
     * @return array{success: bool, message: string, count: int, max: int, state: string, items: array<int, array<string, mixed>>}
     */
    public function add(int $productId): array
    {
        $ids = $this->validIds();

        if (in_array($productId, $ids, true)) {
            return $this->result(true, 'This product is already in your comparison list.', 'duplicate');
        }

        if (count($ids) >= $this->max()) {
            return $this->result(false, 'You can compare up to '.$this->max().' products at a time.', 'limit');
        }

        $product = Product::query()->find($productId);

        if (! $product || $this->isHiddenStatus($product->status)) {
            return $this->result(false, 'This product is not available for comparison.', 'unavailable');
        }

        $this->store([...$ids, (int) $product->id]);

        $name = htmlspecialchars((string) $product->product_name, ENT_QUOTES, 'UTF-8');

        return $this->result(true, $name.' added to your product comparison list.', 'added');
    }

    /**
     * @return array{success: bool, message: string, count: int, max: int, state: string, items: array<int, array<string, mixed>>}
     */
    public function remove(int $productId): array
    {
        $this->store(array_filter($this->validIds(), fn (int $id) => $id !== $productId));

        return $this->result(true, 'Item removed from product comparison list.', 'removed');
    }

    /**
     * @return array{success: bool, message: string, count: int, max: int, state: string, items: array<int, array<string, mixed>>}
     */
    public function clear(): array
    {
        $this->store([]);

        return $this->result(true, 'Your product comparison list has been cleared.', 'cleared');
    }

    /**
     * @return array{products: array<int, array<string, mixed>>, rows: array<int, array<string, mixed>>}
     */
    public function comparison(): array
    {
        $products = $this->load(true);

        return [
            'products' => $products->map(fn (Product $product) => $this->summary($product))->values()->all(),
            'rows' => $this->rows($products),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function summaries(): array
    {
        return $this->load(false)
            ->map(fn (Product $product) => $this->summary($product))
            ->values()
            ->all();
    }

    /**
     * @return array{success: bool, message: string, count: int, max: int, state: string, items: array<int, array<string, mixed>>}
     */
    private function result(bool $success, string $message, string $state): array
    {
        $items = $this->summaries();

        return [
            'success' => $success,
            'message' => $message,
            'count' => count($items),
            'max' => $this->max(),
            'state' => $state,
            'items' => $items,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function validIds(): array
    {
        return $this->load(false)
            ->map(fn (Product $product) => (int) $product->id)
            ->values()
            ->all();
    }

    /**
     * Loads the compared products in session order and rewrites the session
     * when it holds unavailable products or the legacy storage format.
     *
     * @return Collection<int, Product>
     */
    private function load(bool $detailed): Collection
    {
        if (self::$products !== null && (self::$productsDetailed || ! $detailed)) {
            return self::$products;
        }

        $ids = $this->normalizedIds();

        $relations = $detailed
            ? ['productImage', 'brand', 'manufacturerRelation', 'categories', 'attributes', 'attributeValues']
            : ['productImage'];

        $products = $ids === [] ? collect() : $this->comparableQuery()
            ->with($relations)
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Product $product) => array_search((int) $product->id, $ids, true))
            ->values();

        $validIds = $products->map(fn (Product $product) => (int) $product->id)->all();

        if ($validIds !== $ids || $this->storedIsLegacy()) {
            $this->store($validIds);
        }

        self::$productsDetailed = $detailed;

        return self::$products = $products;
    }

    /**
     * @return array<int, int>
     */
    private function normalizedIds(): array
    {
        $stored = session()->get(self::SESSION_KEY, []);

        if (! is_array($stored)) {
            return [];
        }

        return collect($stored)
            ->map(fn ($item) => is_array($item) ? ($item['id'] ?? null) : $item)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Older versions stored product arrays (or other values) instead of plain ids.
     */
    private function storedIsLegacy(): bool
    {
        $stored = session()->get(self::SESSION_KEY, []);

        return ! is_array($stored)
            || collect($stored)->contains(fn ($item) => is_array($item) || ! is_numeric($item));
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function store(array $ids): void
    {
        session()->put(self::SESSION_KEY, array_values($ids));
        self::$products = null;
    }

    private function isHiddenStatus(mixed $status): bool
    {
        return in_array(strtolower(trim((string) $status)), self::HIDDEN_STATUSES, true);
    }

    private function comparableQuery(): Builder
    {
        $hidden = self::HIDDEN_STATUSES;

        if ($hidden === []) {
            return Product::query();
        }

        $placeholders = implode(', ', array_fill(0, count($hidden), '?'));

        return Product::query()->where(function ($query) use ($hidden, $placeholders) {
            $query->whereNull('status')
                ->orWhereRaw("LOWER(TRIM(status)) NOT IN ({$placeholders})", $hidden);
        });
    }

    /**
     * @return array{id: int, code: string, name: string, image: string, href: string}
     */
    private function summary(Product $product): array
    {
        return [
            'id' => (int) $product->id,
            'code' => (string) $product->product_code,
            'name' => (string) $product->product_name,
            'image' => $product->productImage?->main ?: config('amplify.frontend.fallback_image_path'),
            'href' => frontendSingleProductURL($product),
        ];
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<int, array{label: string, values: array<int, string>, differs: bool}>
     */
    private function rows(Collection $products): array
    {
        if ($products->isEmpty()) {
            return [];
        }

        return [...$this->fieldRows($products), ...$this->attributeRows($products)];
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<int, array{label: string, values: array<int, string>, differs: bool}>
     */
    private function fieldRows(Collection $products): array
    {
        $fields = [
            'Item Number' => fn (Product $product) => $product->product_code,
            'Brand' => fn (Product $product) => $product->brand?->title,
            'Manufacturer' => fn (Product $product) => $product->manufacturerRelation?->name
                ?? $product->manufacturerRelation?->title,
            'Manufacturer Item' => fn (Product $product) => is_string($product->manufacturer) ? $product->manufacturer : null,
            'Category' => fn (Product $product) => $product->categories
                ->map(fn ($category) => $category->category_name)
                ->filter()
                ->implode(', '),
        ];

        $rows = [];

        foreach ($fields as $label => $resolve) {
            $rows[] = $this->row($label, $products, fn (Product $product) => $this->display($resolve($product)));
        }

        return $rows;
    }

    /**
     * One row per attribute label, sorted by label. A product with several
     * values for the same label gets them joined with commas.
     *
     * @param  Collection<int, Product>  $products
     * @return array<int, array{label: string, values: array<int, string>, differs: bool}>
     */
    private function attributeRows(Collection $products): array
    {
        $valuesByLabel = [];

        $collect = function (int $productId, string $name, string $label, string $value) use (&$valuesByLabel) {
            if ($name === '' || $value === '' || $this->attributeExcluded($name)) {
                return;
            }

            $existing = $valuesByLabel[$label][$productId] ?? null;
            $valuesByLabel[$label][$productId] = $existing === null ? $value : $existing.', '.$value;
        };

        foreach ($products as $product) {
            foreach ($product->attributes as $attribute) {
                $name = $this->display($attribute->name);
                $label = $attribute->unit ? $name.' ('.$this->display($attribute->unit).')' : $name;
                $collect((int) $product->id, $name, $label, $this->display($attribute->pivot->attribute_value ?? null));
            }

            foreach ($product->attributeValues as $attribute) {
                $name = $this->display($attribute->attribute_name);
                $collect((int) $product->id, $name, $name, $this->display($attribute->attribute_value));
            }
        }

        ksort($valuesByLabel);

        $rows = [];

        foreach ($valuesByLabel as $label => $values) {
            $rows[] = $this->row($label, $products, fn (Product $product) => $values[(int) $product->id] ?? '');
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  callable(Product): string  $resolve
     * @return array{label: string, values: array<int, string>, differs: bool}
     */
    private function row(string $label, Collection $products, callable $resolve): array
    {
        $values = [];

        foreach ($products as $product) {
            $value = $resolve($product);
            $values[(int) $product->id] = $value === '' ? '-' : $value;
        }

        return [
            'label' => $label,
            'values' => $values,
            'differs' => count(array_unique($values)) > 1,
        ];
    }

    private function attributeExcluded(string $name): bool
    {
        $excluded = config('amplify.frontend.product_compare_excluded_attributes', []);

        return in_array(strtolower($name), array_map('strtolower', $excluded), true);
    }

    private function display(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
                $decoded = json_decode($trimmed, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return $this->display($decoded);
                }
            }

            return $trimmed;
        }

        if (is_array($value)) {
            $locale = config('app.locale');

            if (array_key_exists($locale, $value) && ! is_array($value[$locale])) {
                return $this->display($value[$locale]);
            }

            $parts = array_map(fn ($item) => $this->display($item), $value);

            return implode(', ', array_filter($parts, fn (string $part) => $part !== ''));
        }

        return trim((string) $value);
    }
}
