import { Head, Link } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { formatQty, formatQtyWithUnit } from '@/lib/quantity';
import { index, show } from '@/routes/loader';
import type { EstimatedLoading } from '@/types';

export default function LoaderDone({ loading }: { loading: EstimatedLoading }) {
    const completed = loading.order?.status === 'completed';

    return (
        <>
            <Head title="Registrado" />

            <main className="flex flex-1 flex-col items-center justify-center gap-6 p-6 text-center">
                <CheckCircle2
                    className="size-20 text-emerald-600"
                    strokeWidth={1.5}
                />
                <div>
                    <p className="text-sm font-medium tracking-wide text-emerald-800 uppercase">
                        {completed
                            ? 'Pedido concluído'
                            : 'Carregamento registrado'}
                    </p>
                    <h1 className="mt-2 text-3xl font-bold">
                        {loading.number}
                    </h1>
                    <p className="mt-3 text-xl text-stone-700">
                        {loading.product?.name} ·{' '}
                        {formatQty(loading.quantity_m3)} m³
                    </p>
                    <p className="mt-2 text-lg text-stone-600">
                        ≈ {formatQty(loading.quantity_ton)} t{' · '}
                        {formatQtyWithUnit(
                            loading.quantity,
                            loading.product?.unit,
                        )}{' '}
                        no pedido
                    </p>
                    <p className="mt-1 text-base text-stone-500">
                        Placa {loading.vehicle_plate}
                    </p>
                </div>

                <div className="flex w-full max-w-md flex-col gap-3">
                    <Button
                        asChild
                        className="h-16 rounded-2xl bg-emerald-700 text-xl font-semibold text-white hover:bg-emerald-800"
                    >
                        <Link href={index()}>Voltar à fila</Link>
                    </Button>
                    {loading.order_id && !completed && (
                        <Button
                            asChild
                            variant="outline"
                            className="h-14 rounded-2xl border-2 border-stone-300 bg-white text-lg font-semibold text-stone-900 hover:bg-stone-50"
                        >
                            <Link href={show(loading.order_id)}>
                                Continuar neste pedido
                            </Link>
                        </Button>
                    )}
                </div>
            </main>
        </>
    );
}
