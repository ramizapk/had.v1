<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class WalletTransaction extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'customer_id',
        'created_by',
        'amount',
        'transaction_type',
        'description',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
