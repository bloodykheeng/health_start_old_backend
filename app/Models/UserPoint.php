<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'identifier',
        'hospital_id',
        'amount',
        'price',
        'payment_method',
        'details',
        'created_by',
        'updated_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
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