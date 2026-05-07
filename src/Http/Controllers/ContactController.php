<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Events\ContactLoggedIn;
use Amplify\Frontend\Helpers\CustomerHelper;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Http\Requests\FrontendContactRequest;
use Amplify\System\Backend\Models\Contact;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
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
        hasAccessOrFail('contact.list');

        $this->loadPageByType('contact');

        return $this->render();
    }

    /**
     * Show the form for creating a new contact.
     *
     * @throws \ErrorException
     */
    public function create(): string
    {
        hasAccessOrFail('contact.create');

        $this->loadPageByType('contact_create');

        return $this->render();
    }

    /**
     * Store a newly created contact in storage.
     */
    public function store(FrontendContactRequest $request)
    {
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
        hasAccessOrFail('contact.view');

        store()->contactModel = $contact;

        $this->loadPageByType('contact_detail');

        return $this->render();
    }

    /**
     * Show the form for editing the specified contact.
     *
     * @throws \ErrorException
     */
    public function edit(Contact $contact): string
    {
        hasAccessOrFail('contact.update');

        store()->contactModel = $contact;

        $this->loadPageByType('contact_edit');

        return $this->render();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FrontendContactRequest $request, Contact $contact)
    {
        $data = $request->all();

        $contact->update($data);

        $contact->syncRoles($request->input('roles'));

        return redirect(route('frontend.contacts.index'))->with('success', 'Contact updated successfully');
    }

    /**
     * Remove the specified contact from storage.
     */
    public function destroy(Contact $contact): JsonResponse
    {
        hasAccessOrFail('contact.delete');

        if ($contact->delete()) {
            return $this->apiResponse(true, __('Contact deleted successfully.'));
        }

        return $this->apiResponse(false, __('Failed to delete contact.'));
    }

    public function impersonate(Contact $contact, Request $request): RedirectResponse
    {
        hasAccessOrFail('contact.impersonate');

        Auth::guard(Contact::AUTH_GUARD)->logout();

        $request->session()->invalidate();

        Auth::guard(Contact::AUTH_GUARD)->login($contact);

        event(new ContactLoggedIn($contact));

        return redirect()->intended(CustomerHelper::afterLoggedRedirectTo());
    }
}
