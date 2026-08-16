import { formatQty } from '@/lib/quantity';
import { cn } from '@/lib/utils';

export type OrderPhaseQty = {
    orders: number;
    requested_ton: string;
    requested_m3: string;
    loaded_ton: string;
    loaded_m3: string;
    remaining_ton: string;
    remaining_m3: string;
};

export type YardPhaseQty = {
    entries: number;
    ton: string;
    m3: string;
};

function StateBox({
    title,
    count,
    volume,
    extra,
    hint,
    tone = 'mid',
}: {
    title: string;
    count?: string;
    volume?: string;
    extra?: string;
    hint: string;
    tone?: 'start' | 'mid' | 'end' | 'abort';
}) {
    return (
        <div
            className={cn(
                'w-full max-w-60 rounded-xl border px-4 py-3 text-left',
                tone === 'start' && 'bg-muted/70',
                tone === 'mid' && 'bg-card',
                tone === 'end' && 'bg-muted/40',
                tone === 'abort' && 'border-dashed bg-card',
            )}
        >
            <p className="text-sm font-medium">{title}</p>
            {count ? (
                <p className="mt-1 text-lg font-semibold tracking-tight tabular-nums">
                    {count}
                </p>
            ) : null}
            {volume ? (
                <p className="text-sm font-medium tabular-nums">{volume}</p>
            ) : null}
            {extra ? (
                <p className="text-xs text-muted-foreground">{extra}</p>
            ) : null}
            <p className="text-xs text-muted-foreground">{hint}</p>
        </div>
    );
}

function DownArrow({
    label,
    dashed = false,
}: {
    label?: string;
    dashed?: boolean;
}) {
    return (
        <div className="flex flex-col items-center gap-1 py-1">
            {label ? (
                <span className="max-w-56 text-center text-[11px] text-muted-foreground">
                    {label}
                </span>
            ) : null}
            <span
                className={cn(
                    'h-7 w-px',
                    dashed
                        ? 'border-l border-dashed border-muted-foreground/60'
                        : 'bg-primary/40',
                )}
            />
        </div>
    );
}

function countLabel(count: number, singular: string, plural: string): string {
    return `${count} ${count === 1 ? singular : plural}`;
}

function volumeLine(ton: string, m3: string): string {
    return `${formatQty(ton)} t · ${formatQty(m3)} m³`;
}

function orderVolume(
    phase: OrderPhaseQty,
    mode: 'remaining' | 'loaded' | 'requested',
): string {
    if (mode === 'loaded') {
        return volumeLine(phase.loaded_ton, phase.loaded_m3);
    }

    if (mode === 'requested') {
        return volumeLine(phase.requested_ton, phase.requested_m3);
    }

    return volumeLine(phase.remaining_ton, phase.remaining_m3);
}

function yardCount(phase: YardPhaseQty): string {
    return countLabel(phase.entries, 'lançamento', 'lançamentos');
}

function yardVolume(phase: YardPhaseQty): string {
    return volumeLine(phase.ton, phase.m3);
}

export function ExpeditionFlow({
    phases,
}: {
    phases: Record<string, OrderPhaseQty>;
}) {
    return (
        <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_15rem]">
            <div className="flex flex-col items-center">
                <StateBox
                    title="Aberto"
                    count={countLabel(phases.open.orders, 'pedido', 'pedidos')}
                    volume={orderVolume(phases.open, 'remaining')}
                    hint="Volume ainda a carregar"
                    tone="start"
                />
                <DownArrow label="com data de agendamento" />
                <StateBox
                    title="Agendado"
                    count={countLabel(
                        phases.scheduled.orders,
                        'pedido',
                        'pedidos',
                    )}
                    volume={orderVolume(phases.scheduled, 'remaining')}
                    hint="Na fila da expedição"
                />
                <DownArrow label="modo pá, carregamento ou balança" />
                <StateBox
                    title="Carregando"
                    count={countLabel(
                        phases.loading.orders,
                        'pedido',
                        'pedidos',
                    )}
                    volume={orderVolume(phases.loading, 'remaining')}
                    extra={`Já saiu ${volumeLine(phases.loading.loaded_ton, phases.loading.loaded_m3)}`}
                    hint="Já saiu material, ainda falta"
                />
                <DownArrow label="carregado cobre o pedido (tolerância 0,050)" />
                <StateBox
                    title="Concluído"
                    count={countLabel(
                        phases.completed.orders,
                        'pedido',
                        'pedidos',
                    )}
                    volume={orderVolume(phases.completed, 'loaded')}
                    hint="Saiu da fila"
                    tone="end"
                />
            </div>

            <div className="flex flex-col items-center justify-center gap-3 lg:border-l lg:pl-8">
                <StateBox
                    title="Cancelado"
                    count={countLabel(
                        phases.cancelled.orders,
                        'pedido',
                        'pedidos',
                    )}
                    volume={orderVolume(phases.cancelled, 'requested')}
                    hint="Fora da fila"
                    tone="abort"
                />
                <p className="max-w-60 text-center text-xs text-muted-foreground">
                    Entra pela edição do pedido, de qualquer status. Estorno de
                    estimativa ou ticket volta para Aberto ou Carregando.
                </p>
            </div>
        </div>
    );
}

export function YardFlow({ phases }: { phases: Record<string, YardPhaseQty> }) {
    return (
        <div className="flex flex-col items-center">
            <StateBox
                title="Lavra"
                count={yardCount(phases.quarry)}
                volume={yardVolume(phases.quarry)}
                hint="Alimentação da frente"
                tone="start"
            />
            <DownArrow label="viagens de caçamba ou quantidade estimada" />
            <StateBox
                title="Apontamento"
                count={yardCount(phases.entries)}
                volume={yardVolume(phases.entries)}
                hint="Todos os lançamentos (pais)"
            />

            <div className="my-2 hidden h-px w-full max-w-3xl bg-primary/30 sm:block" />

            <div className="mt-2 grid w-full gap-4 sm:grid-cols-3">
                <div className="flex flex-col items-center">
                    <DownArrow label="usina / produtos" />
                    <StateBox
                        title="Usina"
                        count={yardCount(phases.plant)}
                        volume={yardVolume(phases.plant)}
                        hint="Produto acabado"
                    />
                    <DownArrow label="soma no produto" />
                    <StateBox
                        title="Estoque do produto"
                        volume={yardVolume(phases.plant)}
                        hint="Entrou por esta via"
                        tone="end"
                    />
                </div>
                <div className="flex flex-col items-center">
                    <DownArrow label="lavra → primário" />
                    <StateBox
                        title="Primário simples"
                        count={yardCount(phases.primary_plain)}
                        volume={yardVolume(phases.primary_plain)}
                        hint="Sem circuito"
                    />
                    <DownArrow label="soma no feed" />
                    <StateBox
                        title="Estoque do feed"
                        volume={yardVolume(phases.primary_plain)}
                        hint="Entrou por esta via"
                        tone="end"
                    />
                </div>
                <div className="flex flex-col items-center">
                    <DownArrow label="primário + circuito" />
                    <StateBox
                        title="Feed não soma"
                        count={yardCount(phases.primary_circuit)}
                        volume={yardVolume(phases.primary_circuit)}
                        hint="Só alimenta a usina"
                    />
                    <DownArrow label="filhos pelo % do circuito" />
                    <StateBox
                        title="Estoque das britas"
                        volume={yardVolume(phases.circuit_products)}
                        hint="Britas geradas pelo circuito"
                        tone="end"
                    />
                </div>
            </div>
        </div>
    );
}
