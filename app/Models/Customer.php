<?php

namespace App\Models;
// use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'customers';

    protected $fillable = [
        'name',
        'phone_number',
        'password',
        'avatar',
        'is_active',
        'is_suspended',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean'
    ];


    public function routeNotificationForAlawael($notification = null)
    {
        return $this->phone_number;
    }

    public function verificationCode()
    {
        return $this->morphOne(VerificationCode::class, 'verifiable');
    }

    public function verificationCodes()
    {
        return $this->morphMany(VerificationCode::class, 'verifiable');
    }

    public function isActive()
    {
        return $this->is_active != false;
    }

    public function isSuspended()
    {
        return $this->is_suspended != false;
    }

    public static function phoneNumberExists(string $phoneNumber): bool
    {
        return self::where('phone_number', $phoneNumber)->exists();
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'customer_id', 'id');
    }
    public function scopeActiveCustomers($query)
    {
        return $query->where('is_active', 1)->where('is_suspended', 0);
    }


}
