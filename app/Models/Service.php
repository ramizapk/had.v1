<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'image', 'whatsapp_link', 'service_type_id'];

    // العلاقة مع نوع الخدمة
    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }
}
