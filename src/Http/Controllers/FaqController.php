<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Models\Faq;
use Amplify\System\Backend\Models\FaqCategory;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;

class FaqController extends Controller
{
    use HasDynamicPage;

    /**
     * Display a listing of the resource.
     *
     * @throws \ErrorException
     */
    public function __invoke(?string $faqCategorySlug = null): string
    {
        $this->loadPageByType('faq');

        $faqCategory = FaqCategory::when(
            $faqCategorySlug != null, fn (Builder $builder) => $builder->whereSlug($faqCategorySlug)
        )->get()->first();

        if (! $faqCategory) {
            abort(404, 'FAQ Topic not found');
        }

        store()->faqCategoryModel = $faqCategory;

        return $this->render();
    }

    public function statsCount(Faq $faq, string $countFlag)
    {
        if ($countFlag == 'useful') {
            $faq->useful = $faq->useful + 1;
        }

        if ($countFlag == 'not-useful') {
            $faq->not_useful = $faq->not_useful + 1;
        }

        if ($countFlag == 'no-views') {
            $faq->no_views = $faq->no_views + 1;
        }

        $faq->save();

        return response(['status' => true]);
    }
}
