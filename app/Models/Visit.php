<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'hospital_id',
        'start_date',
        'end_date',
        'purpose',
        'doctor_name',
        'details',
        'status',
        'created_by',
        'updated_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function services()
    {
        // 'visit_services': The name of the pivot table that links visits and services.
        // 'visit_id': The foreign key in the pivot table referencing the visits table.
        // 'service_id': The foreign key in the pivot table referencing the services table.
        return $this->belongsToMany(Service::class, 'visit_services', 'visit_id', 'service_id');
    }

    public function visitServices()
    {
        return $this->hasMany(VisitService::class);
    }
}