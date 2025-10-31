<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            $query = Branch::with('organization');

            // Search
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('city', 'LIKE', "%{$search}%")
                        ->orWhere('manager_name', 'LIKE', "%{$search}%");
                });
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('is_main_branch')) {
                $query->where('is_main_branch', $request->boolean('is_main_branch'));
            }

            if ($request->has('organization_id')) {
                $query->where('organization_id', $request->organization_id);
            }

            $query->orderBy('name', 'asc');

            $branches = $query->paginate($perPage);

            if ($branches->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No branches found',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Branches retrieved successfully',
                'data' => $branches
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve branches',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function create() {}

    public function store(CreateBranchRequest $request)
    {
        try {
            $data = $request->validated();

            $organization = Organization::find($data['organization_id']);
            if (!$organization) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Organization not found',
                    'data' => []
                ], 404);
            }

            if ($organization->branches()->count() >= 5) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Organization has reached the maximum number of branches',
                    'data' => []
                ], 400);
            }

            if ($organization->is_multi_branch == false) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Organization is not allowed to create multiple branches',
                    'data' => []
                ], 400);
            }

            $branch = Branch::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Branch created successfully',
                'data' => $branch->load('organization')
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create branch',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $branch = Branch::with('organization')->find($id);

            if (!$branch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Branch retrieved successfully',
                'data' => $branch
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve branch',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function edit(string $id) {}

    public function update(UpdateBranchRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            $branch = Branch::find($id);

            if (!$branch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch not found',
                    'data' => []
                ], 404);
            }

            if (isset($data['organization_id']) && $data['organization_id'] != $branch->organization_id) {
                $organization = Organization::find($data['organization_id']);
                if (!$organization) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Organization not found',
                        'data' => []
                    ], 404);
                }
            }

            $branch->update($data);

            $branch->refresh();

            return response()->json([
                'status' => 'success',
                'message' => 'Branch updated successfully',
                'data' => $branch->load('organization')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update branch',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $branch = Branch::find($id);

            if (!$branch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch not found',
                    'data' => []
                ], 404);
            }

            // Check if this is the main branch
            if ($branch->is_main_branch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete main branch. Set another branch as main first.',
                    'data' => []
                ], 422);
            }

            $branch->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Branch deleted successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete branch',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function forceDestroy(string $id)
    {
        try {
            $branch = Branch::withTrashed()->find($id);

            if (!$branch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch not found',
                    'data' => []
                ], 404);
            }

            $branch->forceDelete();

            return response()->json([
                'status' => 'success',
                'message' => 'Branch permanently deleted successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to permanently delete branch',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $branch = Branch::withTrashed()->find($id);

            if (!$branch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch not found',
                    'data' => []
                ], 404);
            }

            if (!$branch->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch is not deleted',
                    'data' => [
                        'branch_id' => $branch->id,
                        'branch_name' => $branch->name
                    ]
                ], 422);
            }

            $branch->restore();
            $branch->load('organization');

            return response()->json([
                'status' => 'success',
                'message' => 'Branch restored successfully',
                'data' => $branch
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore branch',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function activateBranch(string $id)
    {
        try {
            $branch = Branch::find($id);

            if (!$branch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch not found',
                    'data' => []
                ], 404);
            }

            if ($branch->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch is already active',
                    'data' => [
                        'current_status' => 'active',
                        'branch_id' => $branch->id,
                        'branch_name' => $branch->name
                    ]
                ], 422);
            }

            $branch->activate();
            $branch->load('organization');

            return response()->json([
                'status' => 'success',
                'message' => 'Branch activated successfully',
                'data' => $branch
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate branch',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deactivateBranch(string $id)
    {
        try {
            $branch = Branch::find($id);

            if (!$branch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch not found',
                    'data' => []
                ], 404);
            }

            if (!$branch->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch is already inactive',
                    'data' => [
                        'current_status' => 'inactive',
                        'branch_id' => $branch->id,
                        'branch_name' => $branch->name
                    ]
                ], 422);
            }

            $branch->deactivate();
            $branch->load('organization');

            return response()->json([
                'status' => 'success',
                'message' => 'Branch deactivated successfully',
                'data' => $branch
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate branch',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function setAsMainBranch(string $id)
    {
        try {
            $branch = Branch::find($id);

            if (!$branch) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Branch not found',
                    'data' => []
                ], 404);
            }

            if (!$branch->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only active branches can be set as main branch',
                    'data' => []
                ], 422);
            }

            // Update all branches in the same organization
            Branch::where('organization_id', $branch->organization_id)
                ->update(['is_main_branch' => false]);

            // Set this branch as main
            $branch->update(['is_main_branch' => true]);
            $branch->load('organization');

            return response()->json([
                'status' => 'success',
                'message' => 'Branch set as main branch successfully',
                'data' => $branch
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to set branch as main',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function getByOrganization(string $organizationId)
    {
        try {
            $organization = Organization::find($organizationId);

            if (!$organization) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Organization not found',
                    'data' => []
                ], 404);
            }

            $branches = Branch::where('organization_id', $organizationId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Branches retrieved successfully',
                'data' => $branches
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve branches',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
