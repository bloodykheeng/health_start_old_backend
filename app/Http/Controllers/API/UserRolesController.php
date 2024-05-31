<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserRolesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $userRoles = Role::with("permissions")->get();
        return response()->json($userRoles, 200);
    }



    public function addPermissionsToRole(Request $request)
    {
        // $requestData = $request->all();

        $roleID = $request->role_id;
        $permissionIDs = $request->permission_ids;

        // $role = Role::findById($roleID); // Find the role by ID
        $role = Role::find($roleID);
        $permissions = Permission::whereIn('id', $permissionIDs)->get(); // Get the permissions based on the IDs

        // $role->syncPermissions($permissions); // Sync the permissions to the role
        $role->permissions()->attach($permissions);

        return response()->json(['message' => 'Permissions added to role successfully']);
    }


    public function getAssignableRoles(Request $request)
    {
        if ($request->user()->hasRole('Admin')) {
            $roles = Role::all()->pluck('name');
        } else {
            $roles = Role::whereNotIn('name', ['Admin'])->pluck('name');
        }
        return response()->json($roles, 200);
    }


    public function deletePermissionFromRole(Request $request)
    {
        $roleID = $request->role_id;
        $permissionID = $request->permission_id;

        $role = Role::findOrFail($roleID); // Find the role by ID
        $permission = Permission::findOrFail($permissionID); // Find the permission by ID

        $role->revokePermissionTo($permission); // Revoke the permission from the role

        return response()->json(['message' => 'Permission deleted from role successfully']);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
