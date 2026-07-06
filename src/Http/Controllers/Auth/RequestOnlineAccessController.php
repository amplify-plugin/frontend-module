<?php

namespace Amplify\Frontend\Http\Controllers\Auth;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\ErpApi\Jobs\ContactProfileSyncJob;
use Amplify\Frontend\Http\Requests\Auth\ContactAccountRequest;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\Customer;
use Amplify\System\Backend\Models\CustomerRole;
use Amplify\System\Backend\Models\Event;
use Amplify\System\Backend\Models\IndustryClassification;
use Amplify\System\Factories\NotificationFactory;
use Amplify\System\Marketing\Models\Subscriber;
use Illuminate\Routing\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequestOnlineAccessController extends Controller
{
    use HasDynamicPage;

    /**
     * @return RedirectResponse
     *
     * @throws \Throwable
     */
    public function __invoke(ContactAccountRequest $request)
    {
        DB::beginTransaction();

        try {

            [$customer, $address] = $this->getExistingCustomer($request);

            // Create Contact
            $contact = $customer->contacts()->create([
                'account_title_id' => $request->input('contact_account_title'),
                'name' => $request->input('contact_name'),
                'phone' => $request->input('contact_phone_number', $erpData['CustomerPhone'] ?? null),
                'phone_ext' => $request->input('contact_phone_extension'),
                'password' => $request->input('contact_password'),
                'email' => $request->input('contact_email'),
                'login_id' => $request->input('contact_email'),
                'is_admin' => $customer->wasRecentlyCreated,
                'enabled' => config('amplify.security.skip_request_account_approval', false),
                'customer_address_id' => $address?->id ?? null,
                'warehouse_id' => $customer->warehouse_id ?? null,
                'active_customer_id' => $customer->getKey(),
                'order_limit' => 0,
                'daily_budget_limit' => 0,
                'monthly_budget_limit' => 0,
            ]);

            // Create Contact Login
            $contact->contactLogins()->create([
                'contact_id' => $contact->id,
                'customer_id' => $customer->id,
                'warehouse_id' => $customer->warehouse_id ?? null,
                'customer_address_id' => $address?->id ?? null,
                'ship_to_name' => $address?->address_name ?? null,
            ]);

            DB::commit();

            $request->session()->flash('contactSignedUp', true);

            // Newsletter + Notification
            $this->handlePostRegistrationForRequestAccount($request, $contact);

            return redirect()->to('/')
                ->with([
                    'alert' => true,
                    'success' => __(config('amplify.messages.request_online_access_success')),
                ]);

        } catch (Exception $exception) {
            DB::rollBack();

            Log::error($exception);

            $code = (int)$exception->getCode();
            $message = $exception->getMessage();

            // Handle specific 400 validation-like errors
            if ($code === 400) {
                $fields = ['customer_account_number', 'contact_company_name'];
                foreach ($fields as $field) {
                    if (!empty($request->get($field))) {
                        return redirect()->back()
                            ->withErrors([$field => $message])
                            ->withInput();
                    }
                }
            }

            return redirect()->back()
                ->with([
                    'alert' => true,
                    'error' => $code === 400 ? $message : 'Request for online account has failed. Please try again later.',
                ]);
        }
    }

    private function getExistingCustomer(ContactAccountRequest $request): array
    {
        $customerCode = trim($request->input('customer_account_number'));
        $customerName = trim($request->input('contact_company_name'));

        // Must have at least one identifier
        if ($customerCode === '' && $customerName === '') {
            throw new Exception(
                'Please enter Account Number or Company Name',
                Response::HTTP_BAD_REQUEST
            );
        }

        $customer = null;

        // 1️⃣ Lookup by company name if code is not provided
        if ($customerCode === '') {
            $customer = Customer::select('id', 'customer_code', 'customer_name')
                ->where(DB::raw('TRIM(customer_name)'), $customerName)
                ->first();

            if (!$customer) {
                throw new Exception(
                    'We could not find your company name in our system.',
                    Response::HTTP_BAD_REQUEST
                );
            }

            $customerCode = trim($customer->customer_code);
        }

        // 2️⃣ Always verify existence in ERP
        $customerInERP = ErpApi::getCustomerDetail(['customer_number' => $customerCode]);

        if (empty($customerInERP->CustomerNumber)) {
            throw new Exception(
                'Customer with the provided account number does not exist.',
                Response::HTTP_BAD_REQUEST
            );
        }

        // 3️⃣ If customer is still not found locally, try finding by code
        if (!$customer) {
            $customer = Customer::where(DB::raw('TRIM(customer_code)'), $customerCode)->first();
        }

        // 4️⃣ Create if missing, otherwise fetch address
        if (!$customer) {
            return [$this->createCustomer($customerInERP->toArray()), null];
        }

        $address = $customer->addresses->first();

        return [$customer, $address];
    }

    /**
     * @throws Exception
     */
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
            'approved' => config('amplify.security.skip_request_account_approval', false),
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
            if (!empty($erpCustomer->Message)) {
                throw new Exception($erpCustomer->Message);
            }

            if ($erpCustomer->CustomerNumber == null) {
                throw new Exception('ERP customer creation failed for customer ID: ' . $customer->id);
            }

            // Update customer code with ERP Customer Number
            $customer->customer_code = $erpCustomer->CustomerNumber;
            $customer->save();
        }

        return $customer;
    }

    private function handlePostRegistrationForRequestAccount($request, $contact): void
    {
        if ($request->filled('contact_newsletter') && $request->input('contact_newsletter') == 'yes') {
            if ($alreadySubscriber = Subscriber::whereEmail($request->input('contact_email'))->first()) {
                $alreadySubscriber->increment('attempts');
            } else {
                Subscriber::create(['email' => $request->input('contact_email')]);
            }
        }

        NotificationFactory::call(Event::CONTACT_ACCOUNT_REQUEST_RECEIVED, [
            'contact_id' => $contact->id,
        ]);

        if (config('amplify.security.skip_request_account_approval', false)) {
            NotificationFactory::call(Event::CONTACT_ACCOUNT_REQUEST_ACCEPTED, [
                'contact_id' => $contact->id,
                'customer_id' => $contact->customer_id,
            ]);
        } else {
            NotificationFactory::call(Event::CONTACT_ACCOUNT_REQUEST_VERIFICATION, [
                'contact_id' => $contact->id,
                'type' => Contact::REQUEST_ACCOUNT_VERIFICATION
            ]);
        }

        if (config('amplify.basic.is_permission_system_enabled')) {
            $defaultRole = CustomerRole::where('is_default', true)
                ->where('guard_name', Contact::AUTH_GUARD)
                ->when(config('permission.teams'), fn($q) => $q->where('team_id', $contact->customer_id))->first();

            if ($defaultRole) {
                $contact->assignRole($defaultRole);
            }
        }


        if (config('amplify.erp.auto_create_contact')) {
            ContactProfileSyncJob::dispatch($contact->toArray());
        }
    }
}
