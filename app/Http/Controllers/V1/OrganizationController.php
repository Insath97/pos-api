<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    use FileUploadTrait;

    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            $query = Organization::query();

            // Search
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('is_multi_branch')) {
                $query->where('is_multi_branch', $request->boolean('is_multi_branch'));
            }

            $query->orderBy('name', 'asc');

            $organizations = $query->paginate($perPage);

            if ($organizations->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No organizations found',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Organizations retrieved successfully',
                'data' => $organizations
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve organizations',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function create() {}

    public function store(CreateOrganizationRequest $request)
    {
        try {
            $data = $request->validated();

            $imagePath = $this->handleFileUpload($request, 'logo', null, 'organization/logo', $data['code'] ?? '');
            if ($imagePath) {
                $data['logo'] = $imagePath ?? null;
            }

            $organization = Organization::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Organization created successfully',
                'data' => $organization
            ], 201);
        } catch (\Throwable $th) {

            if (isset($imagePath)) {
                $this->deleteFile($imagePath);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create organization',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $organization = Organization::find($id);

            if (!$organization) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Organization not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Organization retrieved successfully',
                'data' => $organization
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve organization',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function edit(string $id) {}

    public function update(UpdateOrganizationRequest $request, string $id)
    {
        try {
            $organization = Organization::find($id);

            if (!$organization) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Organization not found',
                    'data' => []
                ], 404);
            }

            $data = $request->validated();

            $oldLogoPath = $organization->logo;
            $imagePath = $this->handleFileUpload($request, 'logo', $oldLogoPath, 'organization/logo', $data['code'] ?? $organization->code);

            if ($imagePath) {
                $data['logo'] = $imagePath;
            }

            $organization->update($data);
            $organization->refresh();

            return response()->json([
                'status' => 'success',
                'message' => 'Organization updated successfully',
                'data' => $organization
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update organization',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $organization = Organization::withTrashed()->find($id);

            /*   if ($organization->users()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete organization with associated users'
                ], 422);
            } */
            if (!$organization) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Organization not found',
                    'data' => []
                ], 404);
            }

            $organization->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Organization deleted successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete organization',
                'error' =>  $th->getMessage()
            ], 500);
        }
    }

    public function forceDestroy(string $id)
    {
        try {
            $organization = Organization::withTrashed()->find($id);

            if (!$organization) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Organization not found',
                    'data' => []
                ], 404);
            }

            if ($organization->logo) {
                $this->deleteFile($organization->logo);
            }

            $organization->forceDelete();

            return response()->json([
                'status' => 'success',
                'message' => 'Organization permanently deleted successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to permanently delete organization',
                'error' =>  $th->getMessage()
            ], 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $organization = Organization::withTrashed()->find($id);

            if (!$organization) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Organization not found',
                    'data' => []
                ], 404);
            }

            if (!$organization->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Organization is not deleted',
                    'data' => [
                        'organization_id' => $organization->id,
                        'organization_name' => $organization->name
                    ]
                ], 422);
            }

            $organization->restore();

            return response()->json([
                'status' => 'success',
                'message' => 'Organization restored successfully',
                'data' => $organization
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore organization',
                'error' =>  $th->getMessage()
            ], 500);
        }
    }

    public function activateOrganization(string $id)
    {
        try {
            $organization = Organization::find($id);

            if (!$organization) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Organization not found',
                    'data' => []
                ], 404);
            }

            if ($organization->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Organization is already active',
                    'data' => [
                        'current_status' => 'active',
                        'organization_id' => $organization->id,
                        'organization_name' => $organization->name
                    ]
                ], 422);
            }

            $organization->activate();

            return response()->json([
                'status' => 'success',
                'message' => 'Organization activated successfully',
                'data' => $organization
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate organization',
                'error' =>  $e->getMessage()
            ], 500);
        }
    }

    public function deactivateOrganization(string $id)
    {
        try {
            $organization = Organization::find($id);

            if (!$organization) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Organization not found',
                    'data' => []
                ], 404);
            }

            if (!$organization->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Organization is already inactive',
                    'data' => [
                        'current_status' => 'inactive',
                        'organization_id' => $organization->id,
                        'organization_name' => $organization->name
                    ]
                ], 422);
            }

            $organization->deactivate();

            return response()->json([
                'status' => 'success',
                'message' => 'Organization deactivated successfully',
                'data' => $organization
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate organization',
                'error' =>  $e->getMessage()
            ], 500);
        }
    }

    public function removeLogo(string $id)
    {
        try {
            $organization = Organization::find($id);

            if (!$organization) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Organization not found',
                    'data' => []
                ], 404);
            }

            if (!$organization->logo) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No logo to remove',
                    'data' => [
                        'organization_id' => $organization->id,
                        'organization_name' => $organization->name
                    ]
                ], 422);
            }

            $this->deleteFile($organization->logo);

            $organization->update(['logo' => null]);

            return response()->json([
                'status' => 'success',
                'message' => 'Logo removed successfully',
                'data' => $organization
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove logo',
                'error' =>  $th->getMessage()
            ], 500);
        }
    }

    public function updateLogo(Request $request, string $id)
    {
        try {
            $organization = Organization::find($id);

            if (!$organization) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Organization not found',
                    'data' => []
                ], 404);
            }

            if (!$request->hasFile('logo')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No logo file provided',
                    'data' => [
                        'organization_id' => $organization->id,
                        'organization_name' => $organization->name
                    ]
                ], 422);
            }

            $imagePath = $this->handleFileUpload($request, 'logo', $organization->logo, 'organization/logo', $organization->code ?? '');

            if ($imagePath) {
                $organization->update(['logo' => $imagePath]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Logo updated successfully',
                'data' => $organization
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update logo',
                'error' =>  $th->getMessage()
            ], 500);
        }
    }
}
