<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vendor_id',
        'created_by',
        'updated_by',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    /**
     * Get the user who created the cart.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the cart.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}