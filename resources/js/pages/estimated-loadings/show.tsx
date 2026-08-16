import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { formatQty, formatQtyWithUnit, unitLabel } from '@/lib/quantity';
import { index } from '@/routes/estimated-loadings';
import type { EstimatedLoading } from '@/types';

export default function EstimatedLoadingsShow({
    loading,
}: {
    loading: EstimatedLoading;
}) {
    return (
        <>
            <Head title={loading.number} />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title={loading.number}
                    description="Use estes valores para conferir com o MarketUp. Sem pesagem na balança."
                />

                <dl className="grid gap-4 rounded-xl border p-4 sm:grid-cols-2">
                    <div>
                        <dt className="text-sm text-muted-foreground">Cliente</dt>
                        <dd className="font-medium">{loading.customer?.name}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">Produto</dt>
                        <dd className="font-medium">
                            {loading.product?.name} ({unitLabel(loading.product?.unit)})
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">Pedido</dt>
                        <dd>{loading.order_id ? `#${loading.order_id}` : 'Avulso'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">Placa</dt>
                        <dd>{loading.vehicle_plate}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">Volume</dt>
                        <dd className="text-lg font-semibold">
                            {formatQty(loading.quantity_m3)} m³
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">Peso equivalente</dt>
                        <dd className="text-lg font-semibold">
                            {formatQty(loading.quantity_ton)} t
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">Baixa no estoque</dt>
                        <dd>
                            {formatQtyWithUnit(loading.quantity, loading.product?.unit)}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">Densidade usada</dt>
                        <dd>{formatQty(loading.density, 2)} t/m³</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">Conchas</dt>
                        <dd>
                            {loading.buckets_count
                                ? `${loading.buckets_count} × ${formatQty(loading.bucket_capacity_m3)} m³`
                                : '—'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">Carregamento</dt>
                        <dd>{new Date(loading.loaded_at).toLocaleString('pt-BR')}</dd>
                    </div>
                    <div className="sm:col-span-2">
                        <dt className="text-sm text-muted-foreground">Observações</dt>
                        <dd>{loading.notes || '—'}</dd>
                    </div>
                </dl>

                <Button variant="outline" asChild className="w-fit">
                    <Link href={index()}>Voltar</Link>
                </Button>
            </div>
        </>
    );
}

EstimatedLoadingsShow.layout = {
    breadcrumbs: [
        { title: 'Carregamento', href: index() },
        { title: 'Detalhes', href: index() },
    ],
};
