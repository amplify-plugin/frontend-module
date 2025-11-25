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
            'total_price' => $this->total ?? 0,
            'products' => empty($this->resource) ? [] : CartItemResource::collection($this->whenLoaded('cartItems')),
            'status' => boolval($this->status ?? 0),
            'sub_total' => $this->sub_total ?? 0,
            'tax_amount' => $this->tax_amount ?? 0,
            'ship_charge' => $this->ship_charge ?? 0,
            'total' => $this->total ?? 0,
        ];
    }
}
