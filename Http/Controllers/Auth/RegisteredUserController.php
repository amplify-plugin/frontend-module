<?php

namespace Amplify\Frontend\Http\Controllers\Auth;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\ErpApi\Jobs\ContactProfileSyncJob;
use Amplify\Frontend\Http\Requests\Auth\ContactAccountRequest;
use Amplify\Frontend\Http\Requests\Auth\RegistrationRequest;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Models\Customer;
use Amplify\System\Backend\Models\Event;
use Amplify\System\Backend\Models\IndustryClassification;
use Amplify\System\Factories\NotificationFactory;
use Amplify\System\Marketing\Models\Subscriber;
use App\Http\Controllers\Controller;
use ErrorException;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class RegisteredUserController extends Controller
{
    use HasDynamicPage;

    /**
     * Display the registration view.
     *
     * @throws ErrorException|BindingResolutionException
     */
    public function __invoke(): string
    {
        $this->loadPageByType('registration');

        return $this->render();
    }

    /**
     * @param ContactAccountRequest $request
     * @return RedirectResponse
     * @throws \Throwable
     */
    public function requestAccount(ContactAccountRequest $request)
    {
        DB::beginTransaction();

        try {

            [$customer, $address] = $this->getExistingCustomer($request);

            // Create Contact
            $contact = $customer->contacts()->create([
                'account_title_id' => $request->input('contact_account_title'),
                'name' => $request->input('contact_name'),
                'phone' => $request->input('contact_phone_number', $erpData['CustomerPhone'] ?? null),
                'password' => $request->input('contact_password'),
                'email' => $request->input('contact_email'),
                'login_id' => $request->input('contact_email'),
                'is_admin' => $customer->wasRecentlyCreated,
                'enabled' => config('amplify.security.skip_contact_approval', false),
                'customer_address_id' => $address->id ?? null,
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
                'customer_address_id' => $address->id ?? null,
                'ship_to_name' => $address->address_name ?? null,
            ]);

            DB::commit();

            // Newsletter + Notification
            $this->handlePostRegistrationForRequestAccount($request, $contact);

            return redirect()->to('/')->with('success', __(config('amplify.messages.registration_success')));

        } catch (Exception $exception) {
            DB::rollBack();

            Log::error($exception);

            $code = (int)$exception->getCode();
            $message = $exception->getMessage();

            // Handle specific 400 validation-like errors
            if ($code === 400) {
                $fields = ['customer_account_number', 'contact_company_name'];
                Session::flash($message);
                foreach ($fields as $field) {
                    if (!empty($request->get($field))) {
                        return redirect()->back()
                            ->withErrors([$field => $message])
                            ->withInput();
                    }
                }
            }

            // Generic error fallback
            Session::flash('error', $code === 400
                ? $message
                : 'Request for online account has failed. Please try again later.'
            );

            return redirect()->back();
        }
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws Exception|\Throwable
     */
    public function newRetailCustomer(RegistrationRequest $request): RedirectResponse
    {
        $industry = IndustryClassification::find($request->input('industry_classification_id'));

        $customerAddressPayload = [
            'CustomerName' => $request->input('company_name'),
            'CustomerEmail' => $request->input('email'),
            'CustomerPhone' => $request->input('phone_number'),
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

            return redirect()->to('/')->with('success', __(config('amplify.messages.registration_success')));

        } catch (Exception $exception) {

            Log::error($exception);

            DB::rollBack();

            $request->session()->flash('error', 'Registration request failed. Please try again later.');

            $request->session()->flash('message', $exception->getMessage());

            return redirect()->back()->withInput();
        }
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    private function getExistingCustomer(ContactAccountRequest $request)
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
            return $this->createCustomer($customerInERP->toArray());
        }

        $address = $customer->addresses->first();

        return [$customer, $address];
    }

    /**
     * @param array $attributes
     * @return Customer
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
            'approved' => config('amplify.erp.auto_create_cash_customer', false),
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

    /**
     * @param $request
     * @param $contact
     * @return void
     */
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

        if (config('amplify.erp.auto_create_contact')) {
            ContactProfileSyncJob::dispatch($contact->toArray());
        }
    }

    /**
     * @param $request
     * @param $customer
     * @param $contact
     * @return void
     */
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

        if (config('amplify.erp.auto_create_cash_customer', false)) {
            NotificationFactory::call(Event::REGISTRATION_REQUEST_ACCEPTED,
                ['customer_id' => $customer->id]);
        }

        if (config('amplify.erp.auto_create_contact')) {
            ContactProfileSyncJob::dispatch($contact->toArray());
        }
    }
}
