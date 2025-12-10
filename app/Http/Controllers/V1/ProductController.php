<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);

            $query = Product::query();

            // Search
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%")
                        ->orWhere('slug', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('is_variant')) {
                $query->where('is_variant', $request->boolean('is_variant'));
            }

            if ($request->has('brand_id')) {
                $query->where('brand_id', $request->brand_id);
            }

            if ($request->has('main_category_id')) {
                $query->where('main_category_id', $request->main_category_id);
            }

            if ($request->has('sub_category_id')) {
                $query->where('sub_category_id', $request->sub_category_id);
            }

            $query->with(['brand:id,name', 'mainCategory:id,name', 'subCategory:id,name', 'measurementUnit:id,name', 'unit:id,name', 'container:id,name'])
                ->orderBy('name', 'asc');

            $products = $query->paginate($perPage);

            if ($products->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'No products found',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Products retrieved successfully',
                'data' => $products
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve products',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function create() {}

    public function store(CreateProductRequest $request)
    {
        try {
            $data = $request->validated();

            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $product = Product::create($data);
            $product->load(['brand:id,name', 'mainCategory:id,name', 'subCategory:id,name', 'measurementUnit:id,name', 'unit:id,name', 'container:id,name']);

            ProductAuditLog::create([
                'product_id' => $product->id,
                'user_id' => auth('api')->user()->id,
                'action' => 'created',
                'description' => "Product '{$product->name}' was created",
                'new_values' => $product->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create product',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $product = Product::with(['brand:id,name', 'mainCategory:id,name', 'subCategory:id,name', 'measurementUnit:id,name', 'unit:id,name', 'container:id,name'])
                ->find($id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product retrieved successfully',
                'data' => $product
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve product',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function edit(string $id) {}

    public function update(UpdateProductRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                    'data' => []
                ], 404);
            }

            // Store old values for audit log
            $oldValues = $product->toArray();

            // Generate slug if name changed
            if (isset($data['name']) && $data['name'] !== $product->name) {
                $data['slug'] = Str::slug($data['name']);
            }

            $product->update($data);
            $product->refresh();
            $product->load(['brand:id,name', 'mainCategory:id,name', 'subCategory:id,name', 'measurementUnit:id,name', 'unit:id,name', 'container:id,name']);

            // Log the update
            ProductAuditLog::create([
                'product_id' => $product->id,
                'user_id' => auth('api')->user()->id,
                'action' => 'updated',
                'description' => "Product '{$product->name}' was updated",
                'old_values' => $oldValues,
                'new_values' => $product->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Product updated successfully',
                'data' => $product
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update product',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                    'data' => []
                ], 404);
            }

            $productName = $product->name;
            $product->delete();

            // Log the deletion
            ProductAuditLog::create([
                'product_id' => $id,
                'user_id' => auth('api')->user()->id,
                'action' => 'deleted',
                'description' => "Product '{$productName}' was soft deleted",
                'old_values' => $product->toArray(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Product deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete product',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function forceDestroy(string $id)
    {
        try {
            $product = Product::withTrashed()->find($id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                    'data' => []
                ], 404);
            }

            $productName = $product->name;
            $productData = $product->toArray();

            // Log the permanent deletion
            ProductAuditLog::create([
                'product_id' => null, // Product no longer exists
                'user_id' => auth('api')->user()->id,
                'action' => 'force_deleted',
                'description' => "Product '{$productName}' (ID: {$id}) was permanently deleted",
                'old_values' => $productData,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $product->forceDelete();

            return response()->json([
                'status' => 'success',
                'message' => 'Product permanently deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to permanently delete product',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $product = Product::withTrashed()->find($id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                    'data' => []
                ], 404);
            }

            if (!$product->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product is not deleted',
                    'data' => [
                        'product_id' => $product->id,
                        'product_name' => $product->name
                    ]
                ], 422);
            }

            $product->restore();

            // Log the restoration
            ProductAuditLog::create([
                'product_id' => $product->id,
                'user_id' => auth('api')->user()->id,
                'action' => 'restored',
                'description' => "Product '{$product->name}' was restored",
                'new_values' => $product->toArray(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Product restored successfully',
                'data' => $product
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore product',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function activateProduct(string $id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                    'data' => []
                ], 404);
            }

            if ($product->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product is already active',
                    'data' => [
                        'current_status' => 'active',
                        'product_id' => $product->id,
                        'product_name' => $product->name
                    ]
                ], 422);
            }

            $product->activate();

            // Log the activation
            ProductAuditLog::create([
                'product_id' => $product->id,
                'user_id' => auth('api')->user()->id,
                'action' => 'activated',
                'description' => "Product '{$product->name}' was activated",
                'old_values' => ['is_active' => false],
                'new_values' => ['is_active' => true],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Product activated successfully',
                'data' => $product
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deactivateProduct(string $id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                    'data' => []
                ], 404);
            }

            if (!$product->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product is already inactive',
                    'data' => [
                        'current_status' => 'inactive',
                        'product_id' => $product->id,
                        'product_name' => $product->name
                    ]
                ], 422);
            }

            $product->deactivate();

            // Log the deactivation
            ProductAuditLog::create([
                'product_id' => $product->id,
                'user_id' => auth('api')->user()->id,
                'action' => 'deactivated',
                'description' => "Product '{$product->name}' was deactivated",
                'old_values' => ['is_active' => true],
                'new_values' => ['is_active' => false],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Product deactivated successfully',
                'data' => $product
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActiveProducts()
    {
        try {
            $products = Product::active()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'slug']);

            return response()->json([
                'status' => 'success',
                'message' => 'Active products retrieved successfully',
                'data' => $products
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve active products',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
