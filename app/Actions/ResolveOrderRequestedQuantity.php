<?php

namespace App\Actions;

use App\Enums\ProductUnit;
use App\Models\Product;

class ResolveOrderRequestedQuantity
{
    /**
     * Converte a quantidade informada (m³ ou t) para a unidade do produto.
     */
    public function handle(Product $product, ProductUnit|string $inputUnit, float|string $quantityInput): string
    {
        $unit = $inputUnit instanceof ProductUnit ? $inputUnit : ProductUnit::from($inputUnit);
        $converter = $product->converter();
        $quantities = $converter->from($unit, $quantityInput);

        return $converter->forProductUnit($product->unit, $quantities);
    }
}
