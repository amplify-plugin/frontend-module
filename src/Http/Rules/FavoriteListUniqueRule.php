<?php

namespace Amplify\Frontend\Http\Rules;

use Amplify\System\Backend\Models\OrderList;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FavoriteListUniqueRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $inputs = request()->all();
        if ($inputs['list_id'] == null) {
            $hasItems = OrderList::where([
                'name' => $inputs['list_name'],
                'list_type' => $inputs['list_type'],
                'contact_id' => customer(true)->getKey(),
                'customer_id' => customer()->getKey(),
            ])->exists();
            if ($hasItems) {
                $fail("The name '{$inputs['list_name']}' cannot be used because it is already assigned to a {$value} list type.");
            }
        }
    }
}
