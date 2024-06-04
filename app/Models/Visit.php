<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'identifier',
        'hospital_id',
        'no_of_points',
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

    // Define a relationship to directly load hospitalService
    public function hospitalServices()
    {

        // 'HospitalService': The target model class representing hospital services.
        // 'VisitService': The intermediate model class connecting visits and hospital services.
        // 'visit_id': The foreign key on the intermediate model referencing the visits table.
        // 'id': The local key on the visits table.
        // 'id': The foreign key on the intermediate model referencing the hospital services table.
        // 'hospital_services_id': The local key on the hospital services table.
        return $this->hasManyThrough(HospitalService::class, VisitService::class, 'visit_id', 'id', 'id', 'hospital_services_id');
    }

    protected static function booted()
    {
        static::creating(function ($item) {
            $item->identifier = static::generateUniqueIdentifier();
        });
    }

    public static function generateUniqueIdentifier()
    {
        $baseIdentifier = Str::random(8) . '-' . now()->timestamp;

        if (static::where('identifier', $baseIdentifier)->doesntExist()) {
            return $baseIdentifier;
        }

        $counter = 1;
        // Limiting the counter to prevent infinite loops
        while ($counter < 1000) {
            $identifier = "{$baseIdentifier}-{$counter}";
            if (static::where('identifier', $identifier)->doesntExist()) {
                return $identifier;
            }
            $counter++;
        }

        // Fallback if reached 1000 iterations (should ideally never happen)
        return "{$baseIdentifier}-" . uniqid();
    }

}