<?php

namespace Amplify\Frontend\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_code' => $this->product_code,
            'product_name' => $this->product_name,
            'short_description' => ! empty($this->product) ?
                                        $this->product->local_short_description : '',
            'manufacturer_name' => ! empty($this->product->manufacturerRelation) ?
                                        $this->product->manufacturerRelation->name : '',
            'qty' => (float) $this->quantity,
            'price' => \currency_format($this->unitprice, $this->cart->currency, true),
            'subtotal' => \currency_format($this->subtotal, $this->cart->currency, true),
            'product_warehouse_code' => $this->product_warehouse_code,
            'warehouse_id' => $this->warehouse_id ?? null,
            'warehouse_name' => $this->warehouse->name ?? null,
            'address_id' => $this->address_id,
            'source_type' => $this->source_type,
            'source' => $this->source,
            'expiry_date' => $this->expiry_date,
            'additional_info' => $this->additional_info,
            'product_image' => $this->product_image,
            'uom' => unit_of_measurement($this->uom ?? 'EA', 'Each'),
            'url' => $this->product ? frontendSingleProductURL($this->product) : '#',
            'product_back_order' => $this->product_back_order,
            'note' => $this->getSourceMessage($this->source, $this->source_type, $this->expiry_date, $this->additional_info),
            'custom_item_info' => $this->source_type == 'custom_item' ? json_decode($this->additional_info) : '',
            'ncnr_msg' => (isset($this->additional_info['is_ncnr']))
                ? $this->additional_info['is_ncnr'] ? '<span class="text-warning">It is a <b>Non-Cancelable, Non-Returnable</b> item</span>' : ''
                : '',
            'ship_restriction' => $this->additional_info['ship_restriction'] ?? '',
            'error' => $this->additional_info['error'] ?? '',
        ];
    }

    public function getSourceMessage($source = null, $source_type = null, $source_expire = null, $additional_info = []): ?string
    {
        switch ($source_type) {
            case 'CUSTOM_ITEM' :
                return '<span class="text-success font-italic"><i class="icon-file-subtract mr-1"></i>Specification: #'.($additional_info['OrderSpec'] ?? '').'</span>';
            case 'PROMO':
                $now = CarbonImmutable::now();
                $expiry_date = CarbonImmutable::now();
                if ($expiry_date < $now) {
                    return '<span class="text-danger font-italic"><i class="icon-bell mr-1"></i>Campaign <code class="font-weight-bold">'.$source.'</code> is expired.';
                } else {
                    return '<span class="text-warning font-italic"><i class="icon-bell mr-1"></i>Source: Campaign <code class="font-weight-bold">'.$source.'</code>, Expires: '.carbon_date($expiry_date);
                }
            case 'QUOTE':
                $now = CarbonImmutable::now();
                $expiry_date = CarbonImmutable::now();
                if ($expiry_date < $now) {
                    return '<span class="text-danger font-italic"><i class="icon-clock mr-1"></i>Quotation <code class="font-weight-bold">'.$source.'</code> is expired.';
                } else {
                    return '<span class="text-warning font-italic"><i class="icon-clock mr-1"></i>Source: Quotation <code class="font-weight-bold">'.$source.'</code>, Expires: '.carbon_date($expiry_date);
                }
            default:
                return '';
        }
    }
}
