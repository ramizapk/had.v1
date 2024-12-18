<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ReturnStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'returns_id',
        'status',
        'changed_by',
    ];

    public function returns()
    {
        return $this->belongsTo(Returns::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
