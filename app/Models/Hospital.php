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
        'name', 'address', 'photo_url', 'city', 'state', 'country', 'zip_code', 'phone_number', 'email', 'website', 'capacity', 'status', 'created_by', 'updated_by',
    ];

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