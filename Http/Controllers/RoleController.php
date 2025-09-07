<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Http\Requests\RoleStoreUpdateRequest;
use Amplify\Frontend\Traits\HasDynamicPage;
use App\Http\Controllers\Controller;
use App\Models\CustomerRole;
use App\Models\Role;

class RoleController extends Controller
{
    use HasDynamicPage;

    /**
     * Display a listing of the resource.
     *
     * @throws \ErrorException
     */
    public function index(): string
    {
        $this->loadPageByType('role');
        if (! customer(true)->can('role.view')) {
            abort(403);
        }

        return $this->render();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws \ErrorException
     */
    public function create()
    {
        $this->loadPageByType('role_create');

        if (! customer(true)->can('role.manage')) {
            abort(403);
        }

        return $this->render();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleStoreUpdateRequest $request)
    {
        $permission = json_decode($request->input('permission', []));

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'customer',
            'team_id' => getPermissionsTeamId(),
        ]);

        $role->permissions()->sync($permission);

        return redirect()->route('frontend.roles.index');
    }

    /**
     * Display the specified resource.
     *
     * @throws \ErrorException
     */
    public function show(CustomerRole $role)
    {
        store()->contactRoleModel = $role;

        $this->loadPageByType('role_details');
        if (! customer(true)->can('role.view')) {
            abort(403);
        }

        return $this->render();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @throws \ErrorException
     */
    public function edit(CustomerRole $role)
    {
        store()->contactRoleModel = $role;

        $this->loadPageByType('role_edit');
        if (! customer(true)->can('role.manage')) {
            abort(403);
        }

        return $this->render();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleStoreUpdateRequest $request, CustomerRole $role)
    {
        $permission = json_decode($request->input('permission', []));

        $role->update([
            'name' => $request->name,
        ]);

        $role->permissions()->sync($permission);

        return redirect()->route('frontend.roles.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomerRole $role)
    {
        $role->delete();

        return back();
    }
}
