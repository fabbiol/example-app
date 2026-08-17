<?php

namespace App\Models;

use App\Enums\EstimatedLoadingStatus;
use App\Enums\ProductUnit;
use App\Models\Concerns\LogsActivity;
use Database\Factories\EstimatedLoadingFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $number
 * @property int|null $order_id
 * @property int|null $caixa_id
 * @property string|null $caixa_number
 * @property int $customer_id
 * @property int $product_id
 * @property int|null $user_id
 * @property string $vehicle_plate
 * @property int|null $buckets_count
 * @property string|null $bucket_capacity_m3
 * @property ProductUnit $input_unit
 * @property string $quantity_m3
 * @property string $quantity_ton
 * @property string $quantity
 * @property string $density
 * @property Carbon $loaded_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read EstimatedLoadingStatus $status
 */
#[Fillable([
    'number',
    'order_id',
    'caixa_id',
    'caixa_number',
    'customer_id',
    'product_id',
    'user_id',
    'vehicle_plate',
    'buckets_count',
    'bucket_capacity_m3',
    'input_unit',
    'quantity_m3',
    'quantity_ton',
    'quantity',
    'density',
    'loaded_at',
    'notes',
])]
#[Appends(['status'])]
class EstimatedLoading extends Model
{
    /** @use HasFactory<EstimatedLoadingFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'buckets_count' => 'integer',
            'bucket_capacity_m3' => 'decimal:3',
            'input_unit' => ProductUnit::class,
            'quantity_m3' => 'decimal:3',
            'quantity_ton' => 'decimal:3',
            'quantity' => 'decimal:3',
            'density' => 'decimal:3',
            'loaded_at' => 'datetime',
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

    /**
     * @return HasMany<EstimatedLoadingItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(EstimatedLoadingItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function referenceLabel(): string
    {
        if (is_string($this->caixa_number) && $this->caixa_number !== '') {
            return $this->number.' · Pedido #'.$this->caixa_number;
        }

        return $this->number !== '' ? $this->number : '#'.$this->getKey();
    }

    /**
     * @return Attribute<EstimatedLoadingStatus, never>
     */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): EstimatedLoadingStatus => EstimatedLoadingStatus::fromItems($this->items));
    }
}
