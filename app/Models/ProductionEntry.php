<?php

namespace App\Models;

use App\Enums\ProductionMethod;
use App\Enums\ProductionShift;
use App\Enums\ProductionStage;
use App\Enums\ProductUnit;
use App\Models\Concerns\LogsActivity;
use Database\Factories\ProductionEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property int|null $crushing_circuit_id
 * @property bool $affects_stock
 * @property string|null $yield_percent
 * @property int $product_id
 * @property int|null $user_id
 * @property ProductionMethod $method
 * @property int|null $truck_id
 * @property int|null $trips_count
 * @property string|null $truck_capacity_m3
 * @property ProductUnit|null $input_unit
 * @property string $quantity
 * @property string|null $quantity_m3
 * @property string|null $quantity_ton
 * @property string|null $density
 * @property ProductionStage $stage
 * @property ProductionShift $shift
 * @property Carbon $produced_on
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'parent_id',
    'crushing_circuit_id',
    'affects_stock',
    'yield_percent',
    'product_id',
    'user_id',
    'method',
    'truck_id',
    'trips_count',
    'truck_capacity_m3',
    'input_unit',
    'quantity',
    'quantity_m3',
    'quantity_ton',
    'density',
    'stage',
    'shift',
    'produced_on',
    'notes',
])]
class ProductionEntry extends Model
{
    /** @use HasFactory<ProductionEntryFactory> */
    use HasFactory, LogsActivity;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'method' => 'quantity',
        'stage' => 'plant',
        'shift' => 'morning',
        'affects_stock' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'affects_stock' => 'boolean',
            'yield_percent' => 'decimal:3',
            'method' => ProductionMethod::class,
            'trips_count' => 'integer',
            'truck_capacity_m3' => 'decimal:3',
            'input_unit' => ProductUnit::class,
            'quantity' => 'decimal:3',
            'quantity_m3' => 'decimal:3',
            'quantity_ton' => 'decimal:3',
            'density' => 'decimal:3',
            'stage' => ProductionStage::class,
            'shift' => ProductionShift::class,
            'produced_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<ProductionEntry, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<ProductionEntry, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return BelongsTo<CrushingCircuit, $this>
     */
    public function crushingCircuit(): BelongsTo
    {
        return $this->belongsTo(CrushingCircuit::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Truck, $this>
     */
    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
