<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\System\Backend\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProductSearchController extends Controller
{
    public function __invoke(Request $request)
    {
        return Product::filterProduct([
            'q' => $request->input('q'),
        ])->limit(50)->get();
    }
}
