<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Mail\UserCreateMail;
use App\Models\User;
use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Log\Logger;

class UserController extends Controller
{
    use FileUploadTrait;

    public function index(Request $request)
    {
        try {
            $user = auth('api')->user();
            $perPage = $request->get('per_page', 15);

            $query = User::query();

            // Apply filters based on user role
            if (!$user->isSuperAdmin()) {
                if ($user->organization_id) {
                    $query->where('organization_id', $user->organization_id);

                    if ($user->branch_id) {
                        $query->where('branch_id', $user->branch_id);
                    }
                }
            }

            // Search
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('can_login')) {
                $query->where('can_login', $request->boolean('can_login'));
            }

            if ($request->has('organization_id')) {
                $query->where('organization_id', $request->organization_id);
            }

            if ($request->has('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->has('role')) {
                $roleName = $request->role;
                $query->whereHas('roles', function ($q) use ($roleName) {
                    $q->where('name', $roleName);
                });
            }

            $query->orderBy('created_at', 'desc');
            $query->with(['roles' => function ($q) {
                $q->select('id', 'name');
            }, 'organization', 'branch']);

            $users = $query->paginate($perPage);

            if ($users->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No users found',
                    'data' => []
                ], 200);
            }

            // Transform data to hide pivot
            $users->getCollection()->transform(function ($user) {
                $userData = $user->toArray();
                if (isset($userData['roles'])) {
                    foreach ($userData['roles'] as &$role) {
                        unset($role['pivot']);
                    }
                }
                return $userData;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Users retrieved successfully',
                'data' => $users
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve users',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function create() {}

    public function store(CreateUserRequest $request)
    {
        try {
            $currentUser = auth('api')->user();
            $data = $request->validated();

            if (!$currentUser->isSuperAdmin() && $currentUser->hasRole('Super Admin')) {
                if (isset($data['organization_id']) && $data['organization_id'] != $currentUser->organization_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You can only create users in your own organization'
                    ], 403);
                }

                if (isset($data['branch_id']) && $data['branch_id'] != $currentUser->branch_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You can only create users in your own branch'
                    ], 403);
                }
            }

