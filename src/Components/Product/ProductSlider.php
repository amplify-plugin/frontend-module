<?php

namespace Amplify\Frontend\Components\Product;

use Amplify\ErpApi\Collections\ProductPriceAvailabilityCollection;
use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\System\Backend\Models\OrderList;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Helpers\UtilityHelper;
use Amplify\System\Marketing\Models\MerchandisingZone;
use Amplify\System\Sayt\Classes\ItemRow;
use Amplify\System\Support\Money;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * @class ProductSlider
 */
class ProductSlider extends BaseComponent
{
    public $products;

    public $orderList;

    public $seopath;
    protected ProductPriceAvailabilityCollection $productPriceAvailability;
    
    /**
     * Create a new component instance.
     * @throws \ErrorException
     */
    public function __construct(
        public bool   $showTitle = true,
        public string $title = 'Product Slider',
        public int    $merchandisingZone = 1,
        public int    $productsLimit = 5,
        public bool   $showCartBtn = true,
        public string $cartButtonLabel = 'View Details',
        public bool   $smallButton = true,
        public bool   $showTopDiscountBadge = false,
        public bool   $showOrderList = true,
        public bool   $showNavigation = false,
        public int    $sliderItemGap = 15,
        public bool   $displayProductCode = false,
        public bool   $displayShortDescription = false,
        public bool   $displayManufacturer = false,
        public bool   $showGuestPrice = false,
        public bool   $showPrice = false,
    )
    {
        parent::__construct();
        $this->productPriceAvailability = new ProductPriceAvailabilityCollection();
        $this->products = collect();
        $this->orderList = collect();
        $this->prepareData();
    }

    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        return $this->products->isNotEmpty();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('widget::product.product-slider');
    }

    /**
     * @throws \ErrorException
     */
    public function prepareData()
    {
        $merchandisingZone = MerchandisingZone::find($this->merchandisingZone);

        if ($merchandisingZone) {

            if (customer_check()) {
                $this->orderList = OrderList::with('orderListItems')
                    ->whereCustomerId(customer()->getKey())->get();
            }

            $cacheKey = ErpApi::getCustomerDetail()->CustomerNumber . '-site-' . $merchandisingZone->easyask_key;

            $this->products = Cache::remember($cacheKey, HOUR, function () use ($merchandisingZone) {

                $result = \Sayt::marchProducts($merchandisingZone->easyask_key, ['per_page' => $this->productsLimit]);

                $this->seopath = $result->getCurrentSeoPath();

                $formattedProducts = collect();

                if ($this->showPrice) {
                    $this->productPriceAvailability = ErpApi::getProductPriceAvailability([
                        'items' => array_map(function (ItemRow $itemRow) {
                            return [
                                'item' => $itemRow->Sku_ProductCode ?? $itemRow->Product_Code,
                                'qty' => 1,
                                'uom' => $itemRow->UoM ?? 'EA',
                            ];
                        }, $result->getProducts()),
                        'warehouse' => ErpApi::getWarehouses()->pluck('WarehouseNumber')->implode(','),
                    ]);
                }

                foreach ($result->getProducts() as $product) {
                    $this->push($formattedProducts, $product);
                }

                $productIds = $formattedProducts->pluck('id');
                $productInfo = Product::select('products.id', 'manufacturers.name as manufacturers_name', 'products.short_description')
                    ->leftJoin('manufacturers', 'products.manufacturer_id', '=', 'manufacturers.id')
                    ->whereIn('products.id', $productIds)
                    ->get();

                $formattedProducts->map(function ($product) use ($productInfo) {
                    $pro = $productInfo->where('id', '=', $product->id)->first();
                    if (!empty($pro)) {
                        $product->manufacturer = $pro->manufacturers_name ?? '';
                        $product->short_description = $pro->local_short_description ?? '';
                    }

                    return $product;
                });

                return $formattedProducts;
            });
        }
    }

    /**
     * @param Collection $collection
     * @param ItemRow $product
     * @return void
     */
    public function push(&$collection, $product)
    {
        $item = new \stdClass;

        $item->id = $product->Amplify_Id;
        $item->product_code = $product->Product_Code;
        $item->cart_link = $this->productCartLink($product);
        $item->detail_link = $this->productDetailLink($product);
        $item->name = $this->productTitle($product);
        $item->image = $this->productImage($product);
        $item->price = floatval($this->productPrice($product));
        $item->uom = $item->UoM ?? 'EA';
        $item->old_price = ($product->Msrp ?? $product->Price)?->toFloat();
        $item->exists_in_favorite = false;
        $item->favorite_list_id = null;
        $item->pricing = true;
        $collection->push($item);
    }

    public function productCartLink($product): string
    {
        return customer_check() || config('amplify.basic.enable_guest_pricing') ? frontendSingleProductURL($product) : route('frontend.login');
    }

    public function productDetailLink($product): string
    {

        return frontendSingleProductURL($product, $this->seopath);
    }

    public function carouselOptions(): string
    {
        return json_encode([
            'lazyLoad' => true,
            'animateIn' => 'fadeIn',
            'animateOut' => 'fadeOut',
            'dots' => true,
            'nav' => $this->showNavigation,
            'margin' => $this->sliderItemGap,
            'responsive' => [
                '0' => ['items' => 1],
                '576' => ['items' => 2],
                '768' => ['items' => 3],
                '991' => ['items' => 4],
                '1200' => ['items' => 4],
            ],
        ]);
    }

    /**
     * @param ItemRow $product
     * @return string
     */
    public function productImage($product): string
    {
        $image = $product->Product_Image ?? '';

        if (!empty($product->Sku_List) && count($product->Sku_List) === 1) {
            if (!empty($product->Sku_ProductImage)) {
                $image = $product->Sku_ProductImage;
            }
        } elseif (!empty($product->Sku_Count) && !empty($product->Full_Sku_Count) && $product->Sku_Count > 1 && $product->Sku_Count !== $product->Full_Sku_Count) {
            $image = !empty($product->Sku_ProductImage) ? $product->Sku_ProductImage : $productImage ?? '';
        }

        return assets_image($image);
    }

    /**
     * @param ItemRow $product
     * @return string
     */
    public function productTitle($product)
    {
        $name = $product->Product_Name ?? '';

        if (!empty($product->Sku_Name)) {
            $name = $product->Sku_Name;
        }

        return $name;
    }

    /**
     * @param ItemRow $product
     * @return string
     */
    public function productPrice($product): string
    {
        $price = $product->Msrp ?? $product->Price ?? 0.00;

        if (ErpApi::enabled()) {
            $erpProduct = $this->productPriceAvailability
                ->firstWhere('ItemNumber', '=', $product->Sku_ProductCode ?? $product->Product_Code);
            if ($erpProduct) {
                $price = $erpProduct->Price ?? $erpProduct->ListPrice;
            }
        }

        return $price instanceof Money ? $price->toFloat() : (float)$price;
    }

    // private function productExistOnFavorite($id, &$product): void
    // {
    //     foreach ($this->orderList as $orderList) {
    //         if ($item = $orderList->orderListItems->firstWhere('product_id', $id)) {
    //             $product->exists_in_favorite = true;
    //             $product->favorite_list_id = $item->id;
    //         }
    //     }
    // }
}
