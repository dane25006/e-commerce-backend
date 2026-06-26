<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'discount_type',
        'discount_value',
        'coupon_code',
        'product_id',
        'image',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'is_active'      => 'boolean',
        'starts_at'      => 'datetime',
        'ends_at'        => 'datetime',
    ];

    protected $appends = ['image_url'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image
            ? asset('storage/' . $this->image)
            : null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }
}
