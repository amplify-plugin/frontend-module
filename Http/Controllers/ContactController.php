<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Events\ContactLoggedIn;
use Amplify\Frontend\Helpers\CustomerHelper;
use Amplify\Frontend\Traits\HasDynamicPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\FrontendContactRequest;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    use HasDynamicPage;

    /**
     * Display a listing of the contact.
     *
     * @throws \ErrorException
     */
    public function index(): string
    {
        $this->loadPageByType('contact');
        if (! customer(true)->can('contact-management.list')) {
            abort(403);
        }

        return $this->render();
    }

    /**
     * Show the form for creating a new contact.
     *
     * @throws \ErrorException
     */
    public function create(): string
    {
        $this->loadPageByType('contact_create');
        if (! customer(true)->can('contact-management.add')) {
            abort(403);
        }

        return $this->render();
    }

    /**
     * Store a newly created contact in storage.
     */
    public function store(FrontendContactRequest $request)
    {
        if (! customer(true)->can('contact-management.add')) {
            abort(403);
        }
        $data = $request->all();

        $contact = Contact::create($data);

        $contact->assignRole($request->input('roles'));

        return redirect()->route('frontend.contacts.index')->with('success', 'Contact created successfully');
    }

    /**
     * Display the specified contact.
     *
     * @throws \ErrorException
     */
    public function show(Contact $contact): string
    {
        store()->contactModel = $contact;

        $this->loadPageByType('contact_detail');
        if (! customer(true)->can('contact-management.view')) {
            abort(403);
        }

        return $this->render();
    }

    /**
     * Show the form for editing the specified contact.
     *
     * @throws \ErrorException
     */
    public function edit(Contact $contact): string
    {
        if (! customer(true)->can('contact-management.update')) {
            abort(403);
        }
        store()->contactModel = $contact;

        $this->loadPageByType('contact_edit');

        return $this->render();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FrontendContactRequest $request, Contact $contact)
    {
        if (! customer(true)->can('contact-management.update')) {
            abort(403);
        }

        $data = $request->all();

        $contact->update($data);

        $contact->syncRoles($request->input('roles'));

        return redirect(route('frontend.contacts.index'))->with('success', 'Contact updated successfully');
    }

    /**
     * Remove the specified contact from storage.
     */
    public function destroy(Contact $contact)
    {
        if (! customer(true)->can('contact-management.remove')) {
            abort(403);
        }
        $contact->delete();

        return back()->with('success', 'Contact deleted successfully');
    }

    public function impersonate(Contact $contact, Request $request): RedirectResponse
    {
        Auth::guard(Contact::AUTH_GUARD)->logout();

        $request->session()->invalidate();

        Auth::guard(Contact::AUTH_GUARD)->login($contact);

        event(new ContactLoggedIn($contact));

        return redirect()->intended(CustomerHelper::afterLoggedRedirectTo());
    }
}
