import { Form, Head, Link } from '@inertiajs/react';
import LoaderOperatorController from '@/actions/App/Http/Controllers/LoaderOperatorController';
import FlashMessage from '@/components/flash-message';
import { Button } from '@/components/ui/button';
import { formatQty, formatQtyWithUnit } from '@/lib/quantity';
import { index } from '@/routes/loader';
import type { Product } from '@/types';

type QueueItem = {
    id: number;
    number: string | null;
    caixa_number: string | null;
    vehicle_plate: string | null;
    quantity_m3: string;
    quantity_ton: string;
    quantity: string;
    customer?: { id: number; name: string };
    product?: Pick<Product, 'id' | 'name' | 'unit'>;
};

export default function LoaderItem({ item }: { item: QueueItem }) {
    const reference = item.caixa_number
        ? `#${item.caixa_number}`
        : (item.number ?? 'Carregamento');

    return (
        <>
            <Head title={item.product?.name ?? 'Carregar'} />

            <header className="sticky top-0 z-10 border-b border-stone-200 bg-white px-4 py-3">
                <Link
                    href={index()}
                    className="inline-flex h-11 items-center rounded-lg px-2 text-base font-semibold text-stone-800 active:bg-stone-100"
                >
                    ← Voltar à fila
                </Link>
                <h1 className="mt-1 text-2xl font-bold">
                    {reference} · {item.customer?.name}
                </h1>
                <p className="text-lg text-stone-600">{item.product?.name}</p>
            </header>

            <main className="flex flex-1 flex-col gap-4 p-4 pb-36">
                <FlashMessage />

                <div className="grid grid-cols-2 gap-3">
                    <div className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-stone-200">
                        <p className="text-xs font-semibold tracking-wide text-stone-500 uppercase">
                            Volume
                        </p>
                        <p className="mt-1 text-3xl font-bold text-stone-900">
                            {formatQty(item.quantity_m3)} m³
                        </p>
                    </div>
                    <div className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-stone-200">
                        <p className="text-xs font-semibold tracking-wide text-stone-500 uppercase">
                            Peso
                        </p>
                        <p className="mt-1 text-3xl font-bold text-stone-900">
                            {formatQty(item.quantity_ton)} t
                        </p>
                    </div>
                </div>

                <div className="rounded-2xl bg-white p-4 text-base text-stone-700 shadow-sm ring-1 ring-stone-200">
                    <p>
                        Baixa no estoque:{' '}
                        <span className="font-semibold text-stone-900">
                            {formatQtyWithUnit(
                                item.quantity,
                                item.product?.unit,
                            )}
                        </span>
                    </p>
                    {item.vehicle_plate ? (
                        <p className="mt-2">Placa {item.vehicle_plate}</p>
                    ) : null}
                </div>

                <Form
                    {...LoaderOperatorController.completeItem.form(item.id)}
                    className="contents"
                >
                    {({ processing }) => (
                        <div className="fixed inset-x-0 bottom-0 z-20 border-t border-stone-300 bg-stone-100/95 p-4 backdrop-blur">
                            <div className="mx-auto max-w-3xl">
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="h-16 w-full rounded-2xl bg-emerald-700 text-xl font-semibold text-white hover:bg-emerald-800"
                                >
                                    Confirmar {item.product?.name}
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </main>
        </>
    );
}

LoaderItem.layout = {
    breadcrumbs: [
        { title: 'Fila da pá', href: index() },
        { title: 'Carregar', href: '#' },
    ],
};
