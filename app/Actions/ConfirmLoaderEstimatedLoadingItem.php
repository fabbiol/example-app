<?php

namespace App\Actions;

use App\Models\EstimatedLoadingItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmLoaderEstimatedLoadingItem
{
    public function handle(EstimatedLoadingItem $item): EstimatedLoadingItem
    {
        return DB::transaction(function () use ($item) {
            $item = EstimatedLoadingItem::query()
                ->whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($item->loader_loaded_at !== null) {
                throw ValidationException::withMessages([
                    'item' => 'Este produto já foi carregado.',
                ]);
            }

            $item->update([
                'loader_loaded_at' => now(),
            ]);

            return $item->fresh(['product', 'loading.customer']) ?? $item;
        });
    }
}
