import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatQty } from '@/lib/quantity';
import { edit, index } from '@/routes/customers';
import type { Customer, Order } from '@/types';

export default function CustomersShow({
    customer,
}: {
    customer: Customer & { orders?: Order[] };
}) {
    return (
        <>
            <Head title={customer.name} />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title={customer.name}
                        description={
                            customer.marketup_code
                                ? `MarketUp ${customer.marketup_code}`
                                : undefined
                        }
                    />
                    <Button asChild>
                        <Link href={edit(customer.id)}>Editar</Link>
                    </Button>
                </div>

                <dl className="grid gap-4 rounded-xl border p-4 sm:grid-cols-2">
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Documento
                        </dt>
                        <dd>{customer.document || '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Telefone
                        </dt>
                        <dd>{customer.phone || '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Status
                        </dt>
                        <dd className="mt-1">
                            <Badge
                                variant={
                                    customer.is_active ? 'default' : 'secondary'
                                }
                            >
                                {customer.is_active ? 'Ativo' : 'Inativo'}
                            </Badge>
                        </dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Observações
                        </dt>
                        <dd>{customer.notes || '—'}</dd>
                    </div>
                </dl>

                <div className="space-y-3">
                    <h3 className="text-sm font-medium">Pedidos recentes</h3>
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b bg-muted/40">
                                <tr>
                                    <th className="px-4 py-3 font-medium">#</th>
                                    <th className="px-4 py-3 font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Qtd.
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {(customer.orders ?? []).map((order) => (
                                    <tr
                                        key={order.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-3">
                                            {order.id}
                                        </td>
                                        <td className="px-4 py-3">
                                            {order.status}
                                        </td>
                                        <td className="px-4 py-3">
                                            {formatQty(
                                                order.quantity_requested,
                                            )}
                                        </td>
                                    </tr>
                                ))}
                                {(customer.orders ?? []).length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={3}
                                            className="px-4 py-6 text-center text-muted-foreground"
                                        >
                                            Sem pedidos ainda.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <Button variant="outline" asChild className="w-fit">
                    <Link href={index()}>Voltar</Link>
                </Button>
            </div>
        </>
    );
}

CustomersShow.layout = {
    breadcrumbs: [
        { title: 'Clientes', href: index() },
        { title: 'Detalhes', href: index() },
    ],
};
