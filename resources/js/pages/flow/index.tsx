import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import type { OrderPhaseQty, YardPhaseQty } from '@/components/flow-diagram';
import { ExpeditionFlow, YardFlow } from '@/components/flow-diagram';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { flow } from '@/routes';

type Diagram = 'expedicao' | 'patio';

type FlowFilters = {
    period: string;
    from: string | null;
    to: string | null;
};

type PeriodOption = {
    value: string;
    label: string;
};

const visitOptions = {
    preserveState: true,
    preserveScroll: true,
    replace: true,
} as const;

export default function FlowIndex({
    expedition,
    yard,
    filters,
    periods,
}: {
    expedition: Record<string, OrderPhaseQty>;
    yard: Record<string, YardPhaseQty>;
    filters: FlowFilters;
    periods: PeriodOption[];
}) {
    const [diagram, setDiagram] = useState<Diagram>('expedicao');
    const from = filters.from ?? '';
    const to = filters.to ?? '';

    const applyPeriod = (period: string) => {
        if (period === 'today') {
            router.get(flow.url(), {}, visitOptions);

            return;
        }

        router.get(flow.url({ query: { period } }), {}, visitOptions);
    };

    const applyCustom = (nextFrom: string, nextTo: string) => {
        const nextRangeFrom = nextFrom || nextTo;
        const nextRangeTo = nextTo || nextFrom;

        if (nextRangeFrom === '' || nextRangeTo === '') {
            applyPeriod('all');

            return;
        }

        router.get(
            flow.url({
                query: {
                    period: 'custom',
                    from: nextRangeFrom,
                    to: nextRangeTo,
                },
            }),
            {},
            visitOptions,
        );
    };

    return (
        <>
            <Head title="Fluxo" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Fluxo"
                    description="Quantidade em cada fase da expedição e o volume apontado no pátio, no período escolhido."
                />

                <div className="flex flex-col gap-3">
                    <div className="flex flex-wrap gap-2">
                        {periods.map((period) => (
                            <Button
                                key={period.value}
                                type="button"
                                size="sm"
                                variant={
                                    filters.period === period.value
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() => applyPeriod(period.value)}
                            >
                                {period.label}
                            </Button>
                        ))}
                    </div>

                    <div className="flex flex-wrap items-end gap-3">
                        <div className="grid gap-2">
                            <Label htmlFor="flow-from">De</Label>
                            <Input
                                id="flow-from"
                                type="date"
                                className="w-40"
                                value={from}
                                onChange={(event) => {
                                    applyCustom(event.target.value, to);
                                }}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="flow-to">Até</Label>
                            <Input
                                id="flow-to"
                                type="date"
                                className="w-40"
                                value={to}
                                onChange={(event) => {
                                    applyCustom(from, event.target.value);
                                }}
                            />
                        </div>
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        variant={
                            diagram === 'expedicao' ? 'default' : 'outline'
                        }
                        onClick={() => setDiagram('expedicao')}
                    >
                        Expedição
                    </Button>
                    <Button
                        type="button"
                        variant={diagram === 'patio' ? 'default' : 'outline'}
                        onClick={() => setDiagram('patio')}
                    >
                        Pátio
                    </Button>
                </div>

                <div className="rounded-xl border p-4 md:p-6">
                    {diagram === 'expedicao' ? (
                        <ExpeditionFlow phases={expedition} />
                    ) : (
                        <YardFlow phases={yard} />
                    )}
                </div>

                {diagram === 'expedicao' ? (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full min-w-[640px] text-left text-sm">
                            <thead className="border-b bg-muted/40">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        De
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Para
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Quando
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="border-b">
                                    <td className="px-4 py-3 text-muted-foreground">
                                        —
                                    </td>
                                    <td className="px-4 py-3">Aberto</td>
                                    <td className="px-4 py-3">
                                        Cadastro do pedido
                                    </td>
                                </tr>
                                <tr className="border-b">
                                    <td className="px-4 py-3">Aberto</td>
                                    <td className="px-4 py-3">Agendado</td>
                                    <td className="px-4 py-3">
                                        Edição com data de agendamento
                                    </td>
                                </tr>
                                <tr className="border-b">
                                    <td className="px-4 py-3">
                                        Aberto ou Agendado
                                    </td>
                                    <td className="px-4 py-3">Carregando</td>
                                    <td className="px-4 py-3">
                                        Fila da pá, carregamento ou balança —
                                        ainda falta mais que 0,050
                                    </td>
                                </tr>
                                <tr className="border-b">
                                    <td className="px-4 py-3">
                                        Aberto, Agendado ou Carregando
                                    </td>
                                    <td className="px-4 py-3">Concluído</td>
                                    <td className="px-4 py-3">
                                        Quantidade carregada cobre o pedido
                                    </td>
                                </tr>
                                <tr className="border-b">
                                    <td className="px-4 py-3">Qualquer um</td>
                                    <td className="px-4 py-3">Cancelado</td>
                                    <td className="px-4 py-3">
                                        Mudança de status na edição
                                    </td>
                                </tr>
                                <tr>
                                    <td className="px-4 py-3">
                                        Carregando ou Concluído
                                    </td>
                                    <td className="px-4 py-3">
                                        Aberto ou Carregando
                                    </td>
                                    <td className="px-4 py-3">
                                        Apaga estimativa ou ticket (estorno)
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full min-w-[640px] text-left text-sm">
                            <thead className="border-b bg-muted/40">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Etapa
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Circuito
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Estoque
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="border-b">
                                    <td className="px-4 py-3">
                                        Usina / produtos
                                    </td>
                                    <td className="px-4 py-3">Não se aplica</td>
                                    <td className="px-4 py-3">
                                        Soma no produto apontado
                                    </td>
                                </tr>
                                <tr className="border-b">
                                    <td className="px-4 py-3">
                                        Lavra → primário
                                    </td>
                                    <td className="px-4 py-3">Desligado</td>
                                    <td className="px-4 py-3">
                                        Soma no produto de feed
                                    </td>
                                </tr>
                                <tr className="border-b">
                                    <td className="px-4 py-3">
                                        Lavra → primário
                                    </td>
                                    <td className="px-4 py-3">Ligado</td>
                                    <td className="px-4 py-3">
                                        Feed não soma. Cada brita filha entra
                                        pelo %
                                    </td>
                                </tr>
                                <tr>
                                    <td className="px-4 py-3">
                                        Qualquer lançamento
                                    </td>
                                    <td className="px-4 py-3">
                                        Excluir apontamento
                                    </td>
                                    <td className="px-4 py-3">
                                        Estorna o que tinha afetado estoque
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                )}

                <p className="text-sm text-muted-foreground">
                    {diagram === 'expedicao'
                        ? 'Expedição considera pedidos criados no período. O pedido não baixa estoque — a baixa acontece na expedição (pá ou balança).'
                        : 'Pátio considera a data do apontamento. Cada lançamento é um evento. Balança de produção ainda não está ativa.'}
                </p>
            </div>
        </>
    );
}

FlowIndex.layout = {
    breadcrumbs: [{ title: 'Fluxo', href: flow() }],
};
