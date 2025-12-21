<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Backend\Models\ProductRelation;
use Amplify\System\Backend\Models\ProductRelationshipType;
use Amplify\System\Helpers\UtilityHelper;
use Amplify\System\Sayt\Facade\Sayt;
use App\Http\Controllers\Controller;
use ErrorException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductDetailController extends Controller
{
    use HasDynamicPage;

    /**
     * This method handle the product detail and page to render
     *
     * @throws ErrorException
     */
    public function __invoke(string $identifier, ?string $seo_path = null): string
    {
        abort_unless(!customer_check() || customer(true)->can('shop.browse'), 403);

        $product = store()->productModel;

        if (!$product) {
            abort(404, 'Product Unavailable');
        }

        try {
            $seo_path = trim(trim($seo_path), '/');

            store()->eaProductDetail = Sayt::storeProductDetail($identifier, $seo_path, ['return_skus' => request('return_skus', false)]);

            $this->setProductPreviewPage($product);

            return $this->render();
        } catch (NotFoundHttpException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            abort(500, $exception->getMessage());
        }
    }

    /**
     * set the product detail page style to load
     *
     * @throws ErrorException
     */
    private function setProductPreviewPage(?Product $product = null): void
    {
        if (($page = $product->singleProductPage) && config('amplify.pim.use_product_specific_detail_page', false)) {
            store()->dynamicPageModel = $page;
        } else {
            $this->loadPageByType('single_product');
        }
    }

    public function relatedProducts(Request $request, Product $product): string
    {
        $dbProduct = $product;
        $relationTypeId = $request->get('relation_type') ?? null;

        // Fetch all relations for this product once
        $relations = ProductRelation::where('product_id', $dbProduct->id)->get();

        // Get unique relation type IDs and their details
        $typeIds = $relations->pluck('product_relationship_type_id')->unique()->toArray();
        $relationTypes = ProductRelationshipType::whereIn('id', $typeIds)->get();

        // Default to the first available relation type if none selected
        if (empty($relationTypeId) && $relationTypes->isNotEmpty()) {
            $relationTypeId = $relationTypes->first()->id;
        }

        //Filter related product IDs from the already fetched relations
        $relatedIds = $relations->when($relationTypeId, fn($c) => $c->where('product_relationship_type_id', $relationTypeId))->pluck('related_product_id');

        // Load related products with attributes
        $perPage = $request->get('per_page', getPaginationLengths()[0]);

        $related = Product::with(['attributes'])
            ->whereIn('id', $relatedIds)
            ->paginate($perPage);

        if ($related->isEmpty()) {
            return '';
        }

        $items = $related
            ->map(function ($p) {
                return [
                    'item' => $p->product_code,
                    'uom' => $p->uom ?? 'EA',
                ];
            })
            ->toArray();

        $erpCustomer = null;
        $priceAvailability = collect();

        if (customer_check() || config('amplify.basic.enable_guest_pricing')) {
            $warehouses = ErpApi::getWarehouses([['enabled', '=', true]]);
            $warehouseString = $warehouses->pluck('WarehouseNumber')->implode(',');

            $erpCustomer = ErpApi::getCustomerDetail();
            if (!Str::contains($warehouseString, $erpCustomer->DefaultWarehouse)) {
                $warehouseString = "$warehouseString,{$erpCustomer->DefaultWarehouse}";
            }

            if (!empty($items)) {
                $priceAvailability = ErpApi::getProductPriceAvailability([
                    'items' => $items,
                    'warehouse' => $warehouseString,
                ]);
            }
        }

        $warehouse_codes = array_unique([$erpCustomer->DefaultWarehouse ?? null, customer()?->warehouse?->code ?? null, config('amplify.frontend.guest_checkout_warehouse')]);

        // Enrich related products with ERP data and DB fields
        $related->transform(function ($rp) use ($priceAvailability, $warehouse_codes) {
            $filtered = $priceAvailability->where('ItemNumber', $rp->product_code)->whereIn('WarehouseID', $warehouse_codes);
            $rp->ERP = $filtered->isNotEmpty() ? $filtered->first() : $priceAvailability->where('ItemNumber', $rp->product_code)->first();
            $rp->total_quantity_available = $priceAvailability->where('ItemNumber', $rp->product_code)->sum('QuantityAvailable');

            $rp->mpn = $rp->manufacturer ?? 'N/A';
            $rp->min_order_qty = $rp->min_order_qty ?? 1;
            $rp->qty_interval = $rp->qty_interval ?? 1;
            $rp->allow_back_order = $rp->allow_back_order ?? 0;
            $rp->default_document = $rp->default_document_type ?? null;
            $rp->assembled = $rp->vendornum == 3160;
            $rp->in_stock = $rp->vendornum == 3160 ? true : $rp->in_stock ?? false;
            $rp->is_ncnr = $rp->is_ncnr ?? false;
            $rp->ship_restriction = $rp->ship_restriction ?? false;
            $rp->pricing = true;
            $rp->specifications = $rp->attributes
                ->map(function ($item) {
                    $value = $item->pivot->attribute_value;
                    $value = UtilityHelper::isJson($value) ? json_decode($value, true)[config('app.locale')] ?? null : $value;

                    return (object) [
                        'name' => $item->name,
                        'value' => $value,
                    ];
                })
                ->toArray();

            return $rp;
        });

        try {
            // If this is an AJAX request, return only the product list partial
            // Return the same view (partial/full) so the UI can be replaced when switching types via AJAX
            return view('widget::product.tabs.related-products', [
                'relatedProducts' => $related,
                'product' => $dbProduct,
                'relationTypes' => $relationTypes,
                'selectedRelationType' => $relationTypeId,
            ])->render();
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
    }
}
