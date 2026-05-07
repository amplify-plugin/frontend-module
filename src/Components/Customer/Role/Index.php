<?php

namespace Amplify\Frontend\Components\Customer\Role;

use Amplify\Frontend\Abstracts\BaseComponent;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class Index
 */
class Index extends BaseComponent
{
    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        return customer(true)->can('role.view')
            && !config('amplify.security.single_team_for_customers');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $perPageOptions = getPaginationLengths();
        $perPage = request('per_page', $perPageOptions[0]);
        $search = request('search');
        $roles = \Amplify\System\Backend\Models\Role::query();

        if ($search) {
            $roles->where('name', 'like', "%{$search}%");
        }

        $roles = $roles->where(['team_id' => customer()->id, 'guard_name' => 'customer'])
            ->latest()->paginate($perPage);

        return view('widget::customer.role.index', [
            'perPageOptions' => $perPageOptions,
            'perPage' => $perPage,
            'search' => $search,
            'roles' => $roles,
        ]);
    }
}
