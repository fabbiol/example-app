<?php

namespace App\Enums;

enum ProductUnit: string
{
    case Ton = 'ton';
    case CubicMeter = 'm3';

    public function label(): string
    {
        return match ($this) {
            self::Ton => 't',
            self::CubicMeter => 'm³',
        };
    }
}
