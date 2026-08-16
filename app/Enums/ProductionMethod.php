<?php

namespace App\Enums;

enum ProductionMethod: string
{
    case Trips = 'trips';
    case Quantity = 'quantity';
    case Scale = 'scale';

    public function label(): string
    {
        return match ($this) {
            self::Trips => 'Viagens (caçamba)',
            self::Quantity => 'Quantidade estimada',
            self::Scale => 'Balança',
        };
    }

    public function isAvailable(): bool
    {
        return $this !== self::Scale;
    }
}
