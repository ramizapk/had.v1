<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\HasMany;
class DeliveryAgent extends Model
{
    use HasApiTokens, HasFactory, SoftDeletes;

    protected $table = 'delivery_agents';

    protected $fillable = [
        'name',
        'address',
        'balance',
        'region',
        'password',
        'agent_no',
        'status',
        'is_active',
        'latitude',
        'longitude',
        'phone',
        'avatar'
    ];

    public function hasPassword(): bool
    {
        // dd($this->password);
        return $this->password !== null;
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'deleted_at' => 'datetime:Y-m-d',
    ];




    // public function orders()
    // {
    //     return $this->hasOne(Order::class, 'delivery_agent_id', 'id');
    // }

    public function scopeValidAgent($query)
    {
        return $query->where('is_active', true)->where('status', 'free');
    }

    // public function deliveries()
    // {
    //     return $this->hasMany(Delivery::class, 'delivery_agent_id', 'id');
    // }



    // public function AgentRatings(): HasMany
    // {
    //     return $this->hasMany(AgentRating::class, 'delivery_agent_id', 'id');
    // }
}
