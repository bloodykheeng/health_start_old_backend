<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitService extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'hospital_services_id',
        'created_by',
        'updated_by',
    ];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function hospitalService()
    {
        return $this->belongsTo(HospitalService::class, 'hospital_services_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}