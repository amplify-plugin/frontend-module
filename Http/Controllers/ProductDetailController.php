<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Sayt\Facade\Sayt;
use App\Http\Controllers\Controller;
use App\Models\Product;
use ErrorException;
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
        abort_unless(! customer_check() || customer(true)->can('shop.browse'), 403);

        $product = store()->productModel;

        if (! $product) {
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
}
