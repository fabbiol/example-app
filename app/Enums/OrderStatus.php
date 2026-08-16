<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Open = 'open';
    case Scheduled = 'scheduled';
    case Loading = 'loading';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberto',
            self::Scheduled => 'Agendado',
            self::Loading => 'Carregando',
            self::Completed => 'Concluído',
            self::Cancelled => 'Cancelado',
        };
    }
}
