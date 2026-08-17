<?php

namespace App\Models;

use App\Enums\CaixaType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $data
 * @property string $descricao
 * @property string $valor
 * @property CaixaType|string $tipo
 * @property string|null $metodo_pagamento
 * @property string|null $cliente_haver
 */
class CaixaEntry extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $table = 'caixa';

    /**
     * @var string
     */
    protected $connection = 'caixa';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'data',
        'descricao',
        'valor',
        'tipo',
        'metodo_pagamento',
        'cliente_haver',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'data' => 'date',
            'valor' => 'decimal:2',
            'tipo' => CaixaType::class,
            'cliente_haver' => 'decimal:2',
        ];
    }

    public static function isConfigured(): bool
    {
        $database = config('database.connections.caixa.database');

        return is_string($database) && $database !== '';
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithoutSaida(Builder $query): Builder
    {
        return $query->where('tipo', '!=', CaixaType::Saida->value);
    }

    public function orderNumber(): string
    {
        $number = trim((string) $this->descricao);

        return $number !== '' ? $number : (string) $this->id;
    }
}
