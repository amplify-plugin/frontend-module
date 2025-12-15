<?php

namespace Amplify\Frontend\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $itemCount = (config('amplify.frontend.cart_item_badge_style', 'items') == 'items')
            ? $this->cartItems->count()
            : $this->cartItems->sum('quantity');

        if ($itemCount > 99) {
            $itemCount = '99+';
        }

        return [
            'id' => $this->id,
            'item_count' => $itemCount,
            'products' => empty($this->resource) ? [] : CartItemResource::collection($this->whenLoaded('cartItems')),
            'status' => boolval($this->status ?? 0),
            'sub_total' => \currency_format($this->sub_total, $this->currency, true),
            'tax_amount' => \currency_format($this->tax_amount, $this->currency, true),
            'ship_charge' => \currency_format($this->ship_charge, $this->currency, true),
            'total' => \currency_format($this->total, $this->currency, true),
            'total_amount' => $this->total,
        ];
    }
}
