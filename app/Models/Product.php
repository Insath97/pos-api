<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'slug',
        'brand_id',
        'main_category_id',
        'sub_category_id',
        'measurement_id',
        'unit_id',
        'container_id',
        'description',
        'is_variant',
        'is_active'
    ];

    protected $casts = [
        'is_variant' => 'boolean',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime'
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function mainCategory()
    {
        return $this->belongsTo(MainCategory::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function measurementUnit()
    {
        return $this->belongsTo(MeasurementUnit::class, 'measurement_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function container()
    {
        return $this->belongsTo(Container::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(ProductAuditLog::class)->orderBy('created_at', 'desc');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'LIKE', "%{$search}%")
            ->orWhere('code', 'LIKE', "%{$search}%")
            ->orWhere('description', 'LIKE', "%{$search}%");
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }
}
