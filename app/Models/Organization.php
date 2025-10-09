<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'logo',
        'email',
        'phone',
        'website',
        'address',
        'city',
        'is_active',
        'is_multi_branch'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_multi_branch' => 'boolean',
        'deleted_at' => 'datetime'
    ];

    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }
}
