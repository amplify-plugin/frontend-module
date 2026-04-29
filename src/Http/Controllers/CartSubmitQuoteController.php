<?php

namespace Amplify\Frontend\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartSubmitQuoteController
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Quotation submission is under construction.',
            'redirect_to' => null,
        ], 501);
    }
}
