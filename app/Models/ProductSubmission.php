<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSubmission extends Model
{
    protected $fillable = [
        'external_id',
        'product_name',
        'quantity_in_stock',
        'price_per_item',
        'submitted_at',
    ];

    protected $casts = [
        'quantity_in_stock' => 'integer',
        'price_per_item' => 'decimal:2',
        'submitted_at' => 'datetime',
    ];
}
