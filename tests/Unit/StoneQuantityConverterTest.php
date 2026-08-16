<?php

namespace Tests\Unit;

use App\Enums\ProductUnit;
use App\Support\StoneQuantityConverter;
use PHPUnit\Framework\TestCase;

class StoneQuantityConverterTest extends TestCase
{
    public function test_converts_cubic_meters_to_tons_using_density(): void
    {
        $converter = new StoneQuantityConverter(1.45);
        $quantities = $converter->from(ProductUnit::CubicMeter, 10);

        $this->assertSame('10.000', $quantities['quantity_m3']);
        $this->assertSame('14.500', $quantities['quantity_ton']);
    }

    public function test_converts_tons_to_cubic_meters_using_density(): void
    {
        $converter = new StoneQuantityConverter(1.45);
        $quantities = $converter->from(ProductUnit::Ton, 14.5);

        $this->assertSame('14.500', $quantities['quantity_ton']);
        $this->assertSame('10.000', $quantities['quantity_m3']);
    }

    public function test_converts_from_buckets(): void
    {
        $converter = new StoneQuantityConverter(1.45);
        $quantities = $converter->fromBuckets(8, 1.5);

        $this->assertSame('12.000', $quantities['quantity_m3']);
        $this->assertSame('17.400', $quantities['quantity_ton']);
    }

    public function test_resolves_quantity_for_product_unit(): void
    {
        $converter = new StoneQuantityConverter(1.45);
        $quantities = $converter->from(ProductUnit::CubicMeter, 10);

        $this->assertSame('10.000', $converter->forProductUnit(ProductUnit::CubicMeter, $quantities));
        $this->assertSame('14.500', $converter->forProductUnit(ProductUnit::Ton, $quantities));
    }
}
