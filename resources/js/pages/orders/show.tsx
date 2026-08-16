import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useStoredDisplayUnit } from '@/hooks/use-stored-display-unit';
import { formatQty, toDisplayUnit, unitLabel } from '@/lib/quantity';
import { edit, index } from '@/routes/orders';
import { create as createTicket } from '@/routes/weigh-tickets';
import type { Order, OrderStatus, WeighTicket } from '@/types';

const STORAGE_KEY = 'orders.display_unit';

const statusLabel: Record<OrderStatus, string> = {
    open: 'Aberto',
    scheduled: 'Agendado',
    loading: 'Carregando',
    completed: 'Concluído',
    cancelled: 'Cancelado',
};

export default function OrdersShow({
    order,
    remainingQuantity,
}: {
    order: Order & { weigh_tickets?: WeighTicket[] };
    remainingQuantity: string;
}) {
    const [displayUnit, changeUnit] = useStoredDisplayUnit(STORAGE_KEY);

    const productUnit = order.product?.unit ?? 'ton';
    const density = Number(order.product?.density ?? 1.45);
    const requested = toDisplayUnit(
        Number(order.quantity_requested),
        productUnit,
        density,
        displayUnit,
    );
    const loaded = toDisplayUnit(
        Number(order.quantity_loaded),
        productUnit,
        density,
        displayUnit,
    );
    const remaining = toDisplayUnit(
        Number(remainingQuantity),
        productUnit,
        density,
        displayUnit,
    );

    return (
        <>
            <Head title={`Pedido #${order.id}`} />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={`Pedido #${order.id}`}
                        description={`${order.customer?.name} · ${order.product?.name}`}
                    />
                    <div className="flex flex-wrap items-center gap-2">
                        <div className="flex rounded-lg border p-1">
                            <Button
                                type="button"
                                size="sm"
                                variant={
                                    displayUnit === 'm3' ? 'default' : 'ghost'
                                }
                                onClick={() => changeUnit('m3')}
                            >
                                m³
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant={
                                    displayUnit === 'ton' ? 'default' : 'ghost'
                                }
                                onClick={() => changeUnit('ton')}
                            >
                                t
                            </Button>
                        </div>
                        <Button variant="outline" asChild>
                            <Link href={edit(order.id)}>Editar</Link>
                        </Button>
                        <Button asChild>
                            <Link href={createTicket()}>Registrar pesagem</Link>
                        </Button>
                    </div>
                </div>

                <dl className="grid gap-4 rounded-xl border p-4 sm:grid-cols-3">
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Status
                        </dt>
                        <dd className="mt-1">
                            <Badge variant="secondary">
                                {statusLabel[order.status]}
                            </Badge>
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Solicitado ({unitLabel(displayUnit)})
                        </dt>
                        <dd className="text-lg font-medium">
                            {formatQty(requested)} {unitLabel(displayUnit)}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Carregado ({unitLabel(displayUnit)})
                        </dt>
                        <dd className="text-lg font-medium">
                            {formatQty(loaded)} {unitLabel(displayUnit)}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Restante ({unitLabel(displayUnit)})
                        </dt>
                        <dd className="text-lg font-medium">
                            {formatQty(remaining)} {unitLabel(displayUnit)}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">Placa</dt>
                        <dd>{order.vehicle_plate || '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Destino
                        </dt>
                        <dd>{order.destination || '—'}</dd>
                    </div>
                </dl>

                <div className="space-y-3">
                    <h3 className="text-sm font-medium">Tickets de balança</h3>
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b bg-muted/40">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Número
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Placa
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Líquido ({unitLabel(displayUnit)})
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Data
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {(order.weigh_tickets ?? []).map((ticket) => {
                                    const qty =
                                        displayUnit === 'm3'
                                            ? Number(
                                                  ticket.quantity_m3 ??
                                                      Number(
                                                          ticket.net_weight,
                                                      ) / density,
                                              )
                                            : Number(ticket.net_weight);

                                    return (
                                        <tr
                                            key={ticket.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-4 py-3 font-mono text-xs">
                                                {ticket.number}
                                            </td>
                                            <td className="px-4 py-3">
                                                {ticket.vehicle_plate}
                                            </td>
                                            <td className="px-4 py-3 font-medium">
                                                {formatQty(qty)}{' '}
                                                {unitLabel(displayUnit)}
                                            </td>
                                            <td className="px-4 py-3">
                                                {new Date(
                                                    ticket.weighed_at,
                                                ).toLocaleString('pt-BR')}
                                            </td>
                                        </tr>
                                    );
                                })}
                                {(order.weigh_tickets ?? []).length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="px-4 py-6 text-center text-muted-foreground"
                                        >
                                            Nenhuma pesagem vinculada.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <Button variant="outline" asChild className="w-fit">
                    <Link href={index()}>Voltar</Link>
                </Button>
            </div>
        </>
    );
}

OrdersShow.layout = {
    breadcrumbs: [
        { title: 'Pedidos', href: index() },
        { title: 'Detalhes', href: index() },
    ],
};
