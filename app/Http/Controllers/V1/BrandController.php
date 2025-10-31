<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            $query = Brand::query();

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

            $brands = $query->paginate($perPage);

            if ($brands->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No brands found',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Brands retrieved successfully',
                'data' => $brands
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve brands',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function create() {}

    public function store(CreateBrandRequest $request)
    {
        try {
            $data = $request->validated();

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $brand = Brand::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Brand created successfully',
                'data' => $brand
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create brand',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $brand = Brand::find($id);

            if (!$brand) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Brand not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Brand retrieved successfully',
                'data' => $brand
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve brand',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function edit(string $id) {}

    public function update(UpdateBrandRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            $brand = Brand::find($id);

            if (!$brand) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Brand not found',
                    'data' => []
                ], 404);
            }

            // Generate slug if name changed
            if (isset($data['name']) && $data['name'] !== $brand->name) {
                $data['slug'] = Str::slug($data['name']);
            }

            $brand->update($data);
            $brand->refresh();

            return response()->json([
                'status' => 'success',
                'message' => 'Brand updated successfully',
                'data' => $brand
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update brand',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $brand = Brand::find($id);

            if (!$brand) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Brand not found',
                    'data' => []
                ], 404);
            }

            $brand->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Brand deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete brand',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function forceDestroy(string $id)
    {
        try {
            $brand = Brand::withTrashed()->find($id);

            if (!$brand) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Brand not found',
                    'data' => []
                ], 404);
            }

            $brand->forceDelete();

            return response()->json([
                'status' => 'success',
                'message' => 'Brand permanently deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to permanently delete brand',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $brand = Brand::withTrashed()->find($id);

            if (!$brand) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Brand not found',
                    'data' => []
                ], 404);
            }

            if (!$brand->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Brand is not deleted',
                    'data' => [
                        'brand_id' => $brand->id,
                        'brand_name' => $brand->name
                    ]
                ], 422);
            }

            $brand->restore();

            return response()->json([
                'status' => 'success',
                'message' => 'Brand restored successfully',
                'data' => $brand
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore brand',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function activateBrand(string $id)
    {
        try {
            $brand = Brand::find($id);

            if (!$brand) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Brand not found',
                    'data' => []
                ], 404);
            }

            if ($brand->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Brand is already active',
                    'data' => [
                        'current_status' => 'active',
                        'brand_id' => $brand->id,
                        'brand_name' => $brand->name
                    ]
                ], 422);
            }

            $brand->activate();

            return response()->json([
                'status' => 'success',
                'message' => 'Brand activated successfully',
                'data' => $brand
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate brand',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deactivateBrand(string $id)
    {
        try {
            $brand = Brand::find($id);

            if (!$brand) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Brand not found',
                    'data' => []
                ], 404);
            }

            if (!$brand->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Brand is already inactive',
                    'data' => [
                        'current_status' => 'inactive',
                        'brand_id' => $brand->id,
                        'brand_name' => $brand->name
                    ]
                ], 422);
            }

            $brand->deactivate();

            return response()->json([
                'status' => 'success',
                'message' => 'Brand deactivated successfully',
                'data' => $brand
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate brand',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActiveBrands()
    {
        try {
            $brands = Brand::active()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);

            return response()->json([
                'status' => 'success',
                'message' => 'Active brands retrieved successfully',
                'data' => $brands
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve active brands',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
