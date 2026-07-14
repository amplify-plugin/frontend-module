<?php

namespace Amplify\Frontend\Http\Controllers\Auth;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\ErpApi\Jobs\ContactProfileSyncJob;
use Amplify\Frontend\Events\NewCustomerRegistered;
use Amplify\Frontend\Http\Requests\Auth\RegistrationRequest;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\Customer;
use Amplify\System\Backend\Models\CustomerRole;
use Amplify\System\Backend\Models\Event;
use Amplify\System\Backend\Models\IndustryClassification;
use Amplify\System\Factories\NotificationFactory;
use Amplify\System\Marketing\Models\Subscriber;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class NewCustomerRegisterController extends Controller
{
    use HasDynamicPage;

    /**
     * Handle an incoming registration request.
     *
     * @throws Exception|\Throwable
     */
    public function __invoke(RegistrationRequest $request): RedirectResponse
    {
        $industry = IndustryClassification::find($request->input('industry_classification_id'));

        $customerAddressPayload = [
            'CustomerName' => $request->input('company_name'),
            'CustomerEmail' => $request->input('email'),
            'CustomerPhone' => $request->input('phone_number'),
            'CustomerPhoneExt' => $request->input('phone_extension'),
            'WrittenIndustry' => $industry ? $industry->name : null,
            'DefaultShipTo' => $request->input('address_name'),
            'CustomerAddress1' => $request->input('address_1'),
            'CustomerAddress2' => $request->input('address_2'),
            'CustomerAddress3' => $request->input('address_3'),
            'CustomerZipCode' => $request->input('zip_code'),
            'CustomerCity' => $request->input('city'),
            'CustomerState' => $request->input('state'),
            'CustomerCountry' => $request->input('country_code'),
        ];

        DB::beginTransaction();
        try {

            // Create Customer & Address
            $customer = $this->createCustomer($customerAddressPayload);

            // Create Contact
            $contact = $customer->contacts()->create([
                'account_title_id' => $request->input('contact_account_title'),
                'name' => $request->input('name'),
                'phone' => $request->input('phone_number', $erpData['CustomerPhone'] ?? null),
                'phone_ext' => $request->input('phone_extension', $erpData['CustomerPhoneExt'] ?? null),
                'password' => $request->input('password'),
                'email' => $request->input('email'),
                'login_id' => $request->input('email'),
                'is_admin' => $customer->wasRecentlyCreated,
                'enabled' => config('amplify.security.skip_contact_approval', false),
                'customer_address_id' => null,
                'warehouse_id' => $customer->warehouse_id ?? null,
                'active_customer_id' => $customer->getKey(),
                'order_limit' => 0,
                'daily_budget_limit' => 0,
                'monthly_budget_limit' => 0,
            ]);

            DB::commit();

            $this->handlePostRegistrationForNewRetailCustomer($request, $customer, $contact);

            $request->session()->flash('customerSignedUp', true);

            return redirect()->to('/')
                ->with([
                    'alert' => true,
                    'success' => __(config('amplify.messages.customer_registration_success')),
                ]);

        } catch (Exception $exception) {

            Log::error($exception);

            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with([
                    'alert' => true,
                    'message' => $exception->getMessage(),
                    'error' => __('Registration request failed. Please try again later.'),
                ]);
        }
    }

    private function createCustomer(array $attributes): Customer
    {
        $industryName = $attributes['WrittenIndustry'] ?? null;
        $industryClassification = $industryName
            ? IndustryClassification::where('name', $industryName)->first()
            : null;

        // Create Customer
        $customer = Customer::create([
            'customer_code' => $attributes['CustomerNumber'] ?? null,
            'customer_name' => $attributes['CustomerName'] ?? null,
            'email' => $attributes['CustomerEmail'] ?? null,
            'phone' => $attributes['CustomerPhone'] ?? null,
            'phone_ext' => $attributes['CustomerPhoneExt'] ?? null,
            'approved' => config('amplify.security.skip_contact_approval', false),
            'customer_type' => 'Retail',
            'industry_classification_id' => $industryClassification->id ?? null,
            'address_1' => $attributes['CustomerAddress1'] ?? null,
            'address_2' => $attributes['CustomerAddress2'] ?? null,
            'address_3' => $attributes['CustomerAddress3'] ?? null,
            'zip_code' => $attributes['CustomerZipCode'] ?? null,
            'city' => $attributes['CustomerCity'] ?? null,
            'country_code' => $attributes['CustomerCountry'] ?? null,
            'state' => $attributes['CustomerState'] ?? null,
        ]);

        if (empty($attributes['CustomerNumber'])
            && config('amplify.erp.auto_create_cash_customer', false)) {

            // Create customer in ERP if not exists
            $erpCustomer = ErpApi::createCustomer([
                'template_customer_number' => config('amplify.frontend.guest_default'),
                'email_address' => $customer->email,
                'phone_number' => $customer->phone,
                'phone_ext' => $customer->phone_ext,
                'customer_name' => $customer->customer_name,
                'contact' => null,
                'address_1' => $customer->address_1,
                'address_2' => $customer->address_2,
                'address_3' => $customer->address_3,
                'city' => $customer->city,
                'state' => $customer->state,
                'zip_code' => $customer->zip_code,
                'country_code' => $customer->country_code,
                'branch' => null,
                'customer_industry' => $customer->industryClassification?->name,
            ]);

            // Handle ERP response
            if (! empty($erpCustomer->Message)) {
                throw new Exception($erpCustomer->Message);
            }

            if ($erpCustomer->CustomerNumber == null) {
                throw new Exception('ERP customer creation failed for customer ID: '.$customer->id);
            }

            // Update customer code with ERP Customer Number
            $customer->customer_code = $erpCustomer->CustomerNumber;
            $customer->save();
        }

        return $customer;
    }

    private function handlePostRegistrationForNewRetailCustomer($request, $customer, $contact): void
    {
        if ($request->filled('newsletter') && $request->input('newsletter') == 'yes') {
            if ($alreadySubscriber = Subscriber::whereEmail($request->input('email'))->first()) {
                $alreadySubscriber->increment('attempts');
            } else {
                Subscriber::create(['email' => $request->input('email')]);
            }
        }

        NotificationFactory::call(Event::REGISTRATION_REQUEST_RECEIVED, [
            'customer_id' => $customer->id,
            'contact_id' => $contact->id,
        ]);

        event(new NewCustomerRegistered($customer));

        if (config('amplify.security.skip_new_retail_customer_approval', false)) {
            NotificationFactory::call(Event::REGISTRATION_REQUEST_ACCEPTED,
                ['contact_id' => $contact->id, 'customer_id' => $customer->id]);

        } else {
            if(config('amplify.security.new_retail_customer_verification_method', 'backend') == 'email') {
                NotificationFactory::call(Event::CONTACT_ACCOUNT_REQUEST_VERIFICATION, [
                    'contact_id' => $contact->id,
                    'type' => Contact::NEW_RETAIL_CUSTOMER_VERIFICATION,
                ]);
            }
        }

         if(config('amplify.basic.is_permission_system_enabled')){
            $defaultRole =CustomerRole::where('is_default', true)
            ->where('guard_name', Contact::AUTH_GUARD)
            ->when(config('permission.teams'), fn($q) => $q->where('team_id', $contact->customer_id))->first();

            if($defaultRole){
                $contact->assignRole($defaultRole);
            }
        }


        if (config('amplify.erp.auto_create_contact')) {
            ContactProfileSyncJob::dispatch($contact->toArray());
        }
    }

    public function verifyEmail(string $id, string $hash, Request $request): RedirectResponse
    {
        $contact = Contact::findOrFail($id);

        abort_if(! Hash::check($contact->otp, base64_decode($hash)), 404, 'The verification link is invalid or expired. Please contact system administrator.');

        abort_if(! $contact->update(['enabled' => true, 'enabled_at' => now(), 'otp' => null]), 500, 'Email verification failed. Please try again later or contact System Administrator.');

        $contact->customer->update(['approved' => true]);

        NotificationFactory::call(Event::REGISTRATION_REQUEST_ACCEPTED,
            ['customer_id' => $contact->customer_id, 'contact_id' => $contact->id]);

        return redirect()->to(frontendHomeURL().'/login?verified=1')
            ->with('success', __('Email verification successful. Please sign in with your credentials.'))
            ->with('alert', true);
    }
}
