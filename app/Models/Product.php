<?php

namespace App\Models;

use App\Enums\ProductUnit;
use App\Models\Concerns\LogsActivity;
use App\Support\StoneQuantityConverter;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property ProductUnit $unit
 * @property string $density
 * @property string $bucket_capacity_m3
 * @property string $stock_quantity
 * @property bool $is_active
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'code',
    'unit',
    'density',
    'bucket_capacity_m3',
    'stock_quantity',
    'is_active',
    'notes',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, LogsActivity;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'unit' => 'ton',
        'density' => 1.45,
        'bucket_capacity_m3' => 1.5,
        'stock_quantity' => 0,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit' => ProductUnit::class,
            'density' => 'decimal:3',
            'bucket_capacity_m3' => 'decimal:3',
            'stock_quantity' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return list<string>
     */
    public function activityIgnoredAttributes(): array
    {
        return ['updated_at', 'remember_token', 'stock_quantity'];
    }

    public function converter(): StoneQuantityConverter
    {
        return StoneQuantityConverter::make((float) $this->density);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<WeighTicket, $this>
     */
    public function weighTickets(): HasMany
    {
        return $this->hasMany(WeighTicket::class);
    }

    /**
     * @return HasMany<EstimatedLoading, $this>
     */
    public function estimatedLoadings(): HasMany
    {
        return $this->hasMany(EstimatedLoading::class);
    }

    /**
     * @return HasMany<ProductionEntry, $this>
     */
    public function productionEntries(): HasMany
    {
        return $this->hasMany(ProductionEntry::class);
    }
}
