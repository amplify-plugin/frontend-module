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
            'products' => CartItemResource::collection($this->whenLoaded('cartItems')),
            'total_price' => $this->total ?? 0,
        ];
    }
}
