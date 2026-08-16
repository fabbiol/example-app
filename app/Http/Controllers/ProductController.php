<?php

namespace App\Http\Controllers;

use App\Enums\ProductUnit;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('products/index', [
            'products' => Product::query()
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('products/create', [
            'units' => $this->units(),
            'defaults' => [
                'density' => (float) config('operations.stone_density'),
                'bucket_capacity_m3' => (float) config('operations.default_bucket_capacity_m3'),
            ],
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        Product::query()->create($request->validated());

        return redirect()
            ->route('products.index')
            ->with('success', 'Produto criado.');
    }

    public function show(Product $product): Response
    {
        return Inertia::render('products/show', [
            'product' => $product,
        ]);
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('products/edit', [
            'product' => $product,
            'units' => $this->units(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()
            ->route('products.index')
            ->with('success', 'Produto atualizado.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Produto removido.');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function units(): array
    {
        return collect(ProductUnit::cases())
            ->map(fn (ProductUnit $unit) => [
                'value' => $unit->value,
                'label' => $unit->label(),
            ])
            ->values()
            ->all();
    }
}
