import { Form, Head, Link } from '@inertiajs/react';
import { Plus, Settings2 } from 'lucide-react';
import { Fragment } from 'react';
import ProductionEntryController from '@/actions/App/Http/Controllers/ProductionEntryController';
import FlashMessage from '@/components/flash-message';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatQty } from '@/lib/quantity';
import { edit as crushingCircuitsEdit } from '@/routes/crushing-circuits';
import { create, index } from '@/routes/production';
import type {
    HaulageProductionSummary,
    Paginated,
    ProductionEntry,
} from '@/types';

const shiftLabel = {
    morning: 'Manhã',
    afternoon: 'Tarde',
    night: 'Noite',
} as const;

const methodLabel = {
    trips: 'Viagens',
    quantity: 'Estimativa',
    scale: 'Balança',
} as const;

const stageLabel = {
    quarry_to_primary: 'Lavra → primário',
    plant: 'Usina',
} as const;

function formatTime(value: string | null): string | null {
    if (!value) {
        return null;
    }

    return new Date(value).toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function isDriverTrip(entry: ProductionEntry): boolean {
    return Boolean(entry.loaded_at);
}

function tripDetail(entry: ProductionEntry): string {
    if (entry.method === 'trips') {
        const trips = `${entry.trips_count ?? 0} viagens × ${
            entry.truck_capacity_m3 ? formatQty(entry.truck_capacity_m3) : '—'
        } m³${entry.truck ? ` (${entry.truck.plate})` : ''}`;

        const loaded = formatTime(entry.loaded_at);
        const unloaded = formatTime(entry.unloaded_at);

        if (loaded && unloaded) {
            const destination = entry.stage === 'plant' ? 'Usina' : 'Primário';

            return `${trips} · Lavra ${loaded} → ${destination} ${unloaded}`;
        }

        return trips;
    }

    if (entry.children && entry.children.length > 0) {
        return `${entry.children.length} produtos no circuito`;
    }

    return '—';
}

export default function ProductionIndex({
    entries,
    haulage_today: haulageToday,
    in_transit: inTransit,
}: {
    entries: Paginated<ProductionEntry>;
    haulage_today: HaulageProductionSummary;
    in_transit: ProductionEntry[];
}) {
    return (
        <>
            <Head title="Produção" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Produção"
                        description="Viagens do motorista (lavra → primário) e distribuição no circuito secundário"
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={crushingCircuitsEdit()}>
                                <Settings2 />
                                Circuito
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={create()}>
                                <Plus />
                                Novo apontamento
                            </Link>
                        </Button>
                    </div>
                </div>

                <FlashMessage />

                <div className="grid gap-3 sm:grid-cols-3">
                    <div className="rounded-xl border p-4">
                        <div className="text-xs text-muted-foreground">
                            Viagens do motorista hoje
                        </div>
                        <div className="mt-1 text-2xl font-semibold">
                            {haulageToday.trips}
                        </div>
                    </div>
                    <div className="rounded-xl border p-4">
                        <div className="text-xs text-muted-foreground">
                            Volume das viagens
                        </div>
                        <div className="mt-1 text-2xl font-semibold">
                            {formatQty(haulageToday.volume_m3)} m³
                        </div>
                        <div className="mt-1 text-xs text-muted-foreground">
                            {formatQty(haulageToday.volume_ton)} t
                        </div>
                    </div>
                    <div className="rounded-xl border p-4">
                        <div className="text-xs text-muted-foreground">
                            Em viagem agora
                        </div>
                        <div className="mt-1 text-2xl font-semibold">
                            {inTransit.length}
                        </div>
                    </div>
                </div>

                {inTransit.length > 0 && (
                    <section className="overflow-x-auto rounded-xl border">
                        <div className="border-b bg-muted/40 px-4 py-3 text-sm font-medium">
                            Em viagem (ainda não conta na produção)
                        </div>
                        <table className="w-full min-w-[720px] text-left text-sm">
                            <thead className="border-b bg-muted/20">
                                <tr>
                                    <th className="px-4 py-2 font-medium">
                                        Caminhão
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Produto
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Motorista
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Lavra
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        m³
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {inTransit.map((entry) => (
                                    <tr
                                        key={entry.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-3">
                                            {entry.truck
                                                ? `${entry.truck.name} (${entry.truck.plate})`
                                                : '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {entry.product?.name}
                                        </td>
                                        <td className="px-4 py-3">
                                            {entry.user?.name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {formatTime(entry.loaded_at) ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {entry.quantity_m3
                                                ? formatQty(entry.quantity_m3)
                                                : '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </section>
                )}

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full min-w-[960px] text-left text-sm">
                        <thead className="border-b bg-muted/40">
                            <tr>
                                <th className="px-4 py-3 font-medium">Data</th>
                                <th className="px-4 py-3 font-medium">Etapa</th>
                                <th className="px-4 py-3 font-medium">
                                    Produto
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Método
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Detalhe
                                </th>
                                <th className="px-4 py-3 font-medium">m³</th>
                                <th className="px-4 py-3 font-medium">
                                    t / qtd
                                </th>
                                <th className="px-4 py-3 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {entries.data.map((entry) => (
                                <Fragment key={entry.id}>
                                    <tr className="border-b last:border-0">
                                        <td className="px-4 py-3">
                                            {new Date(
                                                entry.produced_on,
                                            ).toLocaleDateString('pt-BR')}
                                            <div className="text-xs text-muted-foreground">
                                                {shiftLabel[entry.shift]}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-wrap items-center gap-1">
                                                <Badge variant="secondary">
                                                    {stageLabel[entry.stage]}
                                                </Badge>
                                                {isDriverTrip(entry) && (
                                                    <Badge variant="outline">
                                                        Motorista
                                                    </Badge>
                                                )}
                                            </div>
                                            {!entry.affects_stock && (
                                                <div className="mt-1 text-xs text-muted-foreground">
                                                    {entry.stage === 'plant'
                                                        ? 'Sem entrada no estoque'
                                                        : 'Alimentação (sem estoque)'}
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            {entry.product?.name}
                                        </td>
                                        <td className="px-4 py-3">
                                            {methodLabel[entry.method]}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {tripDetail(entry)}
                                        </td>
                                        <td className="px-4 py-3">
                                            {entry.quantity_m3
                                                ? formatQty(entry.quantity_m3)
                                                : '—'}
                                        </td>
                                        <td className="px-4 py-3 font-medium">
                                            {formatQty(
                                                entry.quantity_ton ??
                                                    entry.quantity,
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex justify-end">
                                                <Form
                                                    {...ProductionEntryController.destroy.form(
                                                        entry.id,
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
                                                            Remover
                                                        </Button>
                                                    )}
                                                </Form>
                                            </div>
                                        </td>
                                    </tr>
                                    {(entry.children ?? []).map((child) => (
                                        <tr
                                            key={child.id}
                                            className="border-b bg-muted/20 last:border-0"
                                        >
                                            <td className="px-4 py-2 pl-8 text-xs text-muted-foreground">
                                                └ circuito
                                            </td>
                                            <td className="px-4 py-2">
                                                <Badge variant="outline">
                                                    Usina
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-2">
                                                {child.product?.name}
                                            </td>
                                            <td className="px-4 py-2 text-muted-foreground">
                                                {child.yield_percent
                                                    ? `${child.yield_percent}%`
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-2 text-muted-foreground">
                                                Distribuição automática
                                            </td>
                                            <td className="px-4 py-2">
                                                {child.quantity_m3
                                                    ? formatQty(
                                                          child.quantity_m3,
                                                      )
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-2 font-medium">
                                                {formatQty(
                                                    child.quantity_ton ??
                                                        child.quantity,
                                                )}
                                            </td>
                                            <td className="px-4 py-2" />
                                        </tr>
                                    ))}
                                </Fragment>
                            ))}
                            {entries.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={8}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Nenhum apontamento registrado.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination meta={entries} />
            </div>
        </>
    );
}

ProductionIndex.layout = {
    breadcrumbs: [{ title: 'Produção', href: index() }],
};
