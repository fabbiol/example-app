<?php

namespace App\Enums;

enum ProductionStage: string
{
    case QuarryToPrimary = 'quarry_to_primary';
    case Plant = 'plant';

    public function label(): string
    {
        return match ($this) {
            self::QuarryToPrimary => 'Lavra → primário',
            self::Plant => 'Usina / produtos',
        };
    }
}
