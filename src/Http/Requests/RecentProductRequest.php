<?php

namespace Amplify\Frontend\Http\Requests;

use Amplify\Frontend\Services\RecentlyViewedProductService;
use Illuminate\Foundation\Http\FormRequest;

class RecentProductRequest extends FormRequest
{
    public function __construct(
        protected RecentlyViewedProductService $service,
    ) {}

    public function rules(): array
    {
        return [
            'product_ids' => 'required|array|max:' . $this->service->maxItems(),
            'product_ids.*' => 'integer',
            'layout' => 'nullable|string|in:card,minimal',
            'show_title' => 'nullable|boolean',
            'title' => 'nullable|string|max:255',
            'products_limit' => 'nullable|integer|min:1|max:' . $this->service->maxItems(),
            'show_cart_btn' => 'nullable|boolean',
            'cart_button_label' => 'nullable|string|max:255',
            'detail_button_label' => 'nullable|string|max:255',
            'show_price' => 'nullable|boolean',
            'show_guest_price' => 'nullable|boolean',
            'show_top_discount_badge' => 'nullable|boolean',
            'show_order_list' => 'nullable|boolean',
            'order_list_label' => 'nullable|string|max:255',
            'show_navigation' => 'nullable|boolean',
            'slider_item_gap' => 'nullable|integer|min:0|max:100',
            'display_product_code' => 'nullable|boolean',
            'display_short_description' => 'nullable|boolean',
            'display_manufacturer' => 'nullable|boolean',
            'allow_remove' => 'nullable|boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        $booleanFields = [
            'show_title',
            'show_cart_btn',
            'show_price',
            'show_guest_price',
            'show_top_discount_badge',
            'show_order_list',
            'show_navigation',
            'display_product_code',
            'display_short_description',
            'display_manufacturer',
            'allow_remove',
        ];

        $data = [];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $data[$field] = filter_var(
                    $this->input($field),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );
            }
        }

        $this->merge($data);
    }
}
