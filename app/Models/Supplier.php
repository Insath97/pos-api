<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'company_name',
        'contact_person_name',
        'contact_person_phone',
        'alternate_phone',
        'address',
        'city',
        'state',
        'country',
        'phone',
        'whatsapp',
        'fax',
        'email',
        'website',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime'
    ];

    public function bankAccounts()
    {
        return $this->hasMany(SupplierBankAccount::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
