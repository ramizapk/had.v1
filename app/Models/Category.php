<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Category extends Model
{
    use HasFactory;
    protected $table = 'categories';

    protected $fillable = [
        'name',
        'return_able',
        'publish',
        'order',
        'section_id',
        'created_by',
        'updated_by',
        'is_offer',
    ];

    public static function updateOrder($id = null, $newOrder, $sectionId)
    {
        // Find the category if updating an existing one
        $category = $id ? self::find($id) : null;

        // Handle case for new order being null (or invalid)
        if ($newOrder === null || $newOrder < 1) {
            $newOrder = 1;
        }

        // Reorder for an existing category
        if ($category) {
            // Remove the current category from the order list temporarily
            $categories = self::where('section_id', $sectionId)
                ->where('id', '!=', $category->id)
                ->orderBy('order')
                ->get();

            // Re-insert the current category at the new order position
            $categories->splice($newOrder - 1, 0, [$category]);

            // Update the order column for all categories
            foreach ($categories as $index => $cat) {
                $cat->order = $index + 1;
                $cat->save();
            }
        } else {
            // Adding a new category
            $categories = self::where('section_id', $sectionId)
                ->orderBy('order')
                ->get();

            // Insert the new category into the list at the specified order
            $newCategory = new self([
                'section_id' => $sectionId,
                'order' => $newOrder, // Temporary value, will be recalculated
                // Add other necessary fields
            ]);

            $categories->splice($newOrder - 1, 0, [$newCategory]);

            // Update the order column for all categories including the new one
            foreach ($categories as $index => $cat) {
                $cat->order = $index + 1;
                $cat->save();
            }
        }

        return true;
    }



    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'category_vendor', 'category_id', 'vendor_id')
            ->withPivot('show_in_menu');
    }

    // public function products(): HasMany
    // {
    //     return $this->hasMany(ProductItem::class, 'category_id', 'id');
    // }
}
