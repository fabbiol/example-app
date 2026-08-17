import { Form, Head, Link, router } from '@inertiajs/react';
import { LogOut, RefreshCw, Shovel } from 'lucide-react';
import { useState } from 'react';
import { showItem } from '@/actions/App/Http/Controllers/LoaderOperatorController';
import FlashMessage from '@/components/flash-message';
import { Button } from '@/components/ui/button';
import { formatQty, loadingProductNames, unitLabel } from '@/lib/quantity';
import { logout } from '@/routes';
import { index, show } from '@/routes/loader';
import type { EstimatedLoading, Product } from '@/types';

type QueueOrder = {
    id: number;
    status: string;
    vehicle_plate: string | null;
    destination: string | null;
    quantity_requested: string;
    quantity_loaded: string;
    remaining: string;
    remaining_m3: string;
    remaining_ton: string;
    customer?: { id: number; name: string };
    product?: Pick<
        Product,
        | 'id'
        | 'name'
        | 'unit'
        | 'density'
        | 'bucket_capacity_m3'
        | 'stock_quantity'
    >;
};

type QueueItem = {
    id: number;
    loading_id: number;
    number: string | null;
    caixa_number: string | null;
    vehicle_plate: string | null;
    quantity_m3: string;
    quantity_ton: string;
    quantity: string;
    customer?: { id: number; name: string };
    product?: Pick<Product, 'id' | 'name' | 'unit'>;
};

const statusLabel: Record<string, string> = {
    open: 'Aberto',
    scheduled: 'Agendado',
    loading: 'Carregando',
};

const headerButtonClass =
    'h-12 border-2 border-stone-300 bg-white px-4 text-base font-semibold text-stone-900 shadow-none hover:bg-stone-50';

