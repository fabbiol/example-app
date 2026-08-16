<?php

namespace App\Enums;

enum Permission: string
{
    case Dashboard = 'dashboard';
    case Flow = 'flow';
    case Activities = 'activities';
    case Orders = 'orders';
    case Loader = 'loader';
    case EstimatedLoadings = 'estimated-loadings';
    case WeighTickets = 'weigh-tickets';
    case Production = 'production';
    case Driver = 'driver';
    case CrushingCircuits = 'crushing-circuits';
    case Products = 'products';
    case Customers = 'customers';
    case Users = 'users';
    case Trucks = 'trucks';
    case Roles = 'roles';

    public function label(): string
    {
        return match ($this) {
            self::Dashboard => 'Dashboard',
            self::Flow => 'Fluxo',
            self::Activities => 'Atividades',
            self::Orders => 'Pedidos',
            self::Loader => 'Pá',
            self::EstimatedLoadings => 'Carregamentos',
            self::WeighTickets => 'Balança',
            self::Production => 'Produção',
            self::Driver => 'Motorista',
            self::CrushingCircuits => 'Circuito',
            self::Products => 'Produtos',
            self::Customers => 'Clientes',
            self::Users => 'Pessoas',
            self::Trucks => 'Caminhões',
            self::Roles => 'Papéis',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::Dashboard, self::Flow, self::Activities => 'Operação',
            self::Orders, self::Loader, self::EstimatedLoadings, self::WeighTickets => 'Expedição',
            self::Production, self::Driver, self::CrushingCircuits => 'Pátio',
            self::Products, self::Customers, self::Users, self::Trucks, self::Roles => 'Cadastros',
        };
    }

    public function routeName(): string
    {
        return match ($this) {
            self::Dashboard => 'dashboard',
            self::Flow => 'flow',
            self::Activities => 'activities.index',
            self::Orders => 'orders.index',
            self::Loader => 'loader.index',
            self::EstimatedLoadings => 'estimated-loadings.index',
            self::WeighTickets => 'weigh-tickets.index',
            self::Production => 'production.index',
            self::Driver => 'driver.index',
            self::CrushingCircuits => 'crushing-circuits.edit',
            self::Products => 'products.index',
            self::Customers => 'customers.index',
            self::Users => 'users.index',
            self::Trucks => 'trucks.index',
            self::Roles => 'roles.index',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<string, list<array{value: string, label: string}>>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::cases() as $permission) {
            $groups[$permission->group()][] = [
                'value' => $permission->value,
                'label' => $permission->label(),
            ];
        }

        return $groups;
    }
}
