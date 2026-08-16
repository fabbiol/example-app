<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Operator = 'operator';
    case Driver = 'driver';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrativo',
            self::Operator => 'Operador da pá',
            self::Driver => 'Motorista',
        };
    }
}
