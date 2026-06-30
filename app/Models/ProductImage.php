<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'image', 'sort_order'];
    protected $appends = ['image_url'];
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function getImageUrlAttribute(): ?string
    {
        return $this->image
            ? url('api/storage/' . $this->image)
            : null;
    }
}
