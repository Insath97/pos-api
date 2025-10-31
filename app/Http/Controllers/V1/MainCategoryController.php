<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateMainCategoryRequest;
use App\Http\Requests\UpdateMainCategoryRequest;
use App\Models\MainCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MainCategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            $query = MainCategory::query();

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

            $query->orderBy('name', 'asc');

            $categories = $query->paginate($perPage);

            if ($categories->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No main categories found',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Main categories retrieved successfully',
                'data' => $categories
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve main categories',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function create() {}

    public function store(CreateMainCategoryRequest $request)
    {
        try {
            $data = $request->validated();

            $data['slug'] = Str::slug($data['name']);

            $category = MainCategory::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Main category created successfully',
                'data' => $category
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create main category',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $category = MainCategory::find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Main category not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Main category retrieved successfully',
                'data' => $category
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve main category',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function edit(string $id) {}

    public function update(UpdateMainCategoryRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            $category = MainCategory::find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Main category not found',
                    'data' => []
                ], 404);
            }

            // Generate slug if name changed and slug not provided
            if (isset($data['name']) && $data['name'] !== $category->name) {
                $data['slug'] = Str::slug($data['name']);
            }

            $category->update($data);
            $category->refresh();

            return response()->json([
                'status' => 'success',
                'message' => 'Main category updated successfully',
                'data' => $category
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update main category',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $category = MainCategory::find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Main category not found',
                    'data' => []
                ], 404);
            }

            $category->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Main category deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete main category',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function forceDestroy(string $id)
    {
        try {
            $category = MainCategory::withTrashed()->find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Main category not found',
                    'data' => []
                ], 404);
            }

            $category->forceDelete();

            return response()->json([
                'status' => 'success',
                'message' => 'Main category permanently deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to permanently delete main category',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $category = MainCategory::withTrashed()->find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Main category not found',
                    'data' => []
                ], 404);
            }

            if (!$category->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Main category is not deleted',
                    'data' => [
                        'category_id' => $category->id,
                        'category_name' => $category->name
                    ]
                ], 422);
            }

            $category->restore();

            return response()->json([
                'status' => 'success',
                'message' => 'Main category restored successfully',
                'data' => $category
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore main category',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function activateMainCategory(string $id)
    {
        try {
            $category = MainCategory::find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Main category not found',
                    'data' => []
                ], 404);
            }

            if ($category->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Main category is already active',
                    'data' => [
                        'current_status' => 'active',
                        'category_id' => $category->id,
                        'category_name' => $category->name
                    ]
                ], 422);
            }

            $category->activate();

            return response()->json([
                'status' => 'success',
                'message' => 'Main category activated successfully',
                'data' => $category
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate main category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deactivateMainCategory(string $id)
    {
        try {
            $category = MainCategory::find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Main category not found',
                    'data' => []
                ], 404);
            }

            if (!$category->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Main category is already inactive',
                    'data' => [
                        'current_status' => 'inactive',
                        'category_id' => $category->id,
                        'category_name' => $category->name
                    ]
                ], 422);
            }

            $category->deactivate();

            return response()->json([
                'status' => 'success',
                'message' => 'Main category deactivated successfully',
                'data' => $category
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate main category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActiveCategories()
    {
        try {
            $categories = MainCategory::active()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);

            return response()->json([
                'status' => 'success',
                'message' => 'Active main categories retrieved successfully',
                'data' => $categories
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve active main categories',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
