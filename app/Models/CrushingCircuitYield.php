<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $crushing_circuit_id
 * @property int $product_id
 * @property string|null $group_name
 * @property string $percent
 * @property string|null $percent_min
 * @property string|null $percent_max
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CrushingCircuitYield extends Model
{
    protected $fillable = [
        'crushing_circuit_id',
        'product_id',
        'group_name',
        'percent',
        'percent_min',
        'percent_max',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'percent' => 'decimal:3',
            'percent_min' => 'decimal:3',
            'percent_max' => 'decimal:3',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CrushingCircuit, $this>
     */
    public function circuit(): BelongsTo
    {
        return $this->belongsTo(CrushingCircuit::class, 'crushing_circuit_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
