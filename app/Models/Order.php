<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Models\Concerns\LogsActivity;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $product_id
 * @property string $quantity_requested
 * @property string $quantity_loaded
 * @property OrderStatus $status
 * @property string|null $destination
 * @property string|null $vehicle_plate
 * @property Carbon|null $scheduled_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'customer_id',
    'product_id',
    'quantity_requested',
    'quantity_loaded',
    'status',
    'destination',
    'vehicle_plate',
    'scheduled_at',
    'notes',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, LogsActivity;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'quantity_loaded' => 0,
        'status' => 'open',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_requested' => 'decimal:3',
            'quantity_loaded' => 'decimal:3',
            'status' => OrderStatus::class,
            'scheduled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<WeighTicket, $this>
     */
    public function weighTickets(): HasMany
    {
        return $this->hasMany(WeighTicket::class);
    }

    public function remainingQuantity(): string
    {
        return bcsub((string) $this->quantity_requested, (string) $this->quantity_loaded, 3);
    }

    public function isOpenForLoading(): bool
    {
        return in_array($this->status, [
            OrderStatus::Open,
            OrderStatus::Scheduled,
            OrderStatus::Loading,
        ], true);
    }
}
