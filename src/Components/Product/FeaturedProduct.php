<?php

namespace Amplify\Frontend\Components\Product;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\System\Helpers\UtilityHelper;
use Amplify\System\Marketing\Models\MerchandisingZone;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

/**
 * @class FeaturedProduct
 */
class FeaturedProduct extends BaseComponent
{
    public $products;

    private $orderList;

    private $seopath;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public bool $showTitle = true,
        public string $title = 'Featured Products',
        private int $merchandisingZone = 1,
        private int $productsLimit = 5,
        public bool $showCartButton = false,
        public bool $showPrice = false,
        public bool $showDiscountPrice = false,
    ) {
        parent::__construct();
        $this->products = collect();
        $this->prepareData();
    }

    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        return ! $this->products->isEmpty();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('widget::product.featured-product');
    }

    private function prepareData(): void
    {
        $merchandisingZone = MerchandisingZone::find($this->merchandisingZone);

        if ($merchandisingZone) {

            $this->products = Cache::remember('site-feature-product-'.$merchandisingZone->easyask_key, HOUR, function () use ($merchandisingZone) {

                $result = \Sayt::marchProducts($merchandisingZone->easyask_key, ['per_page' => $this->productsLimit]);

                $this->seopath = $result->getCurrentSeoPath();

                $formattedProducts = collect();

                foreach ($result->getProducts() as $product) {
                    $this->push($formattedProducts, $product);
                }

                return $formattedProducts;
            });
        }
    }

    private function push(&$collection, $product)
    {
        $item = new \stdClass;

        $item->id = $product->Product_Id;
        $item->detail_link = $this->productDetailLink($product);
        $item->name = $this->productTitle($product);
        $item->image = $this->productImage($product);
        $item->price = $this->productPrice($product);
        $item->old_price = $item->price;
        $item->discount = '';
        $collection->push($item);
    }

    private function productCartLink($product): string
    {
        return customer_check() ? frontendSingleProductURL($product) : route('frontend.login');
    }

    private function productDetailLink($product): string
    {
        return frontendSingleProductURL($product, $this->seopath);
    }

    private function productImage($product): string
    {
        $image = $product->Product_Image ?? '';

        if (! empty($product->Sku_List) && count(json_decode($product->Sku_List, true)) === 1) {
            if (! empty($product->Sku_ProductImage)) {
                $image = $product->Sku_ProductImage;
            }
        } elseif (! empty($product->Sku_Count) && ! empty($product->Full_Sku_Count) && $product->Sku_Count > 1 && $product->Sku_Count !== $product->Full_Sku_Count) {
            $image = ! empty($product->Sku_ProductImage) ? $product->Sku_ProductImage : $productImage ?? '';
        }

        return assets_image($image);
    }

    private function productTitle($product)
    {
        $name = $product->Product_Name ?? '';

        if (! empty($product->Sku_Name)) {
            $name = $product->Sku_Name;
        }

        return $name;
    }

    private function productPrice($product): float|int|string
    {
        $price = isset($product->Msrp) ? $product->Msrp : $product->Price ?? 0.00;
        if (ErpApi::enabled() && has_erp_customer()) {
            $erpProduct = ErpApi::getProductPriceAvailability([
                'items' => [['item' => $product->Sku_ProductCode ?? $product->Product_Code, 'qty' => 1]],
            ])->first();

            if ($erpProduct) {
                $price = $erpProduct->Price ?? $erpProduct->ListPrice;
            }
        }

        return price_format($price);
    }
}
