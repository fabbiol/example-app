import { Form, Head, Link } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { useState } from 'react';
import DriverOperatorController from '@/actions/App/Http/Controllers/DriverOperatorController';
import FlashMessage from '@/components/flash-message';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { formatQty, unitLabel } from '@/lib/quantity';
import { logout } from '@/routes';
import { index } from '@/routes/driver';
import type { Product, ProductionEntry, Truck } from '@/types';

const headerButtonClass =
    'h-12 border-2 border-stone-300 bg-white px-4 text-base font-semibold text-stone-900 shadow-none hover:bg-stone-50';

function formatWhen(value: string): string {
    return new Date(value).toLocaleString('pt-BR');
}

export default function DriverShow({
    truck,
    products,
    openTrip,
    summary,
    driver,
}: {
    truck: Pick<Truck, 'id' | 'name' | 'plate' | 'capacity_m3'>;
    products: Array<Pick<Product, 'id' | 'name' | 'code' | 'unit' | 'density'>>;
    openTrip: ProductionEntry | null;
    summary: { trips_today: number; volume_m3_today: string };
    driver: { name: string | null };
}) {
    const [productId, setProductId] = useState(
        openTrip ? String(openTrip.product_id) : String(products[0]?.id ?? ''),
    );
    const [enterStock, setEnterStock] = useState(true);

    return (
        <>
            <Head title={`${truck.name} · ${truck.plate}`} />

            <header className="sticky top-0 z-10 border-b border-stone-200 bg-white px-4 py-4">
                <div className="flex items-center justify-between gap-3">
                    <Link
                        href={index()}
                        className="inline-flex h-11 items-center rounded-lg px-2 text-base font-semibold text-stone-800 active:bg-stone-100"
                    >
                        ← Caminhões
                    </Link>
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
                <p className="mt-2 text-xs font-semibold tracking-wide text-stone-500 uppercase">
                    {driver.name ?? 'Motorista'}
                </p>
                <h1 className="text-2xl font-bold text-stone-900">
                    {truck.name}
                </h1>
                <p className="text-lg text-stone-600">
                    Placa {truck.plate} · {formatQty(truck.capacity_m3)} m³
                </p>
            </header>

            <main className="flex flex-1 flex-col gap-4 p-4 pb-8">
                <FlashMessage />

                <div className="grid grid-cols-2 gap-3">
                    <div className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-stone-200">
                        <p className="text-xs font-semibold tracking-wide text-stone-500 uppercase">
                            Viagens hoje
                        </p>
                        <p className="text-3xl font-bold text-stone-900">
                            {summary.trips_today}
                        </p>
                    </div>
                    <div className="rounded-2xl bg-emerald-50 p-4 shadow-sm ring-1 ring-emerald-100">
                        <p className="text-xs font-semibold tracking-wide text-emerald-800 uppercase">
                            Volume hoje
                        </p>
                        <p className="text-3xl font-bold text-emerald-950">
                            {formatQty(summary.volume_m3_today)} m³
                        </p>
                    </div>
                </div>

                {openTrip ? (
                    <div className="space-y-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <p className="text-sm font-semibold tracking-wide text-amber-900 uppercase">
                            Em viagem
                        </p>
                        <p className="text-xl font-bold text-stone-900">
                            {openTrip.product?.name}
                        </p>
                        <p className="text-base text-stone-700">
                            Carregado na lavra às{' '}
                            {formatWhen(openTrip.loaded_at ?? '')}
                        </p>
                        <p className="text-lg font-semibold text-stone-900">
                            Esta viagem: {formatQty(openTrip.quantity_m3)} m³ ≈{' '}
                            {formatQty(openTrip.quantity_ton)} t
                        </p>

                        <Form
                            {...DriverOperatorController.unload.form(truck.id)}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <input
                                        type="hidden"
                                        name="stage"
                                        value="quarry_to_primary"
                                    />
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="h-16 w-full rounded-2xl bg-emerald-700 text-lg font-semibold text-white hover:bg-emerald-800"
                                    >
                                        Descarregar no primário
                                    </Button>
                                    <p className="text-sm text-stone-600">
                                        Alimenta o britador. Se houver circuito
                                        padrão, distribui nos agregados.
                                    </p>
                                    <InputError message={errors.truck_id} />
                                    <InputError message={errors.stage} />
                                </>
                            )}
                        </Form>

                        <Form
                            {...DriverOperatorController.unload.form(truck.id)}
                            className="space-y-3 rounded-2xl border border-stone-200 bg-white p-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <input
                                        type="hidden"
                                        name="stage"
                                        value="plant"
                                    />
                                    <input
                                        type="hidden"
                                        name="affects_stock"
                                        value={enterStock ? '1' : '0'}
                                    />
                                    <p className="text-base font-semibold text-stone-900">
                                        Ou entrar direto na usina
                                    </p>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setEnterStock((current) => !current)
                                        }
                                        aria-pressed={enterStock}
                                        className={`flex w-full items-start gap-3 rounded-2xl border-2 p-4 text-left ${
                                            enterStock
                                                ? 'border-emerald-600 bg-emerald-50'
                                                : 'border-stone-300 bg-stone-50'
                                        }`}
                                    >
                                        <span
                                            className={`mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-md border-2 ${
                                                enterStock
                                                    ? 'border-emerald-700 bg-emerald-700 text-white'
                                                    : 'border-stone-400 bg-white'
                                            }`}
                                            aria-hidden
                                        >
                                            {enterStock ? '✓' : ''}
                                        </span>
                                        <span>
                                            <span className="block text-lg font-semibold text-stone-900">
                                                Entrar no estoque
                                            </span>
                                            <span className="mt-1 block text-sm text-stone-600">
                                                {enterStock
                                                    ? 'Soma a quantidade deste produto no estoque da usina.'
                                                    : 'Só registra a viagem, sem mexer no estoque.'}
                                            </span>
                                        </span>
                                    </button>
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="h-16 w-full rounded-2xl bg-stone-800 text-lg font-semibold text-white hover:bg-stone-900"
                                    >
                                        Descarregar na usina
                                    </Button>
                                    <InputError message={errors.truck_id} />
                                    <InputError message={errors.stage} />
                                    <InputError
                                        message={errors.affects_stock}
                                    />
                                </>
                            )}
                        </Form>

                        <Form
                            {...DriverOperatorController.cancel.form(truck.id)}
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                    className="h-14 w-full rounded-2xl border-2 border-stone-300 bg-white text-lg font-semibold text-stone-900 hover:bg-stone-50"
                                >
                                    Cancelar carregamento
                                </Button>
                            )}
                        </Form>
                    </div>
                ) : (
                    <Form
                        {...DriverOperatorController.load.form(truck.id)}
                        className="space-y-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-stone-200"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="product_id"
                                        className="text-base"
                                    >
                                        Produto na caçamba
                                    </Label>
                                    <select
                                        id="product_id"
                                        name="product_id"
                                        required
                                        value={productId}
                                        onChange={(event) =>
                                            setProductId(event.target.value)
                                        }
                                        className="h-14 w-full rounded-xl border border-input bg-white px-3 text-lg shadow-xs"
                                    >
                                        <option value="" disabled>
                                            Selecione
                                        </option>
                                        {products.map((product) => (
                                            <option
                                                key={product.id}
                                                value={product.id}
                                            >
                                                {product.name} (
                                                {unitLabel(product.unit)})
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.product_id} />
                                </div>

                                <p className="text-base text-stone-600">
                                    Ao carregar, conta 1 viagem de{' '}
                                    {formatQty(truck.capacity_m3)} m³ depois da
                                    descarga no primário ou na usina.
                                </p>

                                <Button
                                    type="submit"
                                    disabled={
                                        processing || products.length === 0
                                    }
                                    className="h-16 w-full rounded-2xl bg-emerald-700 text-lg font-semibold text-white hover:bg-emerald-800"
                                >
                                    Carregar na lavra
                                </Button>
                                <InputError message={errors.truck_id} />
                            </>
                        )}
                    </Form>
                )}
            </main>
        </>
    );
}

DriverShow.layout = {
    breadcrumbs: [{ title: 'Motorista', href: index() }],
};
