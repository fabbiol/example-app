import { Head, Link } from '@inertiajs/react';
import EstimatedLoadingStatusBadge, {
    itemStatus,
} from '@/components/estimated-loading-status-badge';
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
    const items =
        loading.items && loading.items.length > 0 ? loading.items : null;

    return (
        <>
            <Head title={loading.number} />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title={loading.number}
                    description={
                        loading.caixa_number
                            ? `Pedido #${loading.caixa_number}. Use estes valores para conferir com o MarketUp. Sem pesagem na balança.`
                            : 'Use estes valores para conferir com o MarketUp. Sem pesagem na balança.'
                    }
                />

                <dl className="grid gap-4 rounded-xl border p-4 sm:grid-cols-2">
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Cliente
                        </dt>
                        <dd className="font-medium">
                            {loading.customer?.name}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Pedido
                        </dt>
                        <dd>
                            {loading.caixa_number
                                ? `#${loading.caixa_number}`
                                : loading.order_id
                                  ? `#${loading.order_id}`
                                  : 'Avulso'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Status
                        </dt>
                        <dd className="mt-1">
                            <EstimatedLoadingStatusBadge
                                status={loading.status}
                            />
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">Placa</dt>
                        <dd>{loading.vehicle_plate}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Carregamento
                        </dt>
                        <dd>
                            {new Date(loading.loaded_at).toLocaleString(
                                'pt-BR',
                            )}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Volume total
                        </dt>
                        <dd className="text-lg font-semibold">
                            {formatQty(loading.quantity_m3)} m³
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Peso total
                        </dt>
                        <dd className="text-lg font-semibold">
                            {formatQty(loading.quantity_ton)} t
                        </dd>
                    </div>
                    <div className="sm:col-span-2">
                        <dt className="text-sm text-muted-foreground">
                            Observações
                        </dt>
                        <dd>{loading.notes || '—'}</dd>
                    </div>
                </dl>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-muted/40">
                            <tr>
                                <th className="px-4 py-3 font-medium">
                                    Produto
                                </th>
                                <th className="px-4 py-3 font-medium">m³</th>
                                <th className="px-4 py-3 font-medium">t</th>
                                <th className="px-4 py-3 font-medium">
                                    Baixa no estoque
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {(items ?? []).map((item) => (
                                <tr
                                    key={item.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="px-4 py-3 font-medium">
                                        {item.product?.name} (
                                        {unitLabel(item.product?.unit)})
                                    </td>
                                    <td className="px-4 py-3">
                                        {formatQty(item.quantity_m3)}
                                    </td>
                                    <td className="px-4 py-3">
                                        {formatQty(item.quantity_ton)}
                                    </td>
                                    <td className="px-4 py-3">
                                        {formatQtyWithUnit(
                                            item.quantity,
                                            item.product?.unit,
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        <EstimatedLoadingStatusBadge
                                            status={itemStatus(
                                                item.loader_loaded_at,
                                            )}
                                        />
                                    </td>
                                </tr>
                            ))}
                            {!items && (
                                <tr>
                                    <td className="px-4 py-3 font-medium">
                                        {loading.product?.name} (
                                        {unitLabel(loading.product?.unit)})
                                    </td>
                                    <td className="px-4 py-3">
                                        {formatQty(loading.quantity_m3)}
                                    </td>
                                    <td className="px-4 py-3">
                                        {formatQty(loading.quantity_ton)}
                                    </td>
                                    <td className="px-4 py-3">
                                        {formatQtyWithUnit(
                                            loading.quantity,
                                            loading.product?.unit,
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        <EstimatedLoadingStatusBadge
                                            status={loading.status}
                                        />
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Button variant="outline" asChild className="w-fit">
                    <Link href={index()}>Voltar</Link>
                </Button>
            </div>
        </>
    );
}

EstimatedLoadingsShow.layout = {
    breadcrumbs: [
        { title: 'Carregamentos', href: index() },
        { title: 'Detalhes', href: index() },
    ],
};
