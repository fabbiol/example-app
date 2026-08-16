<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\WeighTicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $number
 * @property int|null $order_id
 * @property int $customer_id
 * @property int $product_id
 * @property int|null $user_id
 * @property string $vehicle_plate
 * @property string $tare_weight
 * @property string $gross_weight
 * @property string $net_weight
 * @property string $quantity
 * @property string|null $quantity_m3
 * @property string|null $density
 * @property Carbon $weighed_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'number',
    'order_id',
    'customer_id',
    'product_id',
    'user_id',
    'vehicle_plate',
    'tare_weight',
    'gross_weight',
    'net_weight',
    'quantity',
    'quantity_m3',
    'density',
    'weighed_at',
    'notes',
])]
class WeighTicket extends Model
{
    /** @use HasFactory<WeighTicketFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tare_weight' => 'decimal:3',
            'gross_weight' => 'decimal:3',
            'net_weight' => 'decimal:3',
            'quantity' => 'decimal:3',
            'quantity_m3' => 'decimal:3',
            'density' => 'decimal:3',
            'weighed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
