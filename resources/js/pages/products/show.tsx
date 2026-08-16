import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatQty, formatQtyWithUnit, unitLabel } from '@/lib/quantity';
import { edit, index } from '@/routes/products';
import type { Product } from '@/types';

export default function ProductsShow({ product }: { product: Product }) {
    return (
        <>
            <Head title={product.name} />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title={product.name}
                        description={`Código ${product.code}`}
                    />
                    <Button asChild>
                        <Link href={edit(product.id)}>Editar</Link>
                    </Button>
                </div>

                <dl className="grid gap-4 rounded-xl border p-4 sm:grid-cols-2">
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Estoque
                        </dt>
                        <dd className="text-lg font-medium">
                            {formatQtyWithUnit(
                                product.stock_quantity,
                                product.unit,
                            )}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Densidade
                        </dt>
                        <dd>{formatQty(product.density, 2)} t/m³</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Concha padrão
                        </dt>
                        <dd>
                            {formatQty(product.bucket_capacity_m3)}{' '}
                            {unitLabel('m3')}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Status
                        </dt>
                        <dd className="mt-1">
                            <Badge
                                variant={
                                    product.is_active ? 'default' : 'secondary'
                                }
                            >
                                {product.is_active ? 'Ativo' : 'Inativo'}
                            </Badge>
                        </dd>
                    </div>
                    <div className="sm:col-span-2">
                        <dt className="text-sm text-muted-foreground">
                            Observações
                        </dt>
                        <dd className="mt-1">{product.notes || '—'}</dd>
                    </div>
                </dl>

                <Button variant="outline" asChild className="w-fit">
                    <Link href={index()}>Voltar</Link>
                </Button>
            </div>
        </>
    );
}

ProductsShow.layout = {
    breadcrumbs: [
        { title: 'Produtos', href: index() },
        { title: 'Detalhes', href: index() },
    ],
};
