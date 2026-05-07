<?php

namespace Amplify\Frontend\Components\Customer\Contact;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\System\Backend\Models\Contact;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class Details
 */
class Details extends BaseComponent
{
    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        return true;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $contact = request('contact');

        if ($contact->customer_id != customer()->id) {
            abort(401, 'Unauthorized');
        }

        $addresses = \Amplify\System\Backend\Models\CustomerAddress::where('customer_id', customer()->id)->get();
        $roles = \Amplify\System\Backend\Models\Role::where('guard_name', Contact::AUTH_GUARD)
            ->where('team_id', getPermissionsTeamId())
            ->get();

        return view('widget::customer.contact.show', [
            'contact' => $contact,
            'addresses' => $addresses,
            'roles' => $roles,
        ]);
    }
}
