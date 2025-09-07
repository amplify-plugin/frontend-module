<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Http\Requests\ProfilePhotoUpdateRequest;
use Amplify\Frontend\Http\Requests\ProfileUpdateRequest;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Marketing\Models\Subscriber;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

class MyProfileController extends Controller
{
    use HasDynamicPage;

    /**
     * Display the customer profile.
     *
     * @throws \ErrorException
     */
    public function show(): string
    {
        $this->loadPageByType('customer_profile');

        return $this->render();
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $contact = customer(true);
        $contact->name = $request->input('name');
        $contact->phone = $request->input('phone');
        $contact->redirect_route = $request->input('redirect_route', 'dashboard');
        if ($request->filled('new_password')) {
            $contact->password = $request->input('new_password');
        }
        $contact->save();

        $is_subscriber = Subscriber::where('email', $contact->email)->first();
        if ($request->has('subscribe') && $is_subscriber) {
            $is_subscriber->status = 'subscribed';
            $is_subscriber->save();

        } elseif (! $request->has('subscribe') && $is_subscriber) {
            $is_subscriber->status = 'unsubscribed';
            $is_subscriber->save();
        } elseif ($request->has('subscribe') && ! $is_subscriber) {
            Subscriber::create([
                'name' => $contact->name,
                'email' => $contact->email,
            ]);
        }

        Session::flash('success', 'Profile Updated Successfully');

        return redirect()->back();
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function photoUpdate(ProfilePhotoUpdateRequest $request): RedirectResponse
    {
        $customer = customer(true);

        $customer->profile_image = $request->file('profile_image');

        if ($customer->save()) {
            Session::flash('success', 'Photo Updated Successfully');
        } else {
            Session::flash('error', 'Failed to update Photo');
        }

        return Redirect::back();
    }
}
