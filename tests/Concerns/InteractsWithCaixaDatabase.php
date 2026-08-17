<?php

namespace Tests\Concerns;

use App\Enums\CaixaType;
use App\Models\CaixaEntry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait InteractsWithCaixaDatabase
{
    protected function setUpInteractsWithCaixaDatabase(): void
    {
        if (config('database.connections.caixa.driver') !== 'sqlite') {
            return;
        }

        $schema = Schema::connection('caixa');

        if ($schema->hasTable('caixa')) {
            return;
        }

        $schema->create('caixa', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->date('data');
            $table->string('descricao');
            $table->decimal('valor', 10, 2);
            $table->string('tipo');
            $table->string('metodo_pagamento')->nullable();
            $table->decimal('cliente_haver', 10, 2)->nullable();
        });
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function createCaixaEntry(array $overrides = []): CaixaEntry
    {
        $this->setUpInteractsWithCaixaDatabase();

        $id = $overrides['id'] ?? ((int) CaixaEntry::query()->max('id')) + 1;

        return CaixaEntry::query()->create([
            'id' => $id,
            'data' => $overrides['data'] ?? now()->toDateString(),
            'descricao' => $overrides['descricao'] ?? 'Venda de brita',
            'valor' => $overrides['valor'] ?? '1500.00',
            'tipo' => $overrides['tipo'] ?? CaixaType::Pix->value,
            'metodo_pagamento' => $overrides['metodo_pagamento'] ?? null,
            'cliente_haver' => $overrides['cliente_haver'] ?? null,
        ]);
    }
}
