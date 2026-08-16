<?php

namespace App\Enums;

enum ActivityDomain: string
{
    case Operational = 'operational';
    case Administrative = 'administrative';

    public function label(): string
    {
        return match ($this) {
            self::Operational => 'Operacional',
            self::Administrative => 'Administrativa',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $domain): array => [
                'value' => $domain->value,
                'label' => $domain->label(),
            ],
            self::cases(),
        );
    }
}
