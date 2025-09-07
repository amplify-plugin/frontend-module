<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\System\Marketing\Models\Subscriber;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class NewsletterSubscriptionController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $is_subscriber = Subscriber::whereEmail($request->input('email'))->first();

        if ($is_subscriber) {
            $is_subscriber->increment('attempts');
        } else {
            Subscriber::create($request->all());
        }

        Session::flash('success', 'Subscribe Successfully');

        return redirect()->back();
    }
}
