import { Form, Head, Link } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/products';
import type { Option, Product } from '@/types';

export default function ProductsEdit({
    product,
    units,
}: {
    product: Product;
    units: Option[];
}) {
    return (
        <>
            <Head title={`Editar ${product.name}`} />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title={`Editar ${product.name}`}
                    description="Atualize dados do produto sem alterar o estoque por aqui"
                />

                <Form
                    {...ProductController.update.form(product.id)}
                    className="max-w-xl space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nome</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    defaultValue={product.name}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="code">Código</Label>
                                <Input
                                    id="code"
                                    name="code"
                                    required
                                    defaultValue={product.code}
                                />
                                <InputError message={errors.code} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="unit">Unidade</Label>
                                <select
                                    id="unit"
                                    name="unit"
                                    defaultValue={product.unit}
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                >
                                    {units.map((unit) => (
                                        <option key={unit.value} value={unit.value}>
                                            {unit.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.unit} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="density">Densidade (t/m³)</Label>
                                    <Input
                                        id="density"
                                        name="density"
                                        type="number"
                                        step="0.001"
                                        min="0.001"
                                        required
                                        defaultValue={product.density}
                                    />
                                    <InputError message={errors.density} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="bucket_capacity_m3">
                                        Concha padrão (m³)
                                    </Label>
                                    <Input
                                        id="bucket_capacity_m3"
                                        name="bucket_capacity_m3"
                                        type="number"
                                        step="0.001"
                                        min="0.001"
                                        required
                                        defaultValue={product.bucket_capacity_m3}
                                    />
                                    <InputError message={errors.bucket_capacity_m3} />
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    id="is_active"
                                    name="is_active"
                                    type="checkbox"
                                    value="1"
                                    defaultChecked={product.is_active}
                                    className="size-4 rounded border border-input"
                                />
                                <Label htmlFor="is_active">Produto ativo</Label>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="notes">Observações</Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows={3}
                                    defaultValue={product.notes ?? ''}
                                    className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                                />
                                <InputError message={errors.notes} />
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Salvar
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={index()}>Cancelar</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

ProductsEdit.layout = {
    breadcrumbs: [
        { title: 'Produtos', href: index() },
        { title: 'Editar', href: index() },
    ],
};
