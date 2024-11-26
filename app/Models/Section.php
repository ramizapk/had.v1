<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Section extends Model
{
    use HasFactory;
    protected $table = 'sections';

    protected $fillable = [
        'name',
        'image',
        'publish',
        'created_by',
        'updated_by',
    ];

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class, 'section_id', 'id');
    }
}
