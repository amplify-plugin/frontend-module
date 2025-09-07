<?php

namespace Amplify\Frontend\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductSearchController extends Controller
{
    public function __invoke(Request $request)
    {
        return Product::filterProduct([
            'q' => $request->input('q'),
        ])->limit(50)->get();
    }
}
