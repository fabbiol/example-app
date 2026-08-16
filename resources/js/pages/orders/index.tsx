import { Form, Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import OrderController from '@/actions/App/Http/Controllers/OrderController';
import FlashMessage from '@/components/flash-message';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useStoredDisplayUnit } from '@/hooks/use-stored-display-unit';
import { formatQty, toDisplayUnit, unitLabel } from '@/lib/quantity';
import { create, edit, index, show } from '@/routes/orders';
import type { Order, OrderStatus, Paginated } from '@/types';

const STORAGE_KEY = 'orders.display_unit';

const statusLabel: Record<OrderStatus, string> = {
    open: 'Aberto',
    scheduled: 'Agendado',
    loading: 'Carregando',
    completed: 'Concluído',
    cancelled: 'Cancelado',
};

export default function OrdersIndex({ orders }: { orders: Paginated<Order> }) {
    const [displayUnit, changeUnit] = useStoredDisplayUnit(STORAGE_KEY);

    return (
        <>
            <Head title="Pedidos" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Pedidos"
                        description="Ordens de carregamento sem emissão de NF"
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
                        <Button asChild>
                            <Link href={create()}>
                                <Plus />
                                Novo pedido
                            </Link>
                        </Button>
                    </div>
                </div>

                <FlashMessage />

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full min-w-[800px] text-left text-sm">
                        <thead className="border-b bg-muted/40">
                            <tr>
                                <th className="px-4 py-3 font-medium">#</th>
                                <th className="px-4 py-3 font-medium">
                                    Cliente
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Produto
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Solicitado ({unitLabel(displayUnit)})
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Carregado ({unitLabel(displayUnit)})
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Status
                                </th>
                                <th className="px-4 py-3 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {orders.data.map((order) => {
                                const productUnit =
                                    order.product?.unit ?? 'ton';
                                const density = Number(
                                    order.product?.density ?? 1.45,
                                );
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

                                return (
                                    <tr
                                        key={order.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-3">
                                            {order.id}
                                        </td>
                                        <td className="px-4 py-3">
                                            {order.customer?.name}
                                        </td>
                                        <td className="px-4 py-3">
                                            {order.product?.name}
                                        </td>
                                        <td className="px-4 py-3 font-medium">
                                            {formatQty(requested)}{' '}
                                            {unitLabel(displayUnit)}
                                        </td>
                                        <td className="px-4 py-3 font-medium">
                                            {formatQty(loaded)}{' '}
                                            {unitLabel(displayUnit)}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant="secondary">
                                                {statusLabel[order.status]}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link href={show(order.id)}>
                                                        Ver
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link href={edit(order.id)}>
                                                        Editar
                                                    </Link>
                                                </Button>
                                                <Form
                                                    {...OrderController.destroy.form(
                                                        order.id,
                                                    )}
                                                    options={{
                                                        preserveScroll: true,
                                                    }}
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            type="submit"
                                                            variant="destructive"
                                                            size="sm"
                                                            disabled={
                                                                processing
                                                            }
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
                            {orders.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Nenhum pedido cadastrado.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination meta={orders} />
            </div>
        </>
    );
}

OrdersIndex.layout = {
    breadcrumbs: [{ title: 'Pedidos', href: index() }],
};
