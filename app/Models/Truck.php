<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\TruckFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $plate
 * @property string $capacity_m3
 * @property bool $is_active
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'plate', 'capacity_m3', 'is_active', 'notes'])]
class Truck extends Model
{
    /** @use HasFactory<TruckFactory> */
    use HasFactory, LogsActivity;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity_m3' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ProductionEntry, $this>
     */
    public function productionEntries(): HasMany
    {
        return $this->hasMany(ProductionEntry::class);
    }
}
