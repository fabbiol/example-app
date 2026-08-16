<?php

namespace App\Enums;

enum ProductionShift: string
{
    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Night = 'night';

    public function label(): string
    {
        return match ($this) {
            self::Morning => 'Manhã',
            self::Afternoon => 'Tarde',
            self::Night => 'Noite',
        };
    }
}
