<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $table = 'payment_methods';

    /**
     * الحقول القابلة للتعديل
     */
    protected $fillable = [
        'method_name',
        'account_name',
        'account_number',
        'company_logo',
    ];


}
