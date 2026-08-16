<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\CrushingCircuitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property bool $is_default
 * @property bool $is_active
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CrushingCircuit extends Model
{
    /** @use HasFactory<CrushingCircuitFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'is_default',
        'is_active',
        'notes',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_default' => false,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CrushingCircuitYield, $this>
     */
    public function yields(): HasMany
    {
        return $this->hasMany(CrushingCircuitYield::class)->orderBy('sort_order');
    }
}
