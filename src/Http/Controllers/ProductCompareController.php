<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Services\ProductCompareService;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Cms\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class ProductCompareController extends Controller
{
    use HasDynamicPage;

    public function __construct(Request $request)
    {
        if (optional($request->route())->getName() !== 'frontend.product-compare.page') {
            return;
        }

        $page = Page::published()->where('slug', $this->comparePageSlug())->first();

        if ($page && ! empty($page->middleware)) {
            $this->middleware($page->middleware);
        }
    }

    public function page(ProductCompareService $compare): string
    {
        if (! $compare->enabled()) {
            abort(404, 'Product comparison is not available.');
        }

        $slug = $this->comparePageSlug();
        $page = Page::published()->where('slug', $slug)->first();

        if (! $page) {
            $page = new Page([
                'name' => 'Product Comparison',
                'title' => 'Product Comparison',
                'slug' => $slug,
                'page_type' => 'static_page',
                'content' => '<x-product-comparison-list />',
                'is_published' => true,
                'has_footer' => true,
                'has_breadcrumb' => false,
                'styles' => '',
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

        store()->dynamicPageModel = $page;

        return $this->render();
    }

    private function comparePageSlug(): string
    {
        return trim((string) config('amplify.frontend.product_compare_page', '/product/compare'), '/');
    }

    public function __invoke(Request $request, ProductCompareService $compare): JsonResponse
    {
        try {
            if (! $compare->enabled()) {
                return $this->apiResponse(false, 'Product comparison is not available.', 404);
            }

            if (! customer_check()) {
                return $this->apiResponse(false, 'You need to be logged in to compare products.', 403);
            }

            hasAccessOrFail('product-compare.manage');

            $validator = Validator::make($request->all(), [
                'product' => 'nullable|integer',
                'action' => 'nullable|string|in:add,remove,clear,list',
            ]);

            if ($validator->fails()) {
                return $this->apiResponse(false, (string) $validator->errors()->first(), 422);
            }

            $action = $request->input('action', 'list');
            $productId = (int) $request->input('product');

            $result = match ($action) {
                'add' => $compare->add($productId),
                'remove' => $compare->remove($productId),
                'clear' => $compare->clear(),
                default => $compare->list(),
            };

            return $this->apiResponse($result['success'], $result['message'], 200, [
                'count' => $result['count'],
                'max' => $result['max'],
                'state' => $result['state'],
                'items' => $result['items'],
                'data' => [
                    'count' => $result['count'],
                    'html' => view('widget::product.comparison.dropdown', [
                        'items' => $result['items'],
                        'compareUrl' => $compare->pageUrl(),
                    ])->render(),
                ],
            ]);
        } catch (\Throwable $exception) {
            return $this->apiResponse(false, $exception->getMessage(), 500);
        }
    }
}
