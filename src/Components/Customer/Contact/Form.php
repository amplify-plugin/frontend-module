<?php

namespace Amplify\Frontend\Components\Customer\Contact;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\Frontend\Helpers\CustomerHelper;
use Amplify\System\Backend\Models\Contact;
use Amplify\System\Helpers\UtilityHelper;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class Create
 */
class Form extends BaseComponent
{
    /**
     * @var array
     */
    public $options;

    /**
     * @var Contact|null
     */
    public $contact;

    private bool $editable;

    /**
     * Create a new component instance.
     *
     * @param bool $editable
     *
     * @throws \ErrorException
     */
    public function __construct($editable)
    {
        parent::__construct();

        $this->editable = UtilityHelper::typeCast($editable, 'boolean');

        if ($this->editable) {
            $this->contact = store()->contactModel;
        }
    }

    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        return customer(true)->canAny('contact.create', 'contact.update');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $addresses = \Amplify\System\Backend\Models\CustomerAddress::where('customer_id', customer()->id)->get();

        $roles = \Amplify\System\Backend\Models\Role::where('guard_name', Contact::AUTH_GUARD)
            ->where('team_id', getPermissionsTeamId())
            ->get();

        $action_route = route('frontend.contacts.store');
        $action_method = 'POST';
        $contact_roles = [];

        if ($this->editable) {
            $action_route = route('frontend.contacts.update', ($this->contact->id ?? ''));
            $action_method = 'PUT';
            $contact_roles = $this->contact->roles->pluck('id')->toArray();
        }

        $urls = CustomerHelper::redirecteableUrls();

        return view('widget::customer.contact.form', [
            'addresses' => $addresses,
            'roles' => $roles,
            'contact' => $this->contact,
            'action_route' => $action_route,
            'action_method' => $action_method,
            'editable' => $this->editable,
            'contact_roles' => $contact_roles,
            'urls' => $urls,
        ]);
    }
}
