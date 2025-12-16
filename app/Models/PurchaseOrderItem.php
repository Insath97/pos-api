<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'variant_id',
        'quantity_ordered',
        'quantity_received',
        'quantity_pending',
        'unit_cost',
        'tax_rate',
        'discount_percentage',
        'line_total',
        'notes',
    ];

    protected $casts = [
        'quantity_ordered' => 'integer',
        'quantity_received' => 'integer',
        'quantity_pending' => 'integer',
        'unit_cost' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'line_total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function calculateLineTotal()
    {
        $subtotal = $this->quantity_ordered * $this->unit_cost;
        $discountAmount = $subtotal * ($this->discount_percentage / 100);
        $afterDiscount = $subtotal - $discountAmount;
        $taxAmount = $afterDiscount * ($this->tax_rate / 100);

        $this->line_total = $afterDiscount + $taxAmount;
        $this->save();

        return $this->line_total;
    }

    public function receiveQuantity($quantity)
    {
        $this->quantity_received += $quantity;
        $this->quantity_pending = $this->quantity_ordered - $this->quantity_received;
        $this->save();
    }

    public function isFullyReceived()
    {
        return $this->quantity_received >= $this->quantity_ordered;
    }

    public function isPartiallyReceived()
    {
        return $this->quantity_received > 0 && $this->quantity_received < $this->quantity_ordered;
    }

    public function getPendingQuantity()
    {
        return $this->quantity_ordered - $this->quantity_received;
    }

    public function getReceivalPercentage()
    {
        if ($this->quantity_ordered == 0) return 0;
        return round(($this->quantity_received / $this->quantity_ordered) * 100, 2);
    }
}
