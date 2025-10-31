<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSubCategoryRequest;
use App\Http\Requests\UpdateSubCategoryRequest;
use App\Models\MainCategory;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubCategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            $query = SubCategory::with('mainCategory');

            // Search
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%")
                        ->orWhere('slug', 'LIKE', "%{$search}%");
                });
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('main_category_id')) {
                $query->where('main_category_id', $request->main_category_id);
            }

            $query->orderBy('name', 'asc');

            $subCategories = $query->paginate($perPage);

            if ($subCategories->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No sub categories found',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Sub categories retrieved successfully',
                'data' => $subCategories
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve sub categories',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function create() {}

    public function store(CreateSubCategoryRequest $request)
    {
        try {
            $data = $request->validated();

            $mainCategory = MainCategory::find($data['main_category_id']);
            if (!$mainCategory) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Main category not found',
                    'data' => []
                ], 404);
            }

            if (!$mainCategory->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot create sub category for inactive main category',
                    'data' => []
                ], 422);
            }

            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $subCategory = SubCategory::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Sub category created successfully',
                'data' => $subCategory->load('mainCategory')
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create sub category',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $subCategory = SubCategory::with('mainCategory')->find($id);

            if (!$subCategory) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sub category not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Sub category retrieved successfully',
                'data' => $subCategory
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve sub category',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function edit(string $id) {}

    public function update(UpdateSubCategoryRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            $subCategory = SubCategory::find($id);

            if (!$subCategory) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sub category not found',
                    'data' => []
                ], 404);
            }

            if (isset($data['main_category_id']) && $data['main_category_id'] != $subCategory->main_category_id) {
                $mainCategory = MainCategory::find($data['main_category_id']);
                if (!$mainCategory) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Main category not found',
                        'data' => []
                    ], 404);
                }

                if (!$mainCategory->is_active) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot move sub category to inactive main category',
                        'data' => []
                    ], 422);
                }
            }

            if (isset($data['name']) && $data['name'] !== $subCategory->name) {
                $data['slug'] = Str::slug($data['name']);
            }

            $subCategory->update($data);
            $subCategory->refresh();

            return response()->json([
                'status' => 'success',
                'message' => 'Sub category updated successfully',
                'data' => $subCategory->load('mainCategory')
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update sub category',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $subCategory = SubCategory::find($id);

            if (!$subCategory) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sub category not found',
                    'data' => []
                ], 404);
            }

            $subCategory->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Sub category deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete sub category',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function forceDestroy(string $id)
    {
        try {
            $subCategory = SubCategory::withTrashed()->find($id);

            if (!$subCategory) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sub category not found',
                    'data' => []
                ], 404);
            }

            $subCategory->forceDelete();

            return response()->json([
                'status' => 'success',
                'message' => 'Sub category permanently deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to permanently delete sub category',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $subCategory = SubCategory::withTrashed()->find($id);

            if (!$subCategory) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sub category not found',
                    'data' => []
                ], 404);
            }

            if (!$subCategory->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sub category is not deleted',
                    'data' => [
                        'sub_category_id' => $subCategory->id,
                        'sub_category_name' => $subCategory->name
                    ]
                ], 422);
            }

            $subCategory->restore();
            $subCategory->load('mainCategory');

            return response()->json([
                'status' => 'success',
                'message' => 'Sub category restored successfully',
                'data' => $subCategory
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore sub category',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function activateSubCategory(string $id)
    {
        try {
            $subCategory = SubCategory::find($id);

            if (!$subCategory) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sub category not found',
                    'data' => []
                ], 404);
            }

            if ($subCategory->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sub category is already active',
                    'data' => [
                        'current_status' => 'active',
                        'sub_category_id' => $subCategory->id,
                        'sub_category_name' => $subCategory->name
                    ]
                ], 422);
            }

            // Check if main category is active
            if (!$subCategory->mainCategory->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot activate sub category. Main category is inactive.',
                    'data' => []
                ], 422);
            }

            $subCategory->activate();
            $subCategory->load('mainCategory');

            return response()->json([
                'status' => 'success',
                'message' => 'Sub category activated successfully',
                'data' => $subCategory
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate sub category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deactivateSubCategory(string $id)
    {
        try {
            $subCategory = SubCategory::find($id);

            if (!$subCategory) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sub category not found',
                    'data' => []
                ], 404);
            }

            if (!$subCategory->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sub category is already inactive',
                    'data' => [
                        'current_status' => 'inactive',
                        'sub_category_id' => $subCategory->id,
                        'sub_category_name' => $subCategory->name
                    ]
                ], 422);
            }

            $subCategory->deactivate();
            $subCategory->load('mainCategory');

            return response()->json([
                'status' => 'success',
                'message' => 'Sub category deactivated successfully',
                'data' => $subCategory
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate sub category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByMainCategory(string $mainCategoryId)
    {
        try {
            $mainCategory = MainCategory::find($mainCategoryId);

            if (!$mainCategory) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Main category not found',
                    'data' => []
                ], 404);
            }

            $subCategories = SubCategory::with('mainCategory')
                ->where('main_category_id', $mainCategoryId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Sub categories retrieved successfully',
                'data' => $subCategories
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve sub categories',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function getActiveSubCategories()
    {
        try {
            $subCategories = SubCategory::with('mainCategory')
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'main_category_id']);

            return response()->json([
                'status' => 'success',
                'message' => 'Active sub categories retrieved successfully',
                'data' => $subCategories
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve active sub categories',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
