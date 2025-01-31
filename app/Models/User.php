<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'lastlogin',
        'photo_url',
        'agree',
        'phone',
        'dateOfBirth',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function hospitals()
    {
        // 'hospital_users': The name of the pivot table that links users and hospitals.
        // 'user_id': The foreign key in the pivot table referencing the users table.
        // 'hospital_id': The foreign key in the pivot table referencing the hospitals table.
        return $this->belongsToMany(Hospital::class, 'hospital_users', 'user_id', 'hospital_id');
    }

    // Relationship with HospitalUser
    public function hospitalUsers()
    {
        return $this->hasMany(HospitalUser::class, 'user_id');
    }

    protected static function booted()
    {
        static::creating(function ($user) {
            $user->slug = static::uniqueSlug($user->name);
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

    // mutator function that ensures only nulls are sent to unique field incase value is empty string
    public function setEmailAttribute($value)
    {
        if (empty($value)) { // will check for empty string
            $this->attributes['email'] = null;
        } else {
            $this->attributes['email'] = $value;
        }
    }

    public function setPhoneAttribute($value)
    {
        if (empty($value)) { // will check for empty string
            $this->attributes['phone'] = null;
        } else {
            $this->attributes['phone'] = $value;
        }
    }

    public function providers()
    {
        return $this->hasMany(ThirdPartyAuthProvider::class, 'user_id', 'id');
    }
}