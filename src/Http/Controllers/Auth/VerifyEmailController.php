<?php

namespace Amplify\Frontend\Http\Controllers\Auth;

use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\Event;
use Amplify\System\Factories\NotificationFactory;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(string $id, string $hash, Request $request): RedirectResponse
    {
        $contact = Contact::findOrFail($id);

        abort_if(! Hash::check($contact->otp, base64_decode($hash)), 404, 'The verification link is invalid or expired. Please contact system administrator.');

        abort_if(! $contact->update(['enabled' => true, 'enabled_at' => now(), 'otp' => null]), 500, 'Email verification failed. Please try again later or contact System Administrator.');

        $contact->customer->update(['approved' => true]);

        NotificationFactory::call(Event::REGISTRATION_REQUEST_ACCEPTED,
            ['customer_id' => $contact->customer_id, 'contact_id' => $contact->id]);

        return redirect()->to(frontendHomeURL().'/login?verified=1')
            ->with('success', __('Email verification successful. Please sign in with your credentials.'));
    }
}
