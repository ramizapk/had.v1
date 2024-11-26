<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'vendors';
    protected $fillable = [
        'name',
        'phone_one',
        'phone_two',
        'email',
        'icon',
        'publish',
        'address',
        'latitude',
        'longitude',
        'section_id',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'publish' => 'boolean',
    ];



    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }
    public function workTimes(): HasMany
    {
        return $this->hasMany(WorkTime::class, 'vendor_id', 'id');
    }



    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }


    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_vendor', 'vendor_id', 'category_id')
            ->withPivot('show_in_menu');
    }
}
