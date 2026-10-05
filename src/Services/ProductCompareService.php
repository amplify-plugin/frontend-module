<?php

namespace Amplify\Frontend\Services;

use Amplify\System\Backend\Models\Product;
use Illuminate\Support\Collection;

class ProductCompareService
{
    public const SESSION_KEY = 'compareProducts';

    private static ?Collection $loaded = null;

    private static bool $loadedDetailed = false;

    public function enabled(): bool
    {
        return (bool) config('amplify.frontend.product_compare_enabled', true);
    }

    public function max(): int
    {
        return max(1, (int) config('amplify.frontend.product_compare_max', 4));
    }

    public function pageUrl(): string
    {
        return url(config('amplify.frontend.product_compare_page', '/product/compare'));
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
            return $this->result(true, 'This product is already in your comparison list.', 'duplicate', $ids);
        }

        if (count($ids) >= $this->max()) {
            return $this->result(
                false,
                'You can compare up to '.$this->max().' products at a time.',
                'limit',
                $ids
            );
        }

        $product = $this->findComparable($productId);

        if (! $product) {
            return $this->result(false, 'This product is not available for comparison.', 'unavailable', $ids);
        }

        $ids[] = (int) $product->id;
        $this->store($ids);

        return $this->result(
            true,
            htmlspecialchars((string) $product->product_name, ENT_QUOTES, 'UTF-8').' added to your product comparison list.',
            'added',
            $ids
        );
    }

    /**
     * @return array{success: bool, message: string, count: int, max: int, state: string, items: array<int, array<string, mixed>>}
     */
    public function remove(int $productId): array
    {
        $ids = array_values(array_filter(
            $this->validIds(),
            fn (int $id) => $id !== $productId
        ));

        $this->store($ids);

        return $this->result(true, 'Item removed from product comparison list.', 'removed', $ids);
    }

    /**
     * @return array{success: bool, message: string, count: int, max: int, state: string, items: array<int, array<string, mixed>>}
     */
    public function clear(): array
    {
        $this->store([]);

        return $this->result(true, 'Your product comparison list has been cleared.', 'cleared', []);
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
     * @param  array<int, int>|null  $ids
     * @return array{success: bool, message: string, count: int, max: int, state: string, items: array<int, array<string, mixed>>}
     */
    private function result(bool $success, string $message, string $state, ?array $ids = null): array
    {
        $items = $ids === null
            ? $this->summaries()
            : $this->summariesFor($ids);

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
     * @return Collection<int, Product>
     */
    private function load(bool $detailed): Collection
    {
        if (self::$loaded instanceof Collection && (self::$loadedDetailed || ! $detailed)) {
            return self::$loaded;
        }

        $ids = $this->normalizedIds();

        if ($ids === []) {
            if ($this->storedIsLegacy()) {
                $this->store([]);
            }

            return self::$loaded = collect();
        }

        $with = $detailed
            ? ['productImage', 'brand', 'manufacturerRelation', 'categories', 'attributes']
            : ['productImage'];

        $products = $this->comparableQuery()
            ->with($with)
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Product $product) => array_search((int) $product->id, $ids, true))
            ->values();

        $valid = $products->map(fn (Product $product) => (int) $product->id)->all();

        if ($valid !== $ids || $this->storedIsLegacy()) {
            $this->store($valid);
        }

        self::$loadedDetailed = $detailed;
        self::$loaded = $products;

        return $products;
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, array<string, mixed>>
     */
    private function summariesFor(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->comparableQuery()
            ->with('productImage')
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Product $product) => array_search((int) $product->id, $ids, true))
            ->map(fn (Product $product) => $this->summary($product))
            ->values()
            ->all();
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

        $ids = [];

        foreach ($stored as $item) {
            $id = is_array($item) ? ($item['id'] ?? null) : $item;

            if (is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function storedIsLegacy(): bool
    {
        $stored = session()->get(self::SESSION_KEY, []);

        if (! is_array($stored)) {
            return true;
        }

        foreach ($stored as $item) {
            if (is_array($item) || ! is_numeric($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function store(array $ids): void
    {
        session()->put(self::SESSION_KEY, array_values($ids));
        self::$loaded = null;
        self::$loadedDetailed = false;
    }

    private function findComparable(int $productId): ?Product
    {
        $product = Product::query()->find($productId);

        if (! $product || $this->isHiddenStatus($product->status)) {
            return null;
        }

        return $product;
    }

    private function isHiddenStatus(mixed $status): bool
    {
        return in_array(strtolower(trim((string) $status)), ['draft', 'archived'], true);
    }

    private function comparableQuery()
    {
        return Product::query()->where(function ($query) {
            $query->whereNull('status')
                ->orWhereRaw('LOWER(TRIM(status)) NOT IN (?, ?)', ['draft', 'archived']);
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

        $definitions = [
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

        foreach ($definitions as $label => $resolver) {
            $values = [];

            foreach ($products as $product) {
                $values[(int) $product->id] = $this->display($resolver($product));
            }

            $rows[] = $this->row($label, $values);
        }

        $attributeLabels = [];

        foreach ($products as $product) {
            foreach ($product->attributes as $attribute) {
                $name = $this->display($attribute->name);

                if ($name === '' || $this->attributeExcluded($name)) {
                    continue;
                }

                $label = $attribute->unit ? $name.' ('.$attribute->unit.')' : $name;
                $attributeLabels[$label][(int) $product->id] = $this->display($attribute->pivot->attribute_value ?? null);
            }
        }

        ksort($attributeLabels);

        foreach ($attributeLabels as $label => $values) {
            $filled = [];

            foreach ($products as $product) {
                $filled[(int) $product->id] = $values[(int) $product->id] ?? '';
            }

            $rows[] = $this->row($label, $filled);
        }

        return array_values(array_filter($rows, function (array $row) {
            return collect($row['values'])->contains(fn (string $value) => $value !== '');
        }));
    }

    /**
     * @param  array<int, string>  $values
     * @return array{label: string, values: array<int, string>, differs: bool}
     */
    private function row(string $label, array $values): array
    {
        $comparable = array_map(fn (string $value) => $value === '' ? '-' : $value, $values);

        return [
            'label' => $label,
            'values' => $comparable,
            'differs' => count(array_unique($comparable)) > 1,
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

            $parts = [];

            foreach ($value as $item) {
                $text = $this->display($item);

                if ($text !== '') {
                    $parts[] = $text;
                }
            }

            return implode(', ', $parts);
        }

        return trim((string) $value);
    }
}
