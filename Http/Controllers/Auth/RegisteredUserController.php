<?php

namespace Amplify\Frontend\Http\Controllers\Auth;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\ErpApi\Jobs\ContactProfileSyncJob;
use Amplify\Frontend\Http\Requests\Auth\ContactAccountRequest;
use Amplify\Frontend\Http\Requests\Auth\RegistrationRequest;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Factories\NotificationFactory;
use Amplify\System\Marketing\Models\Subscriber;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Event;
use App\Models\IndustryClassification;
use ErrorException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\RedirectResponse;
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

    public function requestAccount(ContactAccountRequest $request)
    {
        try {

            $customerCode = trim($request->input('customer_account_number'));

            $customerInERP = ErpApi::getCustomerDetail(['customer_number' => $customerCode]);

            if (empty($customerInERP->CustomerNumber)) {
                return redirect()->back()
                    ->withErrors(['contact_account_number' => 'Customer with the provided account number does not exist.'])
                    ->withInput();
            }

            $customer = Customer::where(DB::raw('TRIM(customer_code)'), $customerCode)->first();

            DB::beginTransaction();

            if (! $customer) {
                // Create new customer from ERP data
                [$customer, $address] = $this->createCustomerAndAddress($customerInERP->toArray());
            } else {
                $address = $customer->addresses->first();
            }

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
            $this->handlePostRegistrationTasks($request, $contact);

            Session::flash('success', __(config('amplify.messages.registration_success')));

            return redirect()->to('/');

        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('Customer Registration Exception: '.$exception->getMessage());

            Session::flash('error', 'Request for online account has failed. Please try again later.');

            return redirect()->back();
        }
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Exception
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
            'CustomerCity' => $request->input('address_3'),
            'CustomerState' => $request->input('state'),
            'CustomerCountry' => $request->input('country_code'),
        ];

        DB::beginTransaction();
        try {

            // Create Customer & Address
            [$customer, $address] = $this->createCustomerAndAddress($customerAddressPayload);

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
                'customer_address_id' => $address->id ?? null,
                'warehouse_id' => $customer->warehouse_id ?? null,
                'active_customer_id' => $customer->getKey(),
                'order_limit' => 0,
                'daily_budget_limit' => 0,
                'monthly_budget_limit' => 0,
            ]);

            DB::commit();

            // Create Contact Login
            $contact->contactLogins()->create([
                'customer_id' => $customer->id,
                'warehouse_id' => $customer->warehouse_id ?? null,
                'customer_address_id' => $address->id ?? null,
                'ship_to_name' => $address->address_name ?? null,
            ]);

            $this->handlePostRegistrationTasks($request, $contact);

            Session::flash('success', __(config('amplify.messages.registration_success')));

            return redirect()->to('/');

        } catch (\Exception $exception) {
            DB::rollBack();

            Log::error('Customer Registration  Exception: '.$exception->getMessage());

            Session::flash('error', 'Registration request failed. Please try again later. ');

            return redirect()->back();
        }
    }

    /**
     * @param  $request
     * @return [\App\Models\Customer, \App\Models\CustomerAddress]
     *
     * @throws \Exception
     */
    private function createCustomerAndAddress(array $attributes): array
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
        ]);

        // Create Address
        $address = $customer->addresses()->create([
            'address_name' => $attributes['DefaultShipTo'],
            'address_1' => $attributes['CustomerAddress1'],
            'address_2' => $attributes['CustomerAddress2'],
            'address_3' => $attributes['CustomerAddress3'],
            'zip_code' => $attributes['CustomerZipCode'],
            'city' => $attributes['CustomerCity'],
            'country_code' => $attributes['CustomerCountry'],
            'state' => $attributes['CustomerState'],
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
                'address_1' => $address->address_1,
                'address_2' => $address->address_2,
                'address_3' => $address->address_3,
                'city' => $address->city,
                'state' => $address->state,
                'zip_code' => $address->zip_code,
                'country_code' => $address->country_code,
                'branch' => null,
                'customer_industry' => $customer->industryClassification?->name,
            ]);

            // Handle ERP response
            if ($erpCustomer->CustomerNumber == null) {
                throw new \Exception('ERP customer creation failed for customer ID: '.$customer->id);
            }

            // Update customer code with ERP Customer Number
            $customer->customer_code = $erpCustomer->CustomerNumber;
            $customer->save();
        }

        return [$customer, $address];
    }

    private function handlePostRegistrationTasks($request, $contact): void
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

        logger()->debug('');

        if (config('amplify.erp.auto_create_contact')) {
            ContactProfileSyncJob::dispatch($contact->toArray());
        }
    }
}
