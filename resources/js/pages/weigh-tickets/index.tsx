import { Form, Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import WeighTicketController from '@/actions/App/Http/Controllers/WeighTicketController';
import FlashMessage from '@/components/flash-message';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { formatQty } from '@/lib/quantity';
import { create, index, show } from '@/routes/weigh-tickets';
import type { Paginated, WeighTicket } from '@/types';

export default function WeighTicketsIndex({
    tickets,
}: {
    tickets: Paginated<WeighTicket>;
}) {
    return (
        <>
            <Head title="Balança" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Balança"
                        description="Tickets de pesagem para baixar estoque e alimentar o MarketUp"
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            Nova pesagem
                        </Link>
                    </Button>
                </div>

                <FlashMessage />

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full min-w-[800px] text-left text-sm">
                        <thead className="border-b bg-muted/40">
                            <tr>
                                <th className="px-4 py-3 font-medium">Ticket</th>
                                <th className="px-4 py-3 font-medium">Cliente</th>
                                <th className="px-4 py-3 font-medium">Produto</th>
                                <th className="px-4 py-3 font-medium">Placa</th>
                                <th className="px-4 py-3 font-medium">Líquido</th>
                                <th className="px-4 py-3 font-medium">Data</th>
                                <th className="px-4 py-3 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {tickets.data.map((ticket) => (
                                <tr key={ticket.id} className="border-b last:border-0">
                                    <td className="px-4 py-3 font-mono text-xs">
                                        {ticket.number}
                                    </td>
                                    <td className="px-4 py-3">{ticket.customer?.name}</td>
                                    <td className="px-4 py-3">{ticket.product?.name}</td>
                                    <td className="px-4 py-3">{ticket.vehicle_plate}</td>
                                    <td className="px-4 py-3 font-medium">
                                        {formatQty(ticket.net_weight)} t
                                    </td>
                                    <td className="px-4 py-3">
                                        {new Date(ticket.weighed_at).toLocaleString('pt-BR')}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={show(ticket.id)}>Ver</Link>
                                            </Button>
                                            <Form
                                                {...WeighTicketController.destroy.form(ticket.id)}
                                                options={{ preserveScroll: true }}
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        type="submit"
                                                        variant="destructive"
                                                        size="sm"
                                                        disabled={processing}
                                                    >
                                                        Estornar
                                                    </Button>
                                                )}
                                            </Form>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {tickets.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Nenhuma pesagem registrada.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination meta={tickets} />
            </div>
        </>
    );
}

WeighTicketsIndex.layout = {
    breadcrumbs: [{ title: 'Balança', href: index() }],
};
