<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalService extends Model
{
    use HasFactory;

    protected $fillable = [
        'hospital_id',
        'service_id',
        'no_of_points',
        'created_by',
        'updated_by',
    ];

    // Relationships
    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
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