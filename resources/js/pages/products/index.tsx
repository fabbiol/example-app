import { Form, Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import FlashMessage from '@/components/flash-message';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatQty, toDisplayUnit, unitLabel } from '@/lib/quantity';
import { create, edit, index, show } from '@/routes/products';
import type { Paginated, Product } from '@/types';

type DisplayUnit = 'm3' | 'ton';

const STORAGE_KEY = 'products.display_unit';

export default function ProductsIndex({
    products,
}: {
    products: Paginated<Product>;
}) {
    const [displayUnit, setDisplayUnit] = useState<DisplayUnit>('m3');

    useEffect(() => {
        const saved = window.localStorage.getItem(STORAGE_KEY);

        if (saved === 'm3' || saved === 'ton') {
            setDisplayUnit(saved);
        }
    }, []);

    const changeUnit = (unit: DisplayUnit) => {
        setDisplayUnit(unit);
        window.localStorage.setItem(STORAGE_KEY, unit);
    };

    return (
        <>
            <Head title="Produtos" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Produtos"
                        description="Granulometrias e estoque operacional"
                    />
                    <div className="flex flex-wrap items-center gap-2">
                        <div className="flex rounded-lg border p-1">
                            <Button
                                type="button"
                                size="sm"
                                variant={displayUnit === 'm3' ? 'default' : 'ghost'}
                                onClick={() => changeUnit('m3')}
                            >
                                m³
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant={displayUnit === 'ton' ? 'default' : 'ghost'}
                                onClick={() => changeUnit('ton')}
                            >
                                t
                            </Button>
                        </div>
                        <Button asChild>
                            <Link href={create()}>
                                <Plus />
                                Novo produto
                            </Link>
                        </Button>
                    </div>
                </div>

                <FlashMessage />

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full min-w-[640px] text-left text-sm">
                        <thead className="border-b bg-muted/40">
                            <tr>
                                <th className="px-4 py-3 font-medium">Código</th>
                                <th className="px-4 py-3 font-medium">Nome</th>
                                <th className="px-4 py-3 font-medium">
                                    Estoque ({unitLabel(displayUnit)})
                                </th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {products.data.map((product) => {
                                const qty = toDisplayUnit(
                                    Number(product.stock_quantity),
                                    product.unit,
                                    Number(product.density),
                                    displayUnit,
                                );

                                return (
                                    <tr key={product.id} className="border-b last:border-0">
                                        <td className="px-4 py-3 font-mono text-xs">
                                            {product.code}
                                        </td>
                                        <td className="px-4 py-3">{product.name}</td>
                                        <td className="px-4 py-3 font-medium">
                                            {formatQty(qty)} {unitLabel(displayUnit)}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                variant={
                                                    product.is_active ? 'default' : 'secondary'
                                                }
                                            >
                                                {product.is_active ? 'Ativo' : 'Inativo'}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex justify-end gap-2">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={show(product.id)}>Ver</Link>
                                                </Button>
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={edit(product.id)}>Editar</Link>
                                                </Button>
                                                <Form
                                                    {...ProductController.destroy.form(product.id)}
                                                    options={{ preserveScroll: true }}
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            type="submit"
                                                            variant="destructive"
                                                            size="sm"
                                                            disabled={processing}
                                                        >
                                                            Excluir
                                                        </Button>
                                                    )}
                                                </Form>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                            {products.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Nenhum produto cadastrado.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination meta={products} />
            </div>
        </>
    );
}

ProductsIndex.layout = {
    breadcrumbs: [{ title: 'Produtos', href: index() }],
};
