<?php

namespace App\Models;

use App\Enums\ProductUnit;
use Database\Factories\EstimatedLoadingItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $estimated_loading_id
 * @property int $product_id
 * @property int $sort_order
 * @property ProductUnit $input_unit
 * @property string $quantity_m3
 * @property string $quantity_ton
 * @property string $quantity
 * @property string $density
 * @property Carbon|null $loader_loaded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'estimated_loading_id',
    'product_id',
    'sort_order',
    'input_unit',
    'quantity_m3',
    'quantity_ton',
    'quantity',
    'density',
    'loader_loaded_at',
])]
class EstimatedLoadingItem extends Model
{
    /** @use HasFactory<EstimatedLoadingItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'input_unit' => ProductUnit::class,
            'quantity_m3' => 'decimal:3',
            'quantity_ton' => 'decimal:3',
            'quantity' => 'decimal:3',
            'density' => 'decimal:3',
            'loader_loaded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EstimatedLoading, $this>
     */
    public function loading(): BelongsTo
    {
        return $this->belongsTo(EstimatedLoading::class, 'estimated_loading_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
