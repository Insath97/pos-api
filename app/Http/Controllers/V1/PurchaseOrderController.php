<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\Branch;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth('api')->user();
            $perPage = $request->get('per_page', 15);

            $query = PurchaseOrder::with(['items.variant.product:id,name,code', 'supplier:id,name,code', 'branch:id,name,code', 'organization:id,name,code'])->forUser($user);

            if ($request->has('search') && $request->search != '') {
                $query->where('po_number', 'LIKE', "%{$request->search}%");
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }

            if ($request->has('branch_id') && ($user->isSuperAdmin() || ($user->organization_id && !$user->branch_id))) {
                $query->where('branch_id', $request->branch_id);
            }

            $query->orderBy('order_date', 'desc');
            $purchaseOrders = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Purchase orders retrieved successfully',
                'data' => $purchaseOrders
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve purchase orders',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function create() {}

    public function store(CreatePurchaseOrderRequest $request)
    {
        try {
            $user = auth('api')->user();
            $data = $request->validated();

            if ($user->branch_id) {

                $data['organization_id'] = $user->organization_id;
                $data['branch_id'] = $user->branch_id;
            } else if ($user->organization_id) {

                $data['organization_id'] = $user->organization_id;

                if (isset($data['branch_id'])) {

                    $branch_id = Branch::find($data['branch_id']);

                    if (!$branch_id || $branch_id->organization_id != $user->organization_id) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Selected branch does not belong to your organization',
                        ], 403);
                    }
                }
            }

            $data['po_number'] = 'PO-' . date('Ymd') . '-' . str_pad(PurchaseOrder::count() + 1, 4, '0', STR_PAD_LEFT);
            $data['created_by'] = $user->id;

            $items = $data['items'];
            unset($data['items']);

            $purchaseOrder = PurchaseOrder::create($data);
            foreach ($items as $item) {
                $item['purchase_order_id'] = $purchaseOrder->id;
                $item['quantity_pending'] = $item['quantity_ordered'];

                $quantity = $item['quantity_ordered'];
                $unitCost = $item['unit_cost'];
                $taxRate = $item['tax_rate'] ?? 0;
                $discountPercentage = $item['discount_percentage'] ?? 0;

                $subtotal = $quantity * $unitCost;
                $discountAmount = $subtotal * ($discountPercentage / 100);
                $afterDiscount = $subtotal - $discountAmount;
                $taxAmount = $afterDiscount * ($taxRate / 100);
                $item['line_total'] = $afterDiscount + $taxAmount;

                PurchaseOrderItem::create($item);
            }

            $purchaseOrder->calculateTotals();
            $purchaseOrder->load(['items.variant.product:id,name,code', 'supplier:id,code,name', 'branch:id,code,name', 'organization:id,code,name', 'createdBy:id,name', 'approvedBy:id,name']);

            return response()->json([
                'status' => 'success',
                'message' => 'Purchase order created successfully',
                'data' => $purchaseOrder
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create purchase order',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $user = auth('api')->user();
            $purchaseOrder = PurchaseOrder::with(['items.variant.product:id,name,code', 'supplier:id,name,code', 'branch:id,name,code', 'organization:id,name,code', 'createdBy:id,name', 'approvedBy:id,name'])->find($id);

            if (!$purchaseOrder) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Purchase order not found',
                    'data' => []
                ], 404);
            }

            if (!$user->isSuperAdmin()) {
                if ($user->organization_id && !$user->branch_id) {
                    if ($purchaseOrder->branch->organization_id != $user->organization_id) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Access denied',
                            'data' => []
                        ], 403);
                    }
                }

                if ($user->branch_id && $purchaseOrder->branch_id != $user->branch_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Access denied',
                        'data' => []
                    ], 403);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Purchase order retrieved successfully',
                'data' => $purchaseOrder
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve purchase order',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function edit(string $id) {}

    public function update(UpdatePurchaseOrderRequest $request, string $id)
    {
        try {
            $user = auth('api')->user();

            $data = $request->validated();

            $purchaseOrder = PurchaseOrder::find($id);

            if (!$purchaseOrder) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Purchase order not found',
                    'data' => []
                ], 404);
            }

            if (!$user->isSuperAdmin()) {
                if ($user->organization_id && !$user->branch_id) {
                    if ($purchaseOrder->organization_id != $user->organization_id) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Access denied',
                            'data' => []
                        ], 403);
                    }
                }

                if ($user->branch_id && $purchaseOrder->branch_id != $user->branch_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Access denied',
                        'data' => []
                    ], 403);
                }
            }

            if ($purchaseOrder->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft purchase orders can be updated',
                    'data' => []
                ], 422);
            }

            if (isset($data['items'])) {
                $items = $data['items'];
                unset($data['items']);

                $purchaseOrder->items()->delete();

                foreach ($items as $item) {
                    $item['purchase_order_id'] = $purchaseOrder->id;
                    $item['quantity_pending'] = $item['quantity_ordered'];

                    $quantity = $item['quantity_ordered'];
                    $unitCost = $item['unit_cost'];
                    $taxRate = $item['tax_rate'] ?? 0;
                    $discountPercentage = $item['discount_percentage'] ?? 0;

                    $subtotal = $quantity * $unitCost;
                    $discountAmount = $subtotal * ($discountPercentage / 100);
                    $afterDiscount = $subtotal - $discountAmount;
                    $taxAmount = $afterDiscount * ($taxRate / 100);
                    $item['line_total'] = $afterDiscount + $taxAmount;

                    PurchaseOrderItem::create($item);
                }
            }

            $purchaseOrder->update($data);
            $purchaseOrder->calculateTotals();
            $purchaseOrder->load(['items.variant.product:id,name,code', 'supplier:id,code,name', 'branch:id,code,name', 'organization:id,code,name']);

            return response()->json([
                'status' => 'success',
                'message' => 'Purchase order updated successfully',
                'data' => $purchaseOrder
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update purchase order',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $user = auth('api')->user();
            $purchaseOrder = PurchaseOrder::find($id);

            if (!$purchaseOrder) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Purchase order not found',
                    'data' => []
                ], 404);
            }

            if (!$user->isSuperAdmin()) {
                if ($user->organization_id && !$user->branch_id) {
                    if ($purchaseOrder->organization_id != $user->organization_id) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Access denied',
                            'data' => []
                        ], 403);
                    }
                }

                if ($user->branch_id && $purchaseOrder->branch_id != $user->branch_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Access denied',
                        'data' => []
                    ], 403);
                }
            }

            if ($purchaseOrder->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft purchase orders can be deleted',
                    'data' => []
                ], 422);
            }

            $purchaseOrder->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Purchase order deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete purchase order',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $user = auth('api')->user();
            $purchaseOrder = PurchaseOrder::withTrashed()->find($id);

            if (!$purchaseOrder) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Purchase order not found',
                    'data' => []
                ], 404);
            }

            if (!$user->isSuperAdmin()) {
                if ($user->organization_id && !$user->branch_id) {
                    if ($purchaseOrder->organization_id != $user->organization_id) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Access denied',
                            'data' => []
                        ], 403);
                    }
                }

                if ($user->branch_id && $purchaseOrder->branch_id != $user->branch_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Access denied',
                        'data' => []
                    ], 403);
                }
            }

            if (!$purchaseOrder->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Purchase order is not deleted',
                    'data' => []
                ], 422);
            }

            $purchaseOrder->restore();

            return response()->json([
                'status' => 'success',
                'message' => 'Purchase order restored successfully',
                'data' =>  $purchaseOrder->load(['items.variant.product:id,name,code', 'supplier:id,code,name', 'branch:id,code,name', 'organization:id,code,name']),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore purchase order',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function forceDestroy(string $id)
    {
        try {
            $user = auth('api')->user();
            $purchaseOrder = PurchaseOrder::withTrashed()->find($id);

            if (!$purchaseOrder) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Purchase order not found'
                ], 404);
            }

            // Check access
            if (!$user->isSuperAdmin()) {
                if ($user->organization_id && !$user->branch_id) {
                    if ($purchaseOrder->organization_id != $user->organization_id) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Access denied'
                        ], 403);
                    }
                }

                if ($user->branch_id && $purchaseOrder->branch_id != $user->branch_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Access denied'
                    ], 403);
                }
            }

            if ($purchaseOrder->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft purchase orders can be deleted'
                ], 422);
            }

            $purchaseOrder->forceDelete();

            return response()->json([
                'status' => 'success',
                'message' => 'Purchase order permanently deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to permanently delete purchase order',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function submit(string $id)
    {
        try {
            $user = auth('api')->user();
            $purchaseOrder = PurchaseOrder::find($id);

            if (!$purchaseOrder) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Purchase order not found',
                    'data' => []
                ], 404);
            }

            // Check access
            if (!$user->isSuperAdmin()) {
                if ($user->organization_id && !$user->branch_id) {
                    if ($purchaseOrder->organization_id != $user->organization_id) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Access denied',
                            'data' => []
                        ], 403);
                    }
                }

                if ($user->branch_id && $purchaseOrder->branch_id != $user->branch_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Access denied',
                        'data' => []
                    ], 403);
                }
            }

            if ($purchaseOrder->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft purchase orders can be submitted',
                    'data' => []
                ], 422);
            }

            $purchaseOrder->update(['status' => 'pending']);

            return response()->json([
                'status' => 'success',
                'message' => 'Purchase order submitted successfully',
                'data' =>  $purchaseOrder->load(['items.variant.product:id,name,code', 'supplier:id,code,name', 'branch:id,code,name', 'organization:id,code,name', 'createdBy:id,name', 'approvedBy:id,name'])
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit purchase order',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function approve(string $id)
    {
        try {
            $user = auth('api')->user();
            $purchaseOrder = PurchaseOrder::find($id);

            if (!$purchaseOrder) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Purchase order not found',
                    'data' => []
                ], 404);
            }

            // Check access
            if (!$user->isSuperAdmin()) {
                if ($user->organization_id && !$user->branch_id) {
                    if ($purchaseOrder->organization_id != $user->organization_id) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Access denied',
                            'data' => []
                        ], 403);
                    }
                }

                if ($user->branch_id && $purchaseOrder->branch_id != $user->branch_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Access denied',
                        'data' => []
                    ], 403);
                }
            }

            if ($purchaseOrder->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only pending purchase orders can be approved',
                    'data' => []
                ], 422);
            }

            $purchaseOrder->approve($user->id);
            $purchaseOrder->refresh();

            return response()->json([
                'status' => 'success',
                'message' => 'Purchase order approved successfully',
                'data' =>  $purchaseOrder->load(['items.variant.product:id,name,code', 'supplier:id,code,name', 'branch:id,code,name', 'organization:id,code,name', 'createdBy:id,name', 'approvedBy:id,name'])
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve purchase order',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function cancel(string $id)
    {
        try {
            $user = auth('api')->user();
            $purchaseOrder = PurchaseOrder::find($id);

            if (!$purchaseOrder) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Purchase order not found',
                    'data' => []
                ], 404);
            }

            // Check access
            if (!$user->isSuperAdmin()) {
                if ($user->organization_id && !$user->branch_id) {
                    if ($purchaseOrder->organization_id != $user->organization_id) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Access denied',
                            'data' => []
                        ], 403);
                    }
                }

                if ($user->branch_id && $purchaseOrder->branch_id != $user->branch_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Access denied',
                        'data' => []
                    ], 403);
                }
            }

            if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot cancel this purchase order',
                    'data' => []
                ], 422);
            }

            $purchaseOrder->cancel();
            $purchaseOrder->refresh();

            return response()->json([
                'status' => 'success',
                'message' => 'Purchase order cancelled successfully',
                'data' =>  $purchaseOrder->load(['items.variant.product:id,name,code', 'supplier:id,code,name', 'branch:id,code,name', 'organization:id,code,name', 'createdBy:id,name', 'approvedBy:id,name'])
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel purchase order',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
