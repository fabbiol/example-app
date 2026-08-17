<?php

namespace App\Enums;

use App\Models\EstimatedLoadingItem;
use Illuminate\Support\Collection;

enum EstimatedLoadingStatus: string
{
    case Released = 'released';
    case Loading = 'loading';
    case Loaded = 'loaded';

    public function label(): string
    {
        return match ($this) {
            self::Released => 'Liberado',
            self::Loading => 'Carregando',
            self::Loaded => 'Carregado',
        };
    }

    /**
     * @param  Collection<int, EstimatedLoadingItem>  $items
     */
    public static function fromItems(Collection $items): self
    {
        if ($items->isEmpty()) {
            return self::Loaded;
        }

        $loadedCount = $items
            ->filter(fn (EstimatedLoadingItem $item): bool => $item->loader_loaded_at !== null)
            ->count();

        return match (true) {
            $loadedCount === 0 => self::Released,
            $loadedCount === $items->count() => self::Loaded,
            default => self::Loading,
        };
    }
}
