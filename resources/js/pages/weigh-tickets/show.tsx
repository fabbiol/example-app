import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { formatQty, formatQtyWithUnit } from '@/lib/quantity';
import { index } from '@/routes/weigh-tickets';
import type { WeighTicket } from '@/types';

export default function WeighTicketsShow({ ticket }: { ticket: WeighTicket }) {
    return (
        <>
            <Head title={ticket.number} />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title={ticket.number}
                    description="Use estes dados para emitir a NF no MarketUp"
                />

                <dl className="grid gap-4 rounded-xl border p-4 sm:grid-cols-2">
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Cliente
                        </dt>
                        <dd className="font-medium">{ticket.customer?.name}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Produto
                        </dt>
                        <dd className="font-medium">{ticket.product?.name}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Pedido
                        </dt>
                        <dd>
                            {ticket.order_id ? `#${ticket.order_id}` : 'Avulso'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">Placa</dt>
                        <dd>{ticket.vehicle_plate}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">Tara</dt>
                        <dd>{formatQty(ticket.tare_weight)} t</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">Bruto</dt>
                        <dd>{formatQty(ticket.gross_weight)} t</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Líquido (balança)
                        </dt>
                        <dd className="text-lg font-semibold">
                            {formatQty(ticket.net_weight)} t
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Equivalente em m³
                        </dt>
                        <dd>
                            {ticket.quantity_m3
                                ? `${formatQty(ticket.quantity_m3)} m³`
                                : '—'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Baixa no estoque
                        </dt>
                        <dd>
                            {formatQtyWithUnit(
                                ticket.quantity,
                                ticket.product?.unit,
                            )}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Densidade
                        </dt>
                        <dd>
                            {ticket.density
                                ? `${formatQty(ticket.density, 2)} t/m³`
                                : '—'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Pesagem
                        </dt>
                        <dd>
                            {new Date(ticket.weighed_at).toLocaleString(
                                'pt-BR',
                            )}
                        </dd>
                    </div>
                    <div className="sm:col-span-2">
                        <dt className="text-sm text-muted-foreground">
                            Observações
                        </dt>
                        <dd>{ticket.notes || '—'}</dd>
                    </div>
                </dl>

                <Button variant="outline" asChild className="w-fit">
                    <Link href={index()}>Voltar</Link>
                </Button>
            </div>
        </>
    );
}

WeighTicketsShow.layout = {
    breadcrumbs: [
        { title: 'Balança', href: index() },
        { title: 'Ticket', href: index() },
    ],
};
