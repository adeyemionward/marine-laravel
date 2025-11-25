<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function listRoles()
    {
        try {
            $user = Auth::user();

            // Check if the user has access - check if permission exists first
            $hasPermission = false;
            try {
                $hasPermission = $user->can('role-list') || $user->hasPermissionTo('role-list', 'api');
            } catch (\Exception $permissionError) {
                // Permission doesn't exist, that's okay - we'll check by email
                Log::info('ROLE_LIST_PERMISSION_NOT_FOUND', [
                    'message' => 'role-list permission does not exist, checking by email instead'
                ]);
            }

            $isAdminEmail = $user->email === 'admin@marine.africa';

            if (!$hasPermission && !$isAdminEmail) {
                Log::warning('LIST_ROLES_UNAUTHORIZED', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'message' => 'User attempted to access roles without permission'
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view this page.',
                ], 403);
            }

            $roles = Role::where('guard_name', 'api')
                ->withCount('permissions')
                ->get()
                ->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description ?? null,
                        'guard_name' => $role->guard_name,
                        'permissions_count' => $role->permissions_count,
                        'created_at' => $role->created_at,
                        'updated_at' => $role->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Roles fetched',
                'data' => $roles
            ], 200);
        } catch (\Exception $e) {
            Log::error('LIST_ROLES_FAILED', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listRolesWithPermissions(Request $request)
    {
        $allPermissions = Permission::where('guard_name', 'api')->count();
        $allRoles = Role::where('guard_name', 'api')->count();
        $totalUsers = User::count();
        $activeUsers = User::where('active_status', User::ACTIVE)->count();
        $activeRoles = Role::where('is_active', 1)->where('guard_name', 'api')->count();
        $inActiveRoles = Role::where('is_active', 0)->where('guard_name', 'api')->count();

        $page  = $request->input('page', 1);
        $limit = $request->input('limit', 20);
        $search = $request->query('search');

        $query = Role::select('id', 'name', 'is_active')->where('guard_name', 'api');
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
                'permissions' => $role->permissions?->pluck('name'),
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
        try {
            $permissions = Permission::where('guard_name', 'api')
                ->select('id', 'name')
                ->get()
                ->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'label' => ucwords(str_replace('_', ' ', $permission->name))
                    ];
                })
                ->values()
                ->toArray();

            // Return permissions directly in data (not nested in permissions key)
            return response()->json([
                'success' => true,
                'message' => 'Permissions fetched',
                'data' => $permissions  // Direct array, not wrapped in 'permissions' key
            ], 200);
        } catch (\Exception $e) {
            Log::error('GET_PERMISSIONS_FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function create(Request $request)
    {
        DB::beginTransaction();
        try {
            // Log the incoming request for debugging
            Log::info('CREATE_ROLE_REQUEST', [
                'all_data' => $request->all(),
                'name' => $request->input('name'),
                'permission_ids' => $request->input('permission_ids'),
                // 'permissions' => $request->input('permissions'),
            ]);

            // $validator = Validator::make($request->all(), [
            //     'name' => 'required|string|max:255',
            //     'description' => 'nullable|string',
            //     'permissions' => 'nullable|array',
            //     'permissions.*' => 'string|exists:permissions,name,guard_name,api',
            // ]);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'permission_ids' => 'nullable|array',
                'permission_ids.*' => 'integer|exists:permissions,id',
            ]);


            if ($validator->fails()) {
                Log::warning('CREATE_ROLE_VALIDATION_FAILED', [
                    'errors' => $validator->errors()->toArray()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            if (Role::where('name', $request->input('name'))->where('guard_name', 'api')->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role already exists'
                ], 400);
            }

            $role = Role::create([
                'name' => $request->input('name'),
                'display_name' => $request->input('display_name') ?? $request->input('name'),
                'description' => $request->input('description'),
                'guard_name' => 'api',
                'is_active' => true,
            ]);

            // Get permissions from either 'permissions' or 'permission' field
            // $permissions = $request->input('permissions', $request->input('permission', []));
            $permissions = $request->input('permission_ids', []);
            if (!empty($permissions)) {
                $role->syncPermissions($permissions);
            }
            $role->load('permissions');
            $role->permissions = $role->permissions?->pluck('name');

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
        'name' => 'string|max:255',
        'description' => 'nullable|string',
        'permission_ids' => 'nullable|array',
        'permission_ids.*' => 'integer|exists:permissions,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    $role = Role::find($id);
    if (!$role) {
        return response()->json([
            'success' => false,
            'message' => 'Role not found'
        ], 404);
    }

    // Update basic info
    $role->update([
        'name' => $request->input('name'),
        'description' => $request->input('description'),
    ]);

    // Update user profiles with the new role name
    $newRoleName = $request->input('name');
    $userRole = match ($newRoleName) {
        'admin' => UserRole::ADMIN,
        'moderator' => UserRole::MODERATOR,
        'seller' => UserRole::SELLER,
        'buyer' => UserRole::BUYER,
        default => UserRole::USER,
    };

    User::whereHas('roles', function ($query) use ($role) {
        $query->where('role_id', $role->id);
    })->chunk(100, function ($users) use ($userRole) {
        foreach ($users as $user) {
            if ($user->profile) {
                $user->profile->update(['role' => $userRole]);
            }
        }
    });



    // 🧠 Defensive fix: ensure permissions is always an array
    $newPermissions = $request->input('permission_ids', []);
    if (!is_array($newPermissions)) {
        $newPermissions = [];
    }

    // Initialize $validPermissionIds outside the try block for scope
    $validPermissionIds = [];

    try {
        // 🧩 Filter permissions by IDs and guard_name
        $validPermissionNames = \Spatie\Permission\Models\Permission::whereIn('id', $newPermissions)
            ->where('guard_name', $role->guard_name)
            ->pluck('name')
            ->toArray();

        Log::info('SYNC_PERMISSIONS_DATA', [
            'role_id' => $role->id,
            'valid_permission_names' => $validPermissionNames
        ]);

        // Spatie's syncPermissions handles an empty array correctly.
        $role->syncPermissions($validPermissionNames);

    } catch (\Exception $e) {
        Log::error('SYNC_PERMISSIONS_FAILED', [
            'role_id' => $role->id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Permission sync failed',
            'error' => $e->getMessage()
        ], 500);
    }

    $role->load('permissions');

    // 🌟 REVISED USER SYNC LOGIC 🌟
    // Sync permissions for users with this role using an efficient chunking method.
    // This is more robust than querying the pivot table directly.
    User::whereHas('roles', function ($query) use ($role) {
        $query->where('role_id', $role->id);
    })->chunk(100, function ($users) use ($validPermissionNames, $userRole) {
        foreach ($users as $user) {
            Log::info('SYNC_USER_PERMISSIONS_DATA', [
                'user_id' => $user->id,
                'valid_permission_names' => $validPermissionNames
            ]);
            $user->syncPermissions($validPermissionNames);

            if ($user->profile) {
                $user->profile->update(['role' => $userRole]);
            }

            // Optional: Reload permissions to ensure any downstream logic has the fresh data
            $user->load('permissions');
        }
    });

    return response()->json([
        'success' => true,
        'message' => 'Role & Permissions updated successfully',
        'data' => [
            'id' => $role->id,
            'name' => $role->name,
            // 🚨 CRITICAL FIX: Use nullsafe operator (?->) to prevent errors
            // if the permissions relationship fails to load (returning null).
            'permissions' => $role->permissions?->pluck('name') ?? collect(),
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
                    'message' => 'User not found'
                ], 404);
            }

            $role = Role::find($request->input('role_id'));
            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], 404);
            }

            // Assign the role
            $user->assignRole($role);

            // Update the user's profile with the new role
            if ($user->profile) {
                $userRole = match ($role->name) {
                    'admin' => UserRole::ADMIN,
                    'moderator' => UserRole::MODERATOR,
                    'seller' => UserRole::SELLER,
                    'buyer' => UserRole::BUYER,
                    default => UserRole::USER,
                };
                $user->profile->update(['role' => $userRole]);
            }

            // Safely get permissions attached to the role
            $permissions = $role->permissions()?->pluck('name')->toArray() ?? [];

            // Sync only if there are permissions
            if (!empty($permissions)) {
                $user->syncPermissions($permissions);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role assigned successfully',
                'data' => [
                    'user' => $user->only(['id', 'name', 'email']),
                    'role' => $role->only(['id', 'name']),
                    'permissions' => $permissions
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ASSIGN_ROLE_FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error assigning role',
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
            $role = Role::find($roleId);

            if (!$user->hasRole($role)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user does not have the specified role.'
                ], 400);
            }

            // Remove role using Spatie's method
            $user->removeRole($role);

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

    public function getRolePermissions($id)
    {
        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        }

        $permissions = $role->permissions()->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'message' => 'Permissions for role fetched successfully',
            'data' => $permissions
        ], 200);
    }

    public function listRoleNames()
    {
        try {
            // Use a cache for this query since it's public and will be hit often.
            // Cache for 1 hour. A more robust implementation would clear this cache when roles are updated.
            $roleNames = cache()->remember('role_names_list', 3600, function () {
                return Role::where('guard_name', 'api')->pluck('name');
            });

            return response()->json(['success' => true, 'data' => $roleNames]);
        } catch (\Exception $e) {
            Log::error('LIST_ROLE_NAMES_FAILED', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch role names'], 500);
        }
    }
}
