<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationSetting extends Model
{
    use HasFactory;

    protected $table = 'application_settings';

    protected $fillable = [
        'open_time',
        'close_time',
        'whatsapp_number',
        'contact_number',
        'facebook_page',
        'instagram_page',
        'twitter_page',
        'tiktok_page',
        'is_open',
        'closure_reason',
        'delivery_price_first_3km',
        'delivery_price_additional_per_km'
    ];

    protected $casts = [
        'is_open' => 'boolean',
    ];
}
