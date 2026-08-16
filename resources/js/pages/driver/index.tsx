import { Form, Head, Link } from '@inertiajs/react';
import { LogOut, Truck } from 'lucide-react';
import FlashMessage from '@/components/flash-message';
import { Button } from '@/components/ui/button';
import { formatQty } from '@/lib/quantity';
import { logout } from '@/routes';
import { index, show } from '@/routes/driver';

type DriverTruck = {
    id: number;
    name: string;
    plate: string;
    capacity_m3: string;
    in_transit: boolean;
};

const headerButtonClass =
    'h-12 border-2 border-stone-300 bg-white px-4 text-base font-semibold text-stone-900 shadow-none hover:bg-stone-50';

export default function DriverIndex({
    trucks,
    driver,
}: {
    trucks: DriverTruck[];
    driver: { name: string | null };
}) {
    return (
        <>
            <Head title="Motorista" />

            <header className="sticky top-0 z-10 flex items-center justify-between gap-3 border-b border-stone-200 bg-white px-4 py-4">
                <div>
                    <p className="text-xs font-semibold tracking-wide text-stone-500 uppercase">
                        Motorista
                    </p>
                    <h1 className="text-xl font-bold text-stone-900">
                        {driver.name ?? 'Motorista'}
                    </h1>
                </div>
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
            </header>

            <main className="flex flex-1 flex-col gap-5 p-4 pb-8">
                <FlashMessage />

                <div>
                    <h2 className="text-lg font-bold text-stone-900">
                        Escolha o caminhão
                    </h2>
                    <p className="mt-1 text-base text-stone-600">
                        A capacidade da caçamba entra no cálculo de cada viagem.
                    </p>
                </div>

                {trucks.length > 0 ? (
                    <div className="grid gap-3">
                        {trucks.map((truck) => (
                            <Link
                                key={truck.id}
                                href={show(truck.id)}
                                className="block rounded-2xl border border-stone-200 bg-white p-4 shadow-sm active:scale-[0.99] active:bg-stone-50"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-2xl leading-tight font-bold text-stone-900">
                                            {truck.name}
                                        </p>
                                        <p className="mt-1 text-lg text-stone-600">
                                            Placa {truck.plate}
                                        </p>
                                    </div>
                                    {truck.in_transit && (
                                        <span className="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-950">
                                            Em viagem
                                        </span>
                                    )}
                                </div>
                                <p className="mt-4 text-xl font-bold text-stone-900">
                                    {formatQty(truck.capacity_m3)} m³
                                </p>
                            </Link>
                        ))}
                    </div>
                ) : (
                    <div className="flex flex-1 flex-col items-center justify-center rounded-3xl bg-white px-6 py-16 text-center shadow-sm ring-1 ring-stone-200">
                        <span className="flex size-16 items-center justify-center rounded-2xl bg-stone-100 text-stone-600">
                            <Truck className="size-8" />
                        </span>
                        <p className="mt-5 text-2xl font-bold text-stone-900">
                            Nenhum caminhão ativo
                        </p>
                        <p className="mt-2 max-w-sm text-base text-stone-600">
                            Peça ao escritório para cadastrar um caminhão com a
                            capacidade da caçamba.
                        </p>
                    </div>
                )}
            </main>
        </>
    );
}

DriverIndex.layout = {
    breadcrumbs: [{ title: 'Motorista', href: index() }],
};
