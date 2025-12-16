<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'po_number',
        'organization_id',
        'supplier_id',
        'branch_id',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_cost',
        'total_amount',
        'notes',
        'payment_terms',
        'delivery_address',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->organization_id && !$user->branch_id) {
            return $query->whereHas('branch', function ($q) use ($user) {
                $q->where('organization_id', $user->organization_id);
            });
        }

        if ($user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeForOrganization($query, $organizationId)
    {
        return $query->whereHas('branch', function ($q) use ($organizationId) {
            $q->where('organization_id', $organizationId);
        });
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeReceived($query)
    {
        return $query->whereIn('status', ['partially_received', 'received']);
    }

    public function calculateTotals()
    {
        $this->subtotal = $this->items->sum('line_total');
        $this->total_amount = $this->subtotal + $this->tax_amount + $this->shipping_cost - $this->discount_amount;
        $this->save();
    }

    public function approve($userId)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    public function markAsReceived()
    {
        // Check if all items are fully received
        $allReceived = $this->items->every(function ($item) {
            return $item->quantity_received >= $item->quantity_ordered;
        });

        $this->update([
            'status' => $allReceived ? 'received' : 'partially_received',
            'actual_delivery_date' => now(),
        ]);
    }

    public function getTotalQuantityOrdered()
    {
        return $this->items->sum('quantity_ordered');
    }

    public function getTotalQuantityReceived()
    {
        return $this->items->sum('quantity_received');
    }

    public function getReceivalPercentage()
    {
        $ordered = $this->getTotalQuantityOrdered();
        if ($ordered == 0) return 0;

        return round(($this->getTotalQuantityReceived() / $ordered) * 100, 2);
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isReceived()
    {
        return in_array($this->status, ['partially_received', 'received']);
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }
}
