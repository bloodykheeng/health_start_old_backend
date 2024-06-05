<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Hospital extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'address', 'points_percentage_value', 'photo_url', 'city', 'state', 'country', 'zip_code', 'phone_number', 'email', 'website', 'capacity', 'status', 'created_by', 'updated_by',
    ];

    // Relationship with HospitalUser pivot table
    public function hospitalUsers()
    {
        return $this->hasMany(HospitalUser::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function hospitalServices()
    {
        return $this->hasMany(HospitalService::class);
    }

    public function services()
    {
        // 'hospital_services': The name of the pivot table that links hospitals and services.
        // 'hospital_id': The foreign key in the pivot table referencing the visits table.
        // 'service_id': The foreign key in the pivot table referencing the services table.
        return $this->belongsToMany(Service::class, 'hospital_services', 'hospital_id', 'service_id');
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

    protected static function booted()
    {
        static::creating(function ($item) {
            $item->slug = static::uniqueSlug($item->name);
        });
    }

    public static function uniqueSlug($string)
    {
        $baseSlug = Str::slug($string, '-');
        if (static::where('slug', $baseSlug)->doesntExist()) {
            return $baseSlug;
        }

        $counter = 1;
        // Limiting the counter to prevent infinite loops
        while ($counter < 1000) {
            $slug = "{$baseSlug}-{$counter}";
            if (static::where('slug', $slug)->doesntExist()) {
                return $slug;
            }
            $counter++;
        }

        // Fallback if reached 1000 iterations (should ideally never happen)
        return "{$baseSlug}-" . uniqid();
    }
}