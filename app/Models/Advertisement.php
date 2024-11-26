<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    use HasFactory;
    protected $table = 'advertisements';

    protected $fillable = [
        'name',
        'description',
        'image',
        'type',
        'vendor_id',
        'product_id',
        'target_link',
        'price',
        'status',
        'start_date',
        'end_date',
        'placement',
        'section_id',
        'created_by',
        'updated_by',
    ];
    protected $dates = ['start_date', 'end_date'];
    /**

     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }


    public function product()
    {
        return $this->belongsTo(Product::class);
    }


    public function section()
    {
        return $this->belongsTo(Section::class);
    }


    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }



}
