<?php

namespace App\Actions;

use App\Enums\CaixaType;
use App\Models\CaixaEntry;
use App\Models\EstimatedLoading;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ListAvailableCaixaEntries
{
    /**
     * @return array{
     *     entries: Collection<int, array{
     *         id: int,
     *         data: string,
     *         descricao: string,
     *         valor: string,
     *         tipo: string,
     *         tipo_label: string,
     *         metodo_pagamento: string|null,
     *         cliente_haver: string|null
     *     }>,
     *     error: string|null
     * }
     */
    public function handle(): array
    {
        if (! CaixaEntry::isConfigured()) {
            return [
                'entries' => collect(),
                'error' => 'O banco do caixa ainda não está configurado no .env (CAIXA_DB_*).',
            ];
        }

        try {
            $usedIds = Schema::hasColumn((new EstimatedLoading)->getTable(), 'caixa_id')
                ? EstimatedLoading::query()
                    ->whereNotNull('caixa_id')
                    ->pluck('caixa_id')
                    ->all()
                : [];

            $entries = CaixaEntry::query()
                ->withoutSaida()
                ->when($usedIds !== [], fn ($query) => $query->whereNotIn('id', $usedIds))
                ->orderByDesc('data')
                ->orderByRaw('descricao + 0 desc')
                ->orderByDesc('id')
                ->get(['id', 'data', 'descricao', 'valor', 'tipo', 'metodo_pagamento', 'cliente_haver'])
                ->map(fn (CaixaEntry $entry): array => [
                    'id' => (int) $entry->id,
                    'data' => $entry->data->toDateString(),
                    'descricao' => $entry->descricao,
                    'valor' => (string) $entry->valor,
                    'tipo' => $entry->tipo instanceof CaixaType
                        ? $entry->tipo->value
                        : (string) $entry->tipo,
                    'tipo_label' => $entry->tipo instanceof CaixaType
                        ? $entry->tipo->label()
                        : (string) $entry->tipo,
                    'metodo_pagamento' => $entry->metodo_pagamento,
                    'cliente_haver' => $entry->cliente_haver !== null ? (string) $entry->cliente_haver : null,
                ])
                ->values();

            return [
                'entries' => $entries,
                'error' => null,
            ];
        } catch (QueryException $exception) {
            Log::warning('Não foi possível ler os pedidos do caixa.', [
                'message' => $exception->getMessage(),
            ]);

            return [
                'entries' => collect(),
                'error' => 'Não foi possível conectar ao MySQL do caixa. Confira host, usuário e senha no .env.',
            ];
        }
    }
}
