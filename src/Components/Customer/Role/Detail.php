<?php

namespace Amplify\Frontend\Components\Customer\Role;

use Amplify\Frontend\Abstracts\BaseComponent;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class Update
 */
class Detail extends BaseComponent
{
    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        return customer(true)->can('role.show')
            && !config('amplify.security.single_team_for_customers');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $role = store()->contactRoleModel;

        $permissionArray = [];

        foreach ($role->permissions as $key => $permission) {
            $group = explode('.', $permission->name, 2);
            $permissionArray[$group[0]][$key] = $permission->name;
        }

        return view('widget::customer.role.show', [
            'role' => $role,
            'permissionArray' => $permissionArray,
        ]);
    }
}
