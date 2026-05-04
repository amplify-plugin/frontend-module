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


        return [
            'id' => $this->id,
            'item_count' => cart_count_badge($this),
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
