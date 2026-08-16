import { Form, Head, Link } from '@inertiajs/react';
import { Minus, Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import LoaderOperatorController from '@/actions/App/Http/Controllers/LoaderOperatorController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatQty, formatQtyInput, unitLabel } from '@/lib/quantity';
import { index } from '@/routes/loader';
import type { Product } from '@/types';

type LoaderOrder = {
    id: number;
    status: string;
    vehicle_plate: string | null;
    destination: string | null;
    quantity_requested: string;
    quantity_loaded: string;
    remaining: string;
    remaining_m3: string;
    remaining_ton: string;
    suggested_m3: string;
    density: string;
    customer?: { id: number; name: string };
    product?: Pick<
        Product,
        'id' | 'name' | 'unit' | 'density' | 'bucket_capacity_m3' | 'stock_quantity'
    >;
};

function round3(value: number): number {
    return Math.round(value * 1000) / 1000;
}

export default function LoaderShow({ order }: { order: LoaderOrder }) {
    const suggested = Math.max(0.5, Number(order.suggested_m3) || 0.5);
    const [quantityM3, setQuantityM3] = useState(suggested);
    const [plate, setPlate] = useState(order.vehicle_plate ?? '');
    const density = Number(order.density);

    const preview = useMemo(
        () => ({
            m3: formatQty(quantityM3),
            ton: formatQty(quantityM3 * density),
        }),
        [quantityM3, density],
    );

    const adjust = (delta: number) => {
        setQuantityM3((current) => round3(Math.min(99999, Math.max(0.5, current + delta))));
    };

    return (
        <>
            <Head title={`Pedido #${order.id}`} />

            <header className="sticky top-0 z-10 border-b border-stone-200 bg-white px-4 py-3">
                <Link
                    href={index()}
                    className="inline-flex h-11 items-center rounded-lg px-2 text-base font-semibold text-stone-800 active:bg-stone-100"
                >
                    ← Voltar à fila
                </Link>
                <h1 className="mt-1 text-2xl font-bold">
                    #{order.id} · {order.customer?.name}
                </h1>
                <p className="text-lg text-stone-600">{order.product?.name}</p>
            </header>

            <main className="flex flex-1 flex-col gap-4 p-4 pb-36">
                <div className="grid grid-cols-2 gap-3">
                    <div className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-stone-200">
                        <p className="text-xs uppercase tracking-wide text-stone-500">Restante</p>
                        <p className="text-2xl font-bold">{formatQty(order.remaining_m3)} m³</p>
                        <p className="mt-1 text-sm text-stone-500">
                            {formatQty(order.remaining)} {unitLabel(order.product?.unit)} ≈{' '}
                            {formatQty(order.remaining_ton)} t
                        </p>
                    </div>
                    <div className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-stone-200">
                        <p className="text-xs uppercase tracking-wide text-stone-500">Estoque</p>
                        <p className="text-2xl font-bold">
                            {formatQty(order.product?.stock_quantity)}{' '}
                            {unitLabel(order.product?.unit)}
                        </p>
                        <p className="mt-1 text-sm text-stone-500">
                            Densidade {order.density} t/m³
                        </p>
                    </div>
                </div>

                {order.destination && (
                    <p className="rounded-xl bg-stone-200/70 px-4 py-3 text-base">
                        Destino: <span className="font-semibold">{order.destination}</span>
                    </p>
                )}

                <Form
                    {...LoaderOperatorController.store.form(order.id)}
                    className="flex flex-col gap-5"
                >
                    {({ errors, processing }) => (
                        <>
                            {(errors.product_id || errors.quantity || errors.order_id) && (
                                <div className="rounded-2xl border-2 border-red-300 bg-red-50 px-4 py-3 text-base text-red-800">
                                    {errors.product_id || errors.quantity || errors.order_id}
                                </div>
                            )}

                            <input type="hidden" name="quantity_m3" value={formatQtyInput(quantityM3)} />

                            <div className="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-stone-200">
                                <Label className="text-base text-stone-600">
                                    Quantidade carregada (m³)
                                </Label>
                                <div className="mt-4 flex items-center justify-between gap-3">
                                    <button
                                        type="button"
                                        onClick={() => adjust(-0.5)}
                                        className="flex size-20 items-center justify-center rounded-2xl bg-stone-800 text-white active:scale-95"
                                        aria-label="Diminuir 0,5 m³"
                                    >
                                        <Minus className="size-10" />
                                    </button>
                                    <div className="text-center">
                                        <p className="text-5xl font-bold tabular-nums leading-none">
                                            {formatQty(quantityM3)}
                                        </p>
                                        <p className="mt-2 text-sm text-stone-500">metros cúbicos</p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => adjust(0.5)}
                                        className="flex size-20 items-center justify-center rounded-2xl bg-emerald-700 text-white active:scale-95"
                                        aria-label="Aumentar 0,5 m³"
                                    >
                                        <Plus className="size-10" />
                                    </button>
                                </div>
                                <div className="mt-4 flex flex-wrap justify-center gap-2">
                                    {[0.5, 1, 2, 5].map((step) => (
                                        <button
                                            key={step}
                                            type="button"
                                            onClick={() => adjust(step)}
                                            className="h-12 min-w-16 rounded-xl bg-stone-100 px-4 text-lg font-semibold active:bg-stone-200"
                                        >
                                            +{step}
                                        </button>
                                    ))}
                                    <button
                                        type="button"
                                        onClick={() => setQuantityM3(suggested)}
                                        className="h-12 rounded-xl bg-amber-100 px-4 text-base font-semibold text-amber-950 active:bg-amber-200"
                                    >
                                        Restante
                                    </button>
                                </div>
                                <InputError className="mt-2" message={errors.quantity_m3} />
                            </div>

                            <div className="rounded-2xl bg-emerald-50 px-4 py-4 text-center ring-1 ring-emerald-200">
                                <p className="text-sm uppercase tracking-wide text-emerald-800">
                                    Prévia deste carregamento
                                </p>
                                <p className="mt-1 text-2xl font-bold text-emerald-950">
                                    {preview.m3} m³ ≈ {preview.ton} t
                                </p>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="vehicle_plate" className="text-base">
                                    Placa do caminhão
                                </Label>
                                <Input
                                    id="vehicle_plate"
                                    name="vehicle_plate"
                                    required
                                    value={plate}
                                    onChange={(event) =>
                                        setPlate(event.target.value.toUpperCase())
                                    }
                                    className="h-14 text-xl uppercase"
                                    autoComplete="off"
                                    inputMode="text"
                                />
                                <InputError message={errors.vehicle_plate} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="notes" className="text-base">
                                    Observação (opcional)
                                </Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows={2}
                                    placeholder="Ex.: pilha norte"
                                    className="w-full rounded-xl border border-input bg-white px-3 py-3 text-lg shadow-xs"
                                />
                                <InputError message={errors.notes} />
                            </div>

                            <div className="fixed inset-x-0 bottom-0 z-20 border-t border-stone-300 bg-stone-100/95 p-4 backdrop-blur">
                                <div className="mx-auto max-w-3xl">
                                    <Button
                                        type="submit"
                                        disabled={processing || !plate || quantityM3 <= 0}
                                        className="h-16 w-full rounded-2xl bg-emerald-700 text-xl font-semibold text-white hover:bg-emerald-800"
                                    >
                                        Confirmar {formatQty(quantityM3)} m³
                                    </Button>
                                </div>
                            </div>
                        </>
                    )}
                </Form>
            </main>
        </>
    );
}

LoaderShow.layout = {
    breadcrumbs: [
        { title: 'Pá', href: index() },
        { title: 'Carregar', href: '#' },
    ],
};
