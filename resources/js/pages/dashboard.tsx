import { Head, Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { Factory, Package, Scale, ShoppingCart, Shovel } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useStoredDisplayUnit } from '@/hooks/use-stored-display-unit';
import { formatQty, toDisplayUnit, unitLabel } from '@/lib/quantity';
import { dashboard } from '@/routes';
import {
    create as createEstimate,
    index as estimatesIndex,
    show as estimateShow,
} from '@/routes/estimated-loadings';
import { index as ordersIndex, show as orderShow } from '@/routes/orders';
import { index as productionIndex } from '@/routes/production';
import { index as productsIndex } from '@/routes/products';
import {
    index as ticketsIndex,
    show as ticketShow,
} from '@/routes/weigh-tickets';
import type {
    EstimatedLoading,
    Order,
    OrderStatus,
    Product,
    WeighTicket,
} from '@/types';

type Totals = {
    active_products: number;
    total_stock_ton: string;
    total_stock_m3: string;
    open_orders: number;
    queue_count: number;
    weighed_today_ton: string;
    weighed_today_m3: string;
    tickets_today: number;
    estimated_today_ton: string;
    estimated_today_m3: string;
    estimates_today: number;
    produced_today_ton: string;
    produced_today_m3: string;
    haulage_trips_today: number;
};

type StockProduct = Pick<
    Product,
    'id' | 'name' | 'code' | 'unit' | 'density' | 'stock_quantity'
>;

const STORAGE_KEY = 'dashboard.display_unit';

const statusLabel: Record<OrderStatus, string> = {
    open: 'Aberto',
    scheduled: 'Agendado',
    loading: 'Carregando',
    completed: 'Concluído',
    cancelled: 'Cancelado',
};

