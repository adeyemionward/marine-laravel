<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function listRoles()
    {
        $roles = Role::select('id', 'name','guard_name')->where('guard_name', 'api')->get();

        return response()->json([
            'success' => true,
            'message' => 'Roles fetched',
            'data' => $roles
        ], 200);
    }

    public function listRolesWithPermissions(Request $request)
    {
        $allPermissions = Permission::where('guard_name','api')->count();
        $allRoles = Role::where('guard_name','api')->count();
        $totalUsers = User::count();
        $activeUsers = User::where('active_status', User::ACTIVE)->count();
        $activeRoles = Role::where('is_active', 1)->where('guard_name','api')->count();
        $inActiveRoles = Role::where('is_active', 0)->where('guard_name','api')->count();

        $page  = $request->input('page', 1);
        $limit = $request->input('limit', 20);
        $search = $request->query('search');

        $query = Role::select('id','name', 'is_active')->where('guard_name','api');
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $roles = $query->paginate($limit, ['*'], 'page', $page);

        $roleCounts = [
            'allRoles' =>  $allRoles,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'allPermissions' =>  $allPermissions,
            'activeRoles' =>  $activeRoles,
            'inActiveRoles' =>  $inActiveRoles,
        ];

        $rolesWithPermissions = $roles->map(function ($role) {
            return [
                'role_id' => $role->id,
                'role' => $role->name,
                'role_status' => $role->status,
                'permissions' => $role->permissions->pluck('name'),
            ];
        });

        if ($roles->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Roles are empty',
                'data' => []
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Roles fetched',
            'data' => [
                'roles' => $rolesWithPermissions,
                'roles_count' => $roleCounts,
            ]
        ], 200);
    }

    public function getPermissions()
    {
        $permissions = Permission::select('id', 'name')->get();
        $groupedPermissions = $permissions->groupBy(function ($permission) {
            return explode('-', $permission->name)[0];
        });

        return response()->json([
            'success' => true,
            'message' => 'Permissions fetched',
            'data' => ['permissions' => $groupedPermissions]
        ], 200);
    }

    public function create(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'permission' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            if (Role::where('name', $request->input('name'))->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role already exists'
                ], 200);
            }

            $role = Role::create([
                'name' => $request->input('name'),
                'display_name' => $request->input('name'),
                 'guard_name' => 'api',
            ]);
            $role->syncPermissions($request->input('permission'));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role created',
                'data' => $role
            ], 201);

        } catch (\Exception $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating role. Please try later',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function view($id)
    {
        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        }

        $existingPermissions = $role->permissions;
        $permissions = Permission::select('id', 'name')->get();
        $groupedPermissions = $permissions->groupBy(function ($permission) {
            return explode('-', $permission->name)[0];
        });

        return response()->json([
            'success' => true,
            'message' => 'Permission Roles',
            'data' => [
                'permissions' => $permissions,
                'existingPermissions' => $existingPermissions,
                'groupedPermissions' => $groupedPermissions,
            ]
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            // 'permission' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if (Role::where('name', $request->input('name'))->where('id', '!=', $id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Role already exists'
            ], 200);
        }

        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        }

        $role->update(['name' => $request->input('name')]);
        $newPermissions = $request->input('permission', []);
        $role->syncPermissions($newPermissions);
        $role->permissions = $role->permissions->pluck('name');

        $userIds = DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->where('model_type', User::class)
            ->pluck('model_id');

        $usersWithRole = User::whereIn('id', $userIds)->get();
        foreach ($usersWithRole as $user) {
            $user->syncPermissions($newPermissions);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role & Permissions updated successfully',
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions
            ]
        ], 200);
    }

    public function destroy($id)
    {
        Role::where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully'
        ], 200);
    }



        public function assignRole(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'role_id' => 'required|integer|exists:roles,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'The user cannot be found'
                ], 404);
            }

            $roleId = $request->input('role_id');
            $role = Role::find($roleId);
            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'The role cannot be found'
                ], 404);
            }

            // Assign role
            $user->assignRole($role);

            // Sync permissions from role
            $permissions = $role->permissions;
            $user->syncPermissions($permissions);

            // Save role_id in users table (if you have that column)
            $user->spatie_role_id = $roleId;
            $user->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role assigned successfully',
                'data' => $user
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error assigning role. Please try later',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function detachRole(Request $request, $id)
    {
        DB::beginTransaction();

        $validator = Validator::make($request->all(), [
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.'
                ], 404);
            }

            $roleId = $request->input('role_id');
            if ($user->spatie_role_id != $roleId) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user does not have the specified role.'
                ], 400);
            }

            // Remove role
            $user->spatie_role_id = null;
            $user->save();

            // Remove from pivot table
            DB::table('model_has_roles')
                ->where('model_id', $id)
                ->where('role_id', $roleId)
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role detached successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ROLE_DETACH_FAILED', ['error' => $e->getMessage(), 'user_id' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred. Please try again later.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
