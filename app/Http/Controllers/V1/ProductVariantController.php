<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductVariantRequest;
use App\Http\Requests\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantAuditLog;
use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    use FileUploadTrait;

    public function index(Request $request, string $product_id)
    {
        try {
            $product = Product::find($product_id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                    'data' => []
                ], 404);
            }
            $perPage = $request->get('per_page', 15);

            $query = ProductVariant::where('product_id', $product_id);

            // Search
            if ($request->has('search') && $request->search != '') {
                $query->search($request->search);
            }

            // Filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('color')) {
                $query->where('color', $request->color);
            }

            if ($request->has('size')) {
                $query->where('size', $request->size);
            }

            $variants = $query->with(['product:id,name,code'])->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Variants retrieved successfully',
                'data' => $variants
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve variants',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function create() {}

    public function store(CreateProductVariantRequest $request, string $product_id)
    {
        try {

            $product = Product::find($product_id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                    'data' => []
                ], 404);
            }

            $data = $request->validated();

            if (!isset($data['product_id'])) {
                $data['product_id'] = $product_id;
            }

            $imagePath = $this->handleFileUpload($request, 'image', null, 'product-variants', $data['sku'] ?? 'variant');
            if ($imagePath) {
                $data['image'] = $imagePath;
            }

            if (isset($data['is_default']) && $data['is_default']) {
                ProductVariant::where('product_id', $data['product_id'])
                    ->update(['is_default' => false]);
            }

            $variant = ProductVariant::create($data);

            // Log the creation
            ProductVariantAuditLog::create([
                'variant_id' => $variant->id,
                'user_id' => auth('api')->user()->id,
                'action' => 'created',
                'description' => "Variant '{$variant->sku}' was created",
                'new_values' => $variant->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Variant created successfully',
                'data' => $variant->load(['product:id,name,code'])
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create variant',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show(string $product_id, string $id)
    {
        try {
            $product = Product::find($product_id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                    'data' => []
                ], 404);
            }

            $variant = ProductVariant::with(['product:id,name,code'])->where('product_id', $product_id)->find($id);

            if (!$variant) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Variant not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Variant retrieved successfully',
                'data' => $variant
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve variant',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function edit(string $id) {}

    public function update(UpdateProductVariantRequest $request, string $product_id, string $id)
    {
        try {
            $product = Product::find($product_id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                    'data' => []
                ], 404);
            }

            $variant = ProductVariant::where('product_id', $product_id)->find($id);

            if (!$variant) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Variant not found',
                    'data' => []
                ], 404);
            }

            $oldValues = $variant->toArray();
            $data = $request->validated();

            $oldLogoPath = $variant->logo;
            $imagePath = $this->handleFileUpload($request, 'image', $oldLogoPath, 'product-variants', $data['sku'] ?? $variant->sku);

            if ($imagePath) {
                $data['image'] = $imagePath;
            }

            if (isset($data['is_default']) && $data['is_default']) {
                ProductVariant::where('product_id', $product_id)
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }

            $variant->update($data);
            $variant->refresh();

            // Log the update
            ProductVariantAuditLog::create([
                'variant_id' => $variant->id,
                'user_id' => auth('api')->user()->id,
                'action' => 'updated',
                'description' => "Variant '{$variant->sku}' was updated",
                'old_values' => $oldValues,
                'new_values' => $variant->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Variant updated successfully',
                'data' => $variant
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update variant',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $product_id, string $id)
    {
        try {
            $product = Product::find($product_id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                    'data' => []
                ], 404);
            }

            $variant = ProductVariant::where('product_id', $product_id)->find($id);

            if (!$variant) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Variant not found',
                    'data' => []
                ], 404);
            }

            $variantSku = $variant->sku;
            $variant->delete();

            // Log the deletion
            ProductVariantAuditLog::create([
                'variant_id' => $id,
                'user_id' => auth('api')->user()->id,
                'action' => 'soft deleted',
                'description' => "Variant '{$variantSku}' was soft deleted",
                'old_values' => $variant->toArray(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Variant deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete variant',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function forceDestroy(string $product_id, string $id)
    {
        try {
            $product = Product::find($product_id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                    'data' => []
                ], 404);
            }

            $variant = ProductVariant::withTrashed()->where('product_id', $product_id)->find($id);

            if (!$variant) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Variant not found',
                    'data' => []
                ], 404);
            }

            $variantSku = $variant->sku;
            $variant->forceDelete();

            // Log the deletion
            ProductVariantAuditLog::create([
                'variant_id' => $id,
                'user_id' => auth('api')->user()->id,
                'action' => 'force deleted',
                'description' => "Variant '{$variantSku}' was force deleted",
                'old_values' => $variant->toArray(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Variant permanently deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to permanently delete variant',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function restore(string $product_id, string $id)
    {
        try {
            $product = Product::find($product_id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                    'data' => []
                ], 404);
            }

            $variant = ProductVariant::withTrashed()->where('product_id', $product_id)->find($id);

            if (!$variant) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Variant not found',
                    'data' => []
                ], 404);
            }

            $variant->restore();

            ProductVariantAuditLog::create([
                'variant_id' => $variant->id,
                'user_id' => auth('api')->user()->id,
                'action' => 'restored',
                'description' => "Variant '{$variant->sku}' was restored",
                'new_values' => $variant->toArray(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Variant restored successfully',
                'data' => ['id' => $variant->id, 'sku' => $variant->sku, 'code' => $variant->code, 'product_id' => $variant->product_id, 'product_name' => $product->name]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore variant',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function activate(string $product_id, string $id)
    {
        try {
            $product = Product::find($product_id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                    'data' => []
                ], 404);
            }

            $variant = ProductVariant::where('product_id', $product_id)->find($id);

            if (!$variant) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Variant not found',
                    'data' => []
                ], 404);
            }

            if ($variant->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Variant is already active',
                    'data' => []
                ], 422);
            }

            $variant->activate();

            ProductVariantAuditLog::create([
                'variant_id' => $variant->id,
                'user_id' => auth('api')->user()->id,
                'action' => 'activated',
                'description' => "Variant '{$variant->sku}' was activated",
                'old_values' => ['is_active' => false],
                'new_values' => ['is_active' => true],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Variant activated successfully',
                'data' => $variant
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate variant',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function deactivate(string $product_id, string $id)
    {
        try {
            $product = Product::find($product_id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                    'data' => []
                ], 404);
            }

            $variant = ProductVariant::where('product_id', $product_id)->find($id);

            if (!$variant) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Variant not found',
                    'data' => []
                ], 404);
            }

            if (!$variant->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Variant is already inactive',
                    'data' => []
                ], 422);
            }

            $variant->deactivate();

            // Log the deactivation
            ProductVariantAuditLog::create([
                'variant_id' => $variant->id,
                'user_id' => auth('api')->user()->id,
                'action' => 'deactivated',
                'description' => "Variant '{$variant->sku}' was deactivated",
                'old_values' => ['is_active' => true],
                'new_values' => ['is_active' => false],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Variant deactivated successfully',
                'data' => $variant
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate variant',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function setAsDefault(string $product_id, string $id)
    {
        try {
            $product = Product::find($product_id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                    'data' => []
                ], 404);
            }

            $variant = ProductVariant::where('product_id', $product_id)->find($id);

            if (!$variant) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Variant not found',
                    'data' => []
                ], 404);
            }

            $variant->setAsDefault();

            return response()->json([
                'status' => 'success',
                'message' => 'Variant set as default successfully',
                'data' => $variant
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to set variant as default',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
