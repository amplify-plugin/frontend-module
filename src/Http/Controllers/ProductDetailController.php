<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Backend\Models\ProductRelation;
use Amplify\System\Backend\Models\ProductRelationshipType;
use Amplify\System\Helpers\UtilityHelper;
use Amplify\System\Sayt\Classes\ItemRow;
use Amplify\System\Sayt\Facade\Sayt;
use App\Http\Controllers\Controller;
use ErrorException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
    public function __invoke(string $identifier, ?string $slug = null): string
    {
        abort_unless(! customer_check() || customer(true)->can('shop.browse'), 403);

        $product = store()->productModel;

        if (! $product) {
            abort(404, 'Product Unavailable');
        }

        try {

            $eaKey = $product instanceof ItemRow ? $product->Amplify_Id : $product->id;

            store()->eaProductDetail = Sayt::storeProductDetail($eaKey, \request('ref'), ['return_skus' => request('return_skus', false)]);

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

    public function relatedProducts(Request $request, Product $product)
    {
        $dbProduct = $product;
        $relationTypeIds = collect(explode(',', (string) $request->get('relation_type', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values();

        // Fetch all relations for this product once
        $relations = ProductRelation::where('product_id', $dbProduct->id)->get();

        // Get unique relation type IDs and their details
        $typeIds = $relations->pluck('product_relationship_type_id')->unique()->toArray();
        $relationTypes = ProductRelationshipType::whereIn('id', $typeIds)->get();

        // Default to the first available relation type if none selected
        if ($relationTypeIds->isEmpty() && $relationTypes->isNotEmpty()) {
            $relationTypeIds = collect([$relationTypes->first()->id]);
        }

        // Filter related product IDs from the already fetched relations
        $relatedIds = $relations
            ->when($relationTypeIds->isNotEmpty(), fn ($c) => $c->whereIn('product_relationship_type_id', $relationTypeIds->all()))
            ->pluck('related_product_id')
            ->unique()
            ->values();

        // Load related products with attributes
        $perPage = $request->get('per_page', getPaginationLengths()[0]);

        $uniqueRelatedIds = Product::query()
            ->whereIn('id', $relatedIds)
            ->selectRaw('MAX(id) as id')
            ->groupBy('product_code')
            ->pluck('id');

        /** @var LengthAwarePaginator $related */
        $related = Product::with(['attributes'])
            ->whereIn('id', $uniqueRelatedIds)
            ->paginate($perPage);

        if ($related->isEmpty()) {
            return '';
        }

        $items = $related->getCollection()
            ->map(function (Product $p) {
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
            if (! Str::contains($warehouseString, $erpCustomer->DefaultWarehouse)) {
                $warehouseString = "$warehouseString,{$erpCustomer->DefaultWarehouse}";
            }

            if (! empty($items)) {
                $priceAvailability = ErpApi::getProductPriceAvailability([
                    'items' => $items,
                    'warehouse' => $warehouseString,
                ]);
            }
        }

        $warehouse_codes = array_unique([$erpCustomer->DefaultWarehouse ?? null, customer()?->warehouse?->code ?? null, config('amplify.frontend.guest_checkout_warehouse')]);

        // Enrich related products with ERP data and DB fields
        $related->setCollection($related->getCollection()->transform(function (Product $rp) use ($priceAvailability, $warehouse_codes) {
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
            $rp->specifications = collect($rp->attributes)
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
        }));

        try {
            // If this is an AJAX request, return only the product list partial
            // Return the same view (partial/full) so the UI can be replaced when switching types via AJAX
            return $this->apiResponse(true, '', 200, [
                'html' => view('widget::product.tabs.related-product-view', [
                    'relatedProducts' => $related,
                    'product' => $dbProduct,
                    'relationTypes' => $relationTypes,
                    'selectedRelationType' => $relationTypeIds->implode(','),
                ])->render(),
            ]);
        } catch (\Exception $e) {
            return $this->apiResponse(false, $e->getMessage(), 500);
        }
    }
}
