<?php

namespace Amplify\Frontend\Http\Rules;

use Amplify\System\Backend\Models\OrderList;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class FavoriteListRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $hasItems = OrderList::join('order_list_items', 'order_lists.id', '=', 'order_list_items.list_id')
            ->where('order_lists.contact_id', customer(true)->getKey())
            ->where('order_lists.customer_id', customer()->getKey())
            ->where('order_list_items.product_id', $value)
            ->where('order_list_items.list_id', request()->list_id)
            ->exists();
        if ($hasItems) {
            $list_name = Str::lower(request('title', 'Order List'));
            $fail("This product is already in the {$list_name}. Please choose a different one.");
        }
    }
}
