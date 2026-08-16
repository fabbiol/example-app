<?php

namespace App\Support;

use App\Enums\ProductUnit;
use InvalidArgumentException;

class StoneQuantityConverter
{
    public function __construct(
        public readonly float $density = 1.45,
    ) {
        if ($this->density <= 0) {
            throw new InvalidArgumentException('A densidade deve ser maior que zero.');
        }
    }

    public static function make(?float $density = null): self
    {
        return new self($density ?? (float) config('operations.stone_density', 1.45));
    }

    /**
     * @return array{quantity_m3: string, quantity_ton: string}
     */
    public function from(ProductUnit|string $inputUnit, float|string $quantity): array
    {
        $unit = $inputUnit instanceof ProductUnit ? $inputUnit : ProductUnit::from($inputUnit);
        $value = number_format((float) $quantity, 3, '.', '');

        return match ($unit) {
            ProductUnit::CubicMeter => [
                'quantity_m3' => $value,
                'quantity_ton' => $this->m3ToTon($value),
            ],
            ProductUnit::Ton => [
                'quantity_m3' => $this->tonToM3($value),
                'quantity_ton' => $value,
            ],
        };
    }

    /**
     * @param  array{quantity_m3: string, quantity_ton: string}  $quantities
     */
    public function forProductUnit(ProductUnit|string $productUnit, array $quantities): string
    {
        $unit = $productUnit instanceof ProductUnit ? $productUnit : ProductUnit::from($productUnit);

        return match ($unit) {
            ProductUnit::CubicMeter => $quantities['quantity_m3'],
            ProductUnit::Ton => $quantities['quantity_ton'],
        };
    }

    public function m3ToTon(float|string $m3): string
    {
        return bcmul(number_format((float) $m3, 3, '.', ''), number_format($this->density, 3, '.', ''), 3);
    }

    public function tonToM3(float|string $ton): string
    {
        return bcdiv(number_format((float) $ton, 3, '.', ''), number_format($this->density, 3, '.', ''), 3);
    }

    public function fromBuckets(float|string $buckets, float|string $capacityM3): array
    {
        $m3 = bcmul(
            number_format((float) $buckets, 3, '.', ''),
            number_format((float) $capacityM3, 3, '.', ''),
            3,
        );

        return $this->from(ProductUnit::CubicMeter, $m3);
    }
}
