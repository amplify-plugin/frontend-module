<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Traits\ProductDetailTrait;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cookie;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ShopSearchController extends Controller
{
    use HasDynamicPage;
    use ProductDetailTrait;

    /**
     * Display a listing of the resource.
     *
     * @throws \ErrorException
     */
    public function __invoke(?string $query = null): RedirectResponse|string
    {
        abort_unless(! customer_check() || customer(true)->can('shop.browse'), 403);

        $eaProductData = store()->eaProductsData;
        $products = $eaProductData->getProducts();
        $searchMessage = $eaProductData->getMessage();

        if (! empty($products) && count($products) === 1 && empty($searchMessage) && !request()->filled('page')){
            $seoPath = $eaProductData->getCurrentSeoPath();
            $firstProduct = array_shift($products);

            if (! empty($firstProduct->Sku_Id) && ! empty($skuId = explode('-', $firstProduct->Sku_Id)[1])) {
                $dbProduct = Product::find($skuId);
                $firstProduct->Product_Id = $firstProduct->Amplify_Id = $skuId;
                $firstProduct->Product_Slug = $dbProduct->product_slug;
            }

            return \redirect(frontendSingleProductURL($firstProduct, $seoPath));

        }

        $this->loadPageByType('shop');

        Cookie::queue('showView',  active_shop_view(), MONTH/60, '/'.config('amplify.frontend.shop_page_prefix'));

        return $this->render();
    }

    private function determineSearchMode($query = null) {}

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ErrorException
     */
    public function getQuickView($id): JsonResponse
    {
        abort_unless(! customer_check() || customer(true)->can('shop.browse'), 403);
        $response = $this->getProductFromEasyAsk($id, 'All');
        abort_if(isset($response['noResultsMessage']), 404);

        $Product = array_shift($response['products']->items);
        $Product->skuList = json_decode($Product?->Sku_List, true) ?? [];

        if (has_erp_customer()) {
            $Product->erpProductList = ErpApi::getProductPriceAvailability([
                'items' => array_map(fn ($item) => ['item' => $item[1]], $Product->skuList),
            ]);
        }

        return response()->json([
            'html' => Blade::render('<x-product-sku-table :product="$Product"/>', compact('Product')),
        ]);
    }

    public function getWarehouseSelectionView(Request $request, $code): JsonResponse
    {
        $activeWarehouse = $request->input('warehouse');

        return response()->json([
            'html' => Blade::render('<x-warehouse-selection :product-code="$code" :active-warehouse="$activeWarehouse"/>', compact('code', 'activeWarehouse')),
        ]);
    }
}
