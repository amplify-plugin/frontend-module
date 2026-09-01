@php

    $diff = null;
    $msrp =  $product?->Msrp?->toFloat() ?? null;
    $price =  $product?->ERP?->Price ?? null;

    if ($price != null && $msrp != null) {
        $diff = (abs($msrp - $price) * 100)/$msrp;
    }

@endphp
<div {!! $htmlAttributes !!}>
    <div class="row">
        <div class="col-md-4 col-10 mx-auto">
            <x-product.product-gallery :image="$product?->product_image">
                <x-slot:before>
                    @if(is_integer($diff))
                        <div class="product-badge product-discount text-danger">
                            {{ \Illuminate\Support\Number::percentage($diff) }} Off
                        </div>
                    @endif
                </x-slot:before>
            </x-product.product-gallery>
        </div>
        <div class="col-md-8">
            <x-product.item-number :product="$product" format="{product_code}" element="h4"
                                   class="text-primary font-weight-bold my-3"/>

            <h2 class="product-title">{{ $product->Product_Name ?? '' }}</h2>

            <x-product-manufacture-image :product="$product"/>

            <x-product.price
                    element="div"
                    class="w-100 product-price"
                    :product="$product"
                    :value="$product->ERP?->Price"
                    :uom="$product->ERP?->UnitOfMeasure ?? 'EA'"
                    :std-price="$product->Msrp->toFloat()"/>

            <x-product.short-description :content="$product->short_description ?? ''" :lines="2"/>

            @if(!empty($eaAttributes))
                <div class="row margin-top-1x">
                    @foreach($eaAttributes as $attribute)
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label for="size">{{ $attribute->getName() }}</label>
                                <select class="form-control" id="size">
                                    <option selected value="">Choose {{ $attribute->getName() }}</option>
                                    @foreach($attribute->getFullList() as $attributeValue)
                                        <option value="">{{ $attributeValue->getDisplayName() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <hr class="mb-3">

            <div class="d-grid d-md-flex justify-content-between gap-2 product-card border-0 p-0">
                <x-product-social-media-link :product="$product"/>
                <div class="product-buttons">
                    <x-product.quick-action :product="$product"/>
                </div>
            </div>
        </div>
    </div>

    <x-product.information-tabs
            :product="$product"
            :header-class="''"
            :tabs="[
                'description',
                 'sku' => ['label' => 'Products'],
                'feature' => ['label' => 'Features', 'style' => 'list'],
                'specification' => ['label' => 'Specifications', 'style' => 'list'],
                'document',
                'related-products' => ['label' => 'Related'],
            ]">
        <x-slot:before>
            <svg width="0" height="0">
                <defs>
                    <clipPath id="tabClip" clipPathUnits="objectBoundingBox">
                        <path d="
                            M-0.01,1
                            C0.08,0.9 0.08,0 0.17,0
                            H0.83
                            C0.92,0 0.92,0.9 1.01,1
                        "/>
                    </clipPath>
                    <clipPath id="tabClip2" clipPathUnits="objectBoundingBox">
                        <path d="
                            M0,1
                            C0.08,0.9 0.08,0 0.17,0
                            H0.83
                            C0.92,0 0.92,0.9 1,1
            "/>

                    </clipPath>
                </defs>
            </svg>
        </x-slot:before>
    </x-product.information-tabs>
</div>