export default function Dashboard({
    totals,
    stocks,
    queue,
    recent_tickets: recentTickets,
    recent_estimates: recentEstimates,
    date,
}: {
    totals: Totals;
    stocks: StockProduct[];
    queue: Order[];
    recent_tickets: WeighTicket[];
    recent_estimates: EstimatedLoading[];
    date: string;
}) {
    const [displayUnit, changeUnit] = useStoredDisplayUnit(STORAGE_KEY);

    const dayLabel = new Date(`${date}T12:00:00`).toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
    });

    const stockTotal =
        displayUnit === 'm3' ? totals.total_stock_m3 : totals.total_stock_ton;
    const estimatedToday =
        displayUnit === 'm3'
            ? totals.estimated_today_m3
            : totals.estimated_today_ton;
    const weighedToday =
        displayUnit === 'm3'
            ? totals.weighed_today_m3
            : totals.weighed_today_ton;
    const producedToday =
        displayUnit === 'm3'
            ? totals.produced_today_m3
            : totals.produced_today_ton;
    const otherUnitHint =
        displayUnit === 'm3'
            ? `${formatQty(totals.estimated_today_ton)} t`
            : `${formatQty(totals.estimated_today_m3)} m³`;

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Operação"
                        description={`Visão do pátio · ${dayLabel}`}
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
                            <Link href={ticketsIndex()}>Balança</Link>
                        </Button>
                        <Button asChild>
                            <Link href={createEstimate()}>
                                Novo carregamento
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <StatCard
                        icon={Package}
                        label="Estoque total"
                        value={`${formatQty(stockTotal)} ${unitLabel(displayUnit)}`}
                        hint={`${totals.active_products} produtos ativos`}
                        href={productsIndex()}
                    />
                    <StatCard
                        icon={ShoppingCart}
                        label="Fila do dia"
                        value={String(totals.queue_count)}
                        hint={`${totals.open_orders} pedidos em aberto`}
                        href={ordersIndex()}
                    />
                    <StatCard
                        icon={Shovel}
                        label="Carregado hoje"
                        value={`${formatQty(estimatedToday)} ${unitLabel(displayUnit)}`}
                        hint={`${otherUnitHint} · ${totals.estimates_today} cargas`}
                        href={estimatesIndex()}
                    />
                    <StatCard
                        icon={Scale}
                        label="Pesado hoje"
                        value={`${formatQty(weighedToday)} ${unitLabel(displayUnit)}`}
                        hint={`${totals.tickets_today} tickets`}
                        href={ticketsIndex()}
                    />
                    <StatCard
                        icon={Factory}
                        label="Produzido hoje"
                        value={`${formatQty(producedToday)} ${unitLabel(displayUnit)}`}
                        hint={
                            totals.haulage_trips_today > 0
                                ? `${totals.haulage_trips_today} viagens do motorista · alimentação / usina`
                                : 'Alimentação / usina'
                        }
                        href={productionIndex()}
                    />
                </div>

                <div className="grid gap-4 xl:grid-cols-2">
                    <section className="rounded-xl border">
                        <div className="flex items-center justify-between border-b px-4 py-3">
                            <h2 className="text-sm font-medium">
                                Estoques ({unitLabel(displayUnit)})
                            </h2>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={productsIndex()}>Ver todos</Link>
                            </Button>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b bg-muted/40">
                                    <tr>
                                        <th className="px-4 py-2 font-medium">
                                            Produto
                                        </th>
                                        <th className="px-4 py-2 font-medium">
                                            Código
                                        </th>
                                        <th className="px-4 py-2 text-right font-medium">
                                            Estoque
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {stocks.map((product) => {
                                        const qty = toDisplayUnit(
                                            Number(product.stock_quantity),
                                            product.unit,
                                            Number(product.density),
                                            displayUnit,
                                        );

                                        return (
                                            <tr
                                                key={product.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-4 py-2">
                                                    {product.name}
                                                </td>
                                                <td className="px-4 py-2 font-mono text-xs">
                                                    {product.code}
                                                </td>
                                                <td className="px-4 py-2 text-right font-medium">
                                                    {formatQty(qty)}{' '}
                                                    {unitLabel(displayUnit)}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                    {stocks.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={3}
                                                className="px-4 py-8 text-center text-muted-foreground"
                                            >
                                                Nenhum produto ativo.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section className="rounded-xl border">
                        <div className="flex items-center justify-between border-b px-4 py-3">
                            <h2 className="text-sm font-medium">
                                Fila do dia ({unitLabel(displayUnit)})
                            </h2>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={ordersIndex()}>Pedidos</Link>
                            </Button>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b bg-muted/40">
                                    <tr>
                                        <th className="px-4 py-2 font-medium">
                                            #
                                        </th>
                                        <th className="px-4 py-2 font-medium">
                                            Cliente
                                        </th>
                                        <th className="px-4 py-2 font-medium">
                                            Produto
                                        </th>
                                        <th className="px-4 py-2 font-medium">
                                            Restante
                                        </th>
                                        <th className="px-4 py-2 font-medium">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {queue.map((order) => {
                                        const remaining =
                                            Number(order.quantity_requested) -
                                            Number(order.quantity_loaded);
                                        const qty = toDisplayUnit(
                                            remaining,
                                            order.product?.unit ?? 'ton',
                                            Number(
                                                order.product?.density ?? 1.45,
                                            ),
                                            displayUnit,
                                        );

                                        return (
                                            <tr
                                                key={order.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-4 py-2">
                                                    <Link
                                                        href={orderShow(
                                                            order.id,
                                                        )}
                                                        className="underline-offset-4 hover:underline"
                                                    >
                                                        {order.id}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-2">
                                                    {order.customer?.name}
                                                </td>
                                                <td className="px-4 py-2">
                                                    {order.product?.name}
                                                </td>
                                                <td className="px-4 py-2 font-medium">
                                                    {formatQty(qty)}{' '}
                                                    {unitLabel(displayUnit)}
                                                </td>
                                                <td className="px-4 py-2">
                                                    <Badge variant="secondary">
                                                        {
                                                            statusLabel[
                                                                order.status
                                                            ]
                                                        }
                                                    </Badge>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                    {queue.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={5}
                                                className="px-4 py-8 text-center text-muted-foreground"
                                            >
                                                Nenhuma ordem na fila agora.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div className="grid gap-4 xl:grid-cols-2">
                    <section className="rounded-xl border">
                        <div className="flex items-center justify-between border-b px-4 py-3">
                            <h2 className="text-sm font-medium">
                                Carregamentos de hoje
                            </h2>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={estimatesIndex()}>Ver todas</Link>
                            </Button>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b bg-muted/40">
                                    <tr>
                                        <th className="px-4 py-2 font-medium">
                                            Nº
                                        </th>
                                        <th className="px-4 py-2 font-medium">
                                            Cliente
                                        </th>
                                        <th className="px-4 py-2 text-right font-medium">
                                            {unitLabel(displayUnit)}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {recentEstimates.map((loading) => {
                                        const qty =
                                            displayUnit === 'm3'
                                                ? loading.quantity_m3
                                                : loading.quantity_ton;

                                        return (
                                            <tr
                                                key={loading.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-4 py-2 font-mono text-xs">
                                                    <Link
                                                        href={estimateShow(
                                                            loading.id,
                                                        )}
                                                        className="underline-offset-4 hover:underline"
                                                    >
                                                        {loading.number}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-2">
                                                    {loading.customer?.name}
                                                </td>
                                                <td className="px-4 py-2 text-right font-medium">
                                                    {formatQty(qty)}{' '}
                                                    {unitLabel(displayUnit)}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                    {recentEstimates.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={3}
                                                className="px-4 py-8 text-center text-muted-foreground"
                                            >
                                                Nenhum carregamento hoje.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section className="rounded-xl border">
                        <div className="flex items-center justify-between border-b px-4 py-3">
                            <h2 className="text-sm font-medium">
                                Pesagens de hoje
                            </h2>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={ticketsIndex()}>Balança</Link>
                            </Button>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b bg-muted/40">
                                    <tr>
                                        <th className="px-4 py-2 font-medium">
                                            Ticket
                                        </th>
                                        <th className="px-4 py-2 font-medium">
                                            Cliente
                                        </th>
                                        <th className="px-4 py-2 text-right font-medium">
                                            {unitLabel(displayUnit)}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {recentTickets.map((ticket) => {
                                        const qty =
                                            displayUnit === 'm3'
                                                ? (ticket.quantity_m3 ??
                                                  Number(ticket.net_weight) /
                                                      Number(
                                                          ticket.density ||
                                                              1.45,
                                                      ))
                                                : ticket.net_weight;

                                        return (
                                            <tr
                                                key={ticket.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-4 py-2 font-mono text-xs">
                                                    <Link
                                                        href={ticketShow(
                                                            ticket.id,
                                                        )}
                                                        className="underline-offset-4 hover:underline"
                                                    >
                                                        {ticket.number}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-2">
                                                    {ticket.customer?.name}
                                                </td>
                                                <td className="px-4 py-2 text-right font-medium">
                                                    {formatQty(qty)}{' '}
                                                    {unitLabel(displayUnit)}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                    {recentTickets.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={3}
                                                className="px-4 py-8 text-center text-muted-foreground"
                                            >
                                                Nenhuma pesagem hoje.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </>
    );
}

function StatCard({
    icon: Icon,
    label,
    value,
    hint,
    href,
}: {
    icon: typeof Package;
    label: string;
    value: string;
    hint: string;
    href: NonNullable<InertiaLinkProps['href']>;
}) {
    return (
        <Link
            href={href}
            className="rounded-xl border p-4 transition-colors hover:bg-muted/30"
        >
            <div className="flex items-center gap-2 text-muted-foreground">
                <Icon className="size-4" />
                <span className="text-sm">{label}</span>
            </div>
            <p className="mt-3 text-2xl font-semibold tracking-tight">
                {value}
            </p>
            <p className="mt-1 text-sm text-muted-foreground">{hint}</p>
        </Link>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
