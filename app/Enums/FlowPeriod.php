<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum FlowPeriod: string
{
    case Today = 'today';
    case Yesterday = 'yesterday';
    case Last7Days = '7d';
    case Last30Days = '30d';
    case Week = 'week';
    case Month = 'month';
    case All = 'all';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Today => 'Hoje',
            self::Yesterday => 'Ontem',
            self::Last7Days => '7 dias',
            self::Last30Days => '30 dias',
            self::Week => 'Semana',
            self::Month => 'Mês',
            self::All => 'Tudo',
            self::Custom => 'Personalizado',
        };
    }

    /**
     * @return list<self>
     */
    public static function quickFilters(): array
    {
        return [
            self::Today,
            self::Yesterday,
            self::Last7Days,
            self::Last30Days,
            self::Week,
            self::Month,
            self::All,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $period): array => [
                'value' => $period->value,
                'label' => $period->label(),
            ],
            self::quickFilters(),
        );
    }

    /**
     * @return array{from: ?CarbonInterface, to: ?CarbonInterface}
     */
    public function range(?string $from = null, ?string $to = null): array
    {
        $today = now()->startOfDay();

        return match ($this) {
            self::Today => ['from' => $today, 'to' => $today],
            self::Yesterday => ['from' => $today->subDay(), 'to' => $today->subDay()],
            self::Last7Days => ['from' => $today->subDays(6), 'to' => $today],
            self::Last30Days => ['from' => $today->subDays(29), 'to' => $today],
            self::Week => [
                'from' => $today->startOfWeek(CarbonInterface::MONDAY),
                'to' => $today->endOfWeek(CarbonInterface::SUNDAY)->startOfDay(),
            ],
            self::Month => [
                'from' => $today->startOfMonth(),
                'to' => $today->endOfMonth()->startOfDay(),
            ],
            self::All => ['from' => null, 'to' => null],
            self::Custom => $this->customRange($from, $to),
        };
    }

    /**
     * @return array{from: CarbonInterface, to: CarbonInterface}
     */
    private function customRange(?string $from, ?string $to): array
    {
        $today = now()->startOfDay();
        $fromDate = $this->parseDate($from);
        $toDate = $this->parseDate($to);

        if ($fromDate === null && $toDate === null) {
            return ['from' => $today, 'to' => $today];
        }

        $fromDate ??= $toDate;
        $toDate ??= $fromDate;

        if ($fromDate->greaterThan($toDate)) {
            return ['from' => $toDate, 'to' => $fromDate];
        }

        return ['from' => $fromDate, 'to' => $toDate];
    }

    private function parseDate(?string $value): ?CarbonInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        return now()->parse($value)->startOfDay();
    }
}
