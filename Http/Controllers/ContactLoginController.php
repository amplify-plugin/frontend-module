<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Events\ContactLoggedIn;
use Amplify\Frontend\Helpers\CustomerHelper;
use Amplify\Frontend\Traits\HasDynamicPage;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactLogin;
use App\Models\Customer;
use App\Models\Role;
use Backpack\Pro\Http\Controllers\Operations\FetchOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContactLoginController extends Controller
{
    use FetchOperation;
    use HasDynamicPage;

    public function __construct()
    {
        if (! app()->runningInConsole()) {
            if (! config('amplify.basic.enable_multi_customer_manage', true)) {
                abort(401, 'Contact Login Management Feature Disabled. Contact Administrator.');
            }
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @throws \ErrorException
     */
    public function index(Request $request): string
    {
        abort_unless(customer(true)->can('login-management.manage-logins'), 403);
        $this->loadPageByType('contact_login');

        store()->contactLoginPaginate = ContactLogin::fetchContactLoginPagination($request->all());

        return $this->render();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $contact = Contact::with([
            'addresses',
            'contactLogins',
            'contactLogins.contact',
            'contactLogins.customer',
            'contactLogins.customerAddress',
        ])->find($id);

        $contact->contactLogins->map(function ($contactLogin) {
            $contactLogin->role_list = Role::where([
                'team_id' => $contactLogin->customer_id,
                'guard_name' => 'customer',
            ])->get();
        });

        abort_if($contact === null, 404, 'Invalid Contact ID Passed');

        store()->contactModel = $contact;

        push_css('https://unpkg.com/vue-multiselect@2.1.6/dist/vue-multiselect.min.css', 'custom-style');

        $this->loadPageByType('contact_login_edit');

        return $this->render();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contact $contact)
    {
        $request->validate([
            'login_customers' => 'required|array',
            'login_customers.*.contact_id' => 'required|integer',
            'login_customers.*.customer_id' => 'required|integer',
            // 'login_customers.*.warehouse_id' => "required|integer",
            // 'login_customers.*.customer_address_id' => "required|integer",
            'login_customers.*.roles' => 'required_without:login_customers.*.permissions',
        ]);

        try {
            DB::beginTransaction();
            $roles = [];
            $permissions = [];
            $contact->contactLogins()->delete();
            foreach ($request->login_customers ?? [] as $login_customer) {
                ContactLogin::create([
                    'contact_id' => $login_customer['contact_id'],
                    'customer_id' => $login_customer['customer_id'],
                    'warehouse_id' => $login_customer['warehouse_id'] ?? null,
                    'customer_address_id' => $login_customer['customer_address_id'] ?? null,
                ]);

                DB::table(config('permission.table_names.model_has_roles'))->where([
                    'model_type' => Contact::class,
                    'model_id' => $login_customer['contact_id'],
                    'team_id' => $login_customer['customer_id'],
                ])->delete();
                DB::table(config('permission.table_names.model_has_permissions'))->where([
                    'model_type' => Contact::class,
                    'model_id' => $login_customer['contact_id'],
                    'team_id' => $login_customer['customer_id'],
                ])->delete();

                foreach ($login_customer['roles'] ?? [] as $role) {
                    $roles[] = [
                        'role_id' => $role,
                        'model_type' => Contact::class,
                        'model_id' => $login_customer['contact_id'],
                        'team_id' => $login_customer['customer_id'],
                    ];
                }

                foreach ($login_customer['permissions'] ?? [] as $permission) {
                    $permissions[] = [
                        'permission_id' => $permission,
                        'model_type' => Contact::class,
                        'model_id' => $login_customer['contact_id'],
                        'team_id' => $login_customer['customer_id'],
                    ];
                }

                // if ($contact->customer_id === $login_customer['customer_id']) {
                //     $contact->updateEntryAsPerContactLogin($login_customer);
                // }
            }

            DB::table(config('permission.table_names.model_has_roles'))->insert($roles);
            DB::table(config('permission.table_names.model_has_permissions'))->insert($permissions);
            $contact->validateActiveCustomer();

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'message' => 'Something went wrong.',
            ], 500);
        }

        return response()->json([
            'message' => 'Successfully updated.',
        ], 200);
    }

    /**
     * Impersonate a selected contact user on new window tab
     */
    public function impersonate(Contact $contact, Request $request): RedirectResponse
    {
        Auth::guard(Contact::AUTH_GUARD)->logout();

        $request->session()->invalidate();

        Auth::guard(Contact::AUTH_GUARD)->login($contact);

        ContactLoggedIn::dispatch($contact, null);

        return redirect()->intended(CustomerHelper::afterLoggedRedirectTo());
    }

    public function fetchAssignableCustomer(Request $request)
    {
        $customerExcluded = $request->input('cus_exld', []) ?? [];

        return $this->fetch([
            'model' => \App\Models\Customer::class,
            'searchable_attributes' => ['customer_name', 'customer_code', 'id', 'email', 'phone'],
            'paginate' => 10, // items to show per page
            'searchOperator' => 'LIKE',
            'query' => fn ($model) => $model->where('is_assignable', true)->whereNotIn('id', $customerExcluded)->orderBy('customer_name'),
        ]);
    }

    public function verifyAssignableContact(Request $request): JsonResponse
    {
        try {
            $contact_email = $request->input('contact_email');
            $customer_id = $request->input('customer_id');

            if (! $customer_id || ! $contact_email) {
                throw new \Exception('Contact Email or Customer Id is missing');
            }

            $customerModel = Customer::find($customer_id);

            if (! $customerModel) {
                throw new \Exception('Invalid Customer ID received from input');
            }

            $warehouses = ErpApi::getWarehouses();
            $customerAddresses = $customerModel->addresses;
            $response = ErpApi::contactValidation([
                'email_address' => $contact_email,
                'customer_number' => $customerModel->customer_code,
            ]);

            $jsonResponse['warehouse_id'] = null;
            $jsonResponse['customer_address'] = null;
            $jsonResponse['valid'] = (isset($response->ValidCombination) && $response->ValidCombination == 'Y');
            $jsonResponse['status'] = $jsonResponse['valid'];
            $jsonResponse['message'] = ($jsonResponse['status'])
                ? 'This customer can be assigned to current contact person.'
                : 'This customer can not be assigned to current contact person.';

            if (isset($response->DefaultWarehouse)) {
                $defaultWarehouse = $warehouses->firstWhere('WarehouseNumber', $response->DefaultWarehouse);
                $jsonResponse['warehouse_id'] = ($defaultWarehouse) ? $defaultWarehouse->InternalId : $customerModel->warehouse_id;
            }

            if (isset($response->DefaultShipTo)) {
                $defaultShipTo = $customerAddresses->firstWhere('address_code', $response->DefaultShipTo);

                if (! $defaultShipTo) {
                    $defaultShipTo = $customerAddresses->firstWhere('address_code', $customerModel->shipto_address_code);
                }

                $jsonResponse['customer_address'] = $defaultShipTo ?? null;
            }

            return response()->json($jsonResponse);

        } catch (\Exception $exception) {

            return response()->json(['message' => $exception->getMessage(), 'status' => false], 200);
        }
    }

    public function getRoles(Request $request)
    {
        $res = [];

        if ($request.filled('customer_id')) {
            $res = Role::where([
                'team_id' => $request->customer_id,
                'guard_name' => 'customer',
            ])->get();
        }

        return response()->json($res, 200);
    }
}
