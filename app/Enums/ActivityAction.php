<?php

namespace App\Enums;

enum ActivityAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case LoggedIn = 'logged_in';
    case LoggedOut = 'logged_out';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Criou',
            self::Updated => 'Atualizou',
            self::Deleted => 'Excluiu',
            self::LoggedIn => 'Entrou',
            self::LoggedOut => 'Saiu',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $action): array => [
                'value' => $action->value,
                'label' => $action->label(),
            ],
            self::cases(),
        );
    }
}