export default function LoaderIndex({
    orders,
    released,
    recent,
    operator,
}: {
    orders: QueueOrder[];
    released: QueueItem[];
    recent: EstimatedLoading[];
    operator: { name: string | null };
}) {
    const [refreshing, setRefreshing] = useState(false);
    const hasQueue = orders.length > 0 || released.length > 0;

    const refreshQueue = () => {
        if (refreshing) {
            return;
        }

        setRefreshing(true);
        router.get(
            index.url(),
            {},
            {
                replace: true,
                preserveState: false,
                preserveScroll: false,
                async: false,
                showProgress: true,
                onFinish: () => setRefreshing(false),
            },
        );
    };

    return (
        <>
            <Head title="Fila da pá" />

            <header className="sticky top-0 z-10 flex items-center justify-between gap-3 border-b border-stone-200 bg-white px-4 py-4">
                <div>
                    <p className="text-xs font-semibold tracking-wide text-stone-500 uppercase">
                        Operador da pá
                    </p>
                    <h1 className="text-xl font-bold text-stone-900">
                        {operator.name ?? 'Operador'}
                    </h1>
                </div>
                <div className="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="lg"
                        className={headerButtonClass}
                        disabled={refreshing}
                        onClick={refreshQueue}
                    >
                        <RefreshCw
                            className={`size-5 ${refreshing ? 'animate-spin' : ''}`}
                        />
                        Atualizar
                    </Button>
                    <Form {...logout.form()}>
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="outline"
                                size="lg"
                                className={headerButtonClass}
                                disabled={processing}
                            >
                                <LogOut className="size-5" />
                                Sair
                            </Button>
                        )}
                    </Form>
                </div>
            </header>

            <main className="flex flex-1 flex-col gap-5 p-4 pb-8">
                <FlashMessage />

                {hasQueue ? (
                    <div>
                        <div className="mb-3 flex items-baseline justify-between gap-3">
                            <h2 className="text-lg font-bold text-stone-900">
                                Pedidos na fila
                            </h2>
                            <span className="rounded-full bg-stone-200 px-3 py-1 text-sm font-semibold text-stone-800">
                                {orders.length + released.length}
                            </span>
                        </div>
                        <div className="grid gap-3">
                            {released.map((item) => (
                                <Link
                                    key={`item-${item.id}`}
                                    href={showItem.url(item.id)}
                                    className="block rounded-2xl border border-stone-200 bg-white p-4 shadow-sm active:scale-[0.99] active:bg-stone-50"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <p className="text-2xl leading-tight font-bold text-stone-900">
                                                {item.caixa_number
                                                    ? `#${item.caixa_number}`
                                                    : (item.number ??
                                                      'Carregamento')}{' '}
                                                · {item.customer?.name}
                                            </p>
                                            <p className="mt-1 text-lg text-stone-600">
                                                {item.product?.name}
                                            </p>
                                        </div>
                                        <span className="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-950">
                                            Liberado
                                        </span>
                                    </div>

                                    <div className="mt-4 grid grid-cols-2 gap-3 text-base">
                                        <div className="rounded-xl bg-emerald-50 p-3">
                                            <p className="text-xs font-semibold tracking-wide text-emerald-800 uppercase">
                                                Volume
                                            </p>
                                            <p className="text-xl font-bold text-emerald-950">
                                                {formatQty(item.quantity_m3)} m³
                                            </p>
                                        </div>
                                        <div className="rounded-xl bg-stone-100 p-3">
                                            <p className="text-xs font-semibold tracking-wide text-stone-500 uppercase">
                                                Peso
                                            </p>
                                            <p className="text-xl font-bold text-stone-900">
                                                {formatQty(item.quantity_ton)} t
                                            </p>
                                        </div>
                                    </div>

                                    <div className="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-stone-600">
                                        {item.vehicle_plate && (
                                            <span>
                                                Placa {item.vehicle_plate}
                                            </span>
                                        )}
                                        {item.number && (
                                            <span>{item.number}</span>
                                        )}
                                    </div>
                                </Link>
                            ))}
                            {orders.map((order) => (
                                <Link
                                    key={order.id}
                                    href={show(order.id)}
                                    className="block rounded-2xl border border-stone-200 bg-white p-4 shadow-sm active:scale-[0.99] active:bg-stone-50"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <p className="text-2xl leading-tight font-bold text-stone-900">
                                                #{order.id} ·{' '}
                                                {order.customer?.name}
                                            </p>
                                            <p className="mt-1 text-lg text-stone-600">
                                                {order.product?.name}
                                            </p>
                                        </div>
                                        <span className="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-950">
                                            {statusLabel[order.status] ??
                                                order.status}
                                        </span>
                                    </div>

                                    <div className="mt-4 grid grid-cols-2 gap-3 text-base">
                                        <div className="rounded-xl bg-emerald-50 p-3">
                                            <p className="text-xs font-semibold tracking-wide text-emerald-800 uppercase">
                                                Restante
                                            </p>
                                            <p className="text-xl font-bold text-emerald-950">
                                                {formatQty(order.remaining_m3)}{' '}
                                                m³
                                            </p>
                                        </div>
                                        <div className="rounded-xl bg-stone-100 p-3">
                                            <p className="text-xs font-semibold tracking-wide text-stone-500 uppercase">
                                                Pedido
                                            </p>
                                            <p className="text-xl font-bold text-stone-900">
                                                {formatQty(order.remaining)}{' '}
                                                {unitLabel(order.product?.unit)}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-stone-600">
                                        {order.vehicle_plate && (
                                            <span>
                                                Placa {order.vehicle_plate}
                                            </span>
                                        )}
                                        {order.destination && (
                                            <span>{order.destination}</span>
                                        )}
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                ) : (
                    <div className="flex flex-1 flex-col items-center justify-center rounded-3xl bg-white px-6 py-16 text-center shadow-sm ring-1 ring-stone-200">
                        <span className="flex size-16 items-center justify-center rounded-2xl bg-stone-100 text-stone-600">
                            <Shovel className="size-8" />
                        </span>
                        <p className="mt-5 text-2xl font-bold text-stone-900">
                            Fila vazia
                        </p>
                        <p className="mt-2 max-w-sm text-base text-stone-600">
                            Quando o escritório lançar um carregamento para
                            hoje ou liberar um pedido, toque em atualizar para
                            aparecer aqui.
                        </p>
                        <Button
                            type="button"
                            className="mt-8 h-14 rounded-2xl bg-emerald-700 px-8 text-lg font-semibold text-white hover:bg-emerald-800"
                            disabled={refreshing}
                            onClick={refreshQueue}
                        >
                            <RefreshCw
                                className={`size-5 ${refreshing ? 'animate-spin' : ''}`}
                            />
                            Atualizar fila
                        </Button>
                    </div>
                )}

                {recent.length > 0 && (
                    <div>
                        <h2 className="mb-3 text-lg font-bold text-stone-900">
                            Últimos registros
                        </h2>
                        <ul className="space-y-2">
                            {recent.map((item) => (
                                <li
                                    key={item.id}
                                    className="rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-700"
                                >
                                    <span className="font-semibold text-stone-900">
                                        {item.number}
                                    </span>
                                    {item.caixa_number
                                        ? ` · Pedido #${item.caixa_number}`
                                        : ''}
                                    {' · '}
                                    {loadingProductNames(item)}
                                    {' · '}
                                    {formatQty(item.quantity_m3)} m³
                                    {' · '}
                                    {item.vehicle_plate}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </main>
        </>
    );
}

LoaderIndex.layout = {
    breadcrumbs: [{ title: 'Fila da pá', href: index() }],
};