            $role = Role::where('name', $data['role'])->first();
            if ($role->name === 'Super Admin' && !$currentUser->isSuperAdmin() && !$currentUser->hasRole('Super Admin')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only Super Admins can create other Super Admins'
                ], 403);
            }

            $imagePath = $this->handleFileUpload($request, 'profile_image', null, 'users/profile', $data['email']);
            if ($imagePath) {
                $data['profile_image'] = $imagePath;
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
                'organization_id' => $data['organization_id'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'profile_image' => $data['profile_image'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'can_login' => $data['can_login'] ?? true,
            ]);

            $user->assignRole($data['role']);

            try {
                $emailData = [
                    'user' => $user,
                    'password' => $data['password'],
                    'role' => $data['role'],
                    'organization_name' => isset($user->organization) ? $user->organization->name : null,
                    'branch_name' => isset($user->branch) ? $user->branch->name : null,
                    'created_by' => $currentUser->name,
                ];

                Mail::to($user->email)->send(new UserCreateMail($emailData));
            } catch (\Throwable $th) {
                Log::error('Failed to prepare user creation email data: ' . $th->getMessage());
            }

            $user->load(['roles' => function ($q) {
                $q->select('id', 'name');
            }, 'organization', 'branch']);

            $userData = $user->toArray();
            if (isset($userData['roles'])) {
                foreach ($userData['roles'] as &$role) {
                    unset($role['pivot']);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'User created successfully',
                'data' => $userData
            ], 201);
        } catch (\Throwable $th) {
            if (isset($imagePath)) {
                $this->deleteFile($imagePath);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create user',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $currentUser = auth('api')->user();

            $user = User::with(['roles' => function ($q) {
                $q->select('id', 'name');
            }, 'organization', 'branch'])->find($id);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                    'data' => []
                ], 404);
            }

            if (!$currentUser->isSuperAdmin() && !$currentUser->hasRole('Super Admin')) {
                if (
                    !$currentUser->canAccessOrganization($user->organization_id) ||
                    !$currentUser->canAccessBranch($user->branch_id)
                ) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to view this user'
                    ], 403);
                }
            }

            $userData = $user->toArray();

            if (isset($userData['roles'])) {
                foreach ($userData['roles'] as &$role) {
                    unset($role['pivot']);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'User retrieved successfully',
                'data' => $userData
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve user',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function edit(string $id) {}

    public function update(UpdateUserRequest $request, string $id)
    {
        try {
            $currentUser = auth('api')->user();
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                    'data' => []
                ], 404);
            }

            if (!$currentUser->isSuperAdmin() && !$currentUser->hasRole('Super Admin')) {
                if (
                    !$currentUser->canAccessOrganization($user->organization_id) ||
                    !$currentUser->canAccessBranch($user->branch_id)
                ) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to update this user'
                    ], 403);
                }
            }

            $data = $request->validated();

            if (isset($data['role'])) {
                $role = Role::where('name', $data['role'])->first();
                if ($role->name === 'Super Admin' && !$currentUser->isSuperAdmin()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Only Super Admins can assign Super Admin role'
                    ], 403);
                }
            }

            $oldImagePath = $user->profile_image;
            $imagePath = $this->handleFileUpload($request, 'profile_image', $oldImagePath, 'users/profile', $user->email);

            if ($imagePath) {
                $data['profile_image'] = $imagePath;
            }

            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $user->update($data);
            $user->refresh();

            if (isset($data['role'])) {
                $user->syncRoles([$data['role']]);
            }

            $user->load(['roles' => function ($q) {
                $q->select('id', 'name');
            }, 'organization', 'branch']);

            $userData = $user->toArray();
            if (isset($userData['roles'])) {
                foreach ($userData['roles'] as &$role) {
                    unset($role['pivot']);
                }
            }

            $userData['role_names'] = $user->getRoleNames();

            return response()->json([
                'status' => 'success',
                'message' => 'User updated successfully',
                'data' => $userData
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update user',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $currentUser = auth('api')->user();
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                    'data' => []
                ], 404);
            }

            if ($user->id === $currentUser->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You cannot delete your own account'
                ], 422);
            }

            if (!$currentUser->isSuperAdmin()) {
                if (
                    !$currentUser->canAccessOrganization($user->organization_id) ||
                    !$currentUser->canAccessBranch($user->branch_id)
                ) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to delete this user'
                    ], 403);
                }
            }

            if ($user->hasRole('Super Admin')) {
                $superAdminCount = User::role('Super Admin')->count();
                if ($superAdminCount <= 1) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot delete the last Super Admin'
                    ], 422);
                }
            }

            $this->deleteFile($user->profile_image);
            $user->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'User deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete user',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function activate(string $id)
    {
        try {
            $currentUser = auth('api')->user();
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                    'data' => []
                ], 404);
            }

            // Check permissions
            if (!$currentUser->isSuperAdmin()) {
                if (
                    !$currentUser->canAccessOrganization($user->organization_id) ||
                    !$currentUser->canAccessBranch($user->branch_id)
                ) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to activate this user'
                    ], 403);
                }
            }

            if ($user->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User is already active',
                    'data' => [
                        'current_status' => 'active',
                        'user_id' => $user->id,
                        'user_name' => $user->name
                    ]
                ], 422);
            }

            $user->update(['is_active' => true]);

            return response()->json([
                'status' => 'success',
                'message' => 'User activated successfully',
                'data' => $user
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate user',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function deactivate(string $id)
    {
        try {
            $currentUser = auth('api')->user();
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                    'data' => []
                ], 404);
            }

            // Prevent self-deactivation
            if ($user->id === $currentUser->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You cannot deactivate your own account'
                ], 422);
            }

            // Check permissions
            if (!$currentUser->isSuperAdmin()) {
                if (
                    !$currentUser->canAccessOrganization($user->organization_id) ||
                    !$currentUser->canAccessBranch($user->branch_id)
                ) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to deactivate this user'
                    ], 403);
                }
            }

            if (!$user->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User is already inactive',
                    'data' => [
                        'current_status' => 'inactive',
                        'user_id' => $user->id,
                        'user_name' => $user->name
                    ]
                ], 422);
            }

            $user->update(['is_active' => false]);

            return response()->json([
                'status' => 'success',
                'message' => 'User deactivated successfully',
                'data' => $user
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate user',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function updateProfileImage(Request $request, string $id)
    {
        try {
            $currentUser = auth('api')->user();
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                    'data' => []
                ], 404);
            }

            // Check permissions (users can update their own image, admins can update others)
            if ($currentUser->id !== $user->id && !$currentUser->isSuperAdmin()) {
                if (
                    !$currentUser->canAccessOrganization($user->organization_id) ||
                    !$currentUser->canAccessBranch($user->branch_id)
                ) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to update this user\'s profile image'
                    ], 403);
                }
            }

            if (!$request->hasFile('profile_image')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No profile image file provided'
                ], 422);
            }

            $imagePath = $this->handleFileUpload($request, 'profile_image', $user->profile_image, 'users/profile', $user->email);

            if ($imagePath) {
                $user->update(['profile_image' => $imagePath]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Profile image updated successfully',
                'data' => $user
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update profile image',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function removeProfileImage(string $id)
    {
        try {
            $currentUser = auth('api')->user();
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                    'data' => []
                ], 404);
            }

            // Check permissions
            if ($currentUser->id !== $user->id && !$currentUser->isSuperAdmin()) {
                if (
                    !$currentUser->canAccessOrganization($user->organization_id) ||
                    !$currentUser->canAccessBranch($user->branch_id)
                ) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to remove this user\'s profile image'
                    ], 403);
                }
            }

            if (!$user->profile_image) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No profile image to remove'
                ], 422);
            }

            $this->deleteFile($user->profile_image);
            $user->update(['profile_image' => null]);

            return response()->json([
                'status' => 'success',
                'message' => 'Profile image removed successfully',
                'data' => $user
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove profile image',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
