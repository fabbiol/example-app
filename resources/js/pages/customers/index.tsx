import { Form, Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import CustomerController from '@/actions/App/Http/Controllers/CustomerController';
import FlashMessage from '@/components/flash-message';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, edit, index, show } from '@/routes/customers';
import type { Customer, Paginated } from '@/types';

export default function CustomersIndex({
    customers,
}: {
    customers: Paginated<Customer>;
}) {
    return (
        <>
            <Head title="Clientes" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Clientes"
                        description="Cadastro operacional com vínculo opcional ao MarketUp"
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            Novo cliente
                        </Link>
                    </Button>
                </div>

                <FlashMessage />

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full min-w-[720px] text-left text-sm">
                        <thead className="border-b bg-muted/40">
                            <tr>
                                <th className="px-4 py-3 font-medium">Nome</th>
                                <th className="px-4 py-3 font-medium">Documento</th>
                                <th className="px-4 py-3 font-medium">MarketUp</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {customers.data.map((customer) => (
                                <tr key={customer.id} className="border-b last:border-0">
                                    <td className="px-4 py-3">{customer.name}</td>
                                    <td className="px-4 py-3">{customer.document || '—'}</td>
                                    <td className="px-4 py-3 font-mono text-xs">
                                        {customer.marketup_code || '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge variant={customer.is_active ? 'default' : 'secondary'}>
                                            {customer.is_active ? 'Ativo' : 'Inativo'}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={show(customer.id)}>Ver</Link>
                                            </Button>
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={edit(customer.id)}>Editar</Link>
                                            </Button>
                                            <Form
                                                {...CustomerController.destroy.form(customer.id)}
                                                options={{ preserveScroll: true }}
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        type="submit"
                                                        variant="destructive"
                                                        size="sm"
                                                        disabled={processing}
                                                    >
                                                        Excluir
                                                    </Button>
                                                )}
                                            </Form>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {customers.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Nenhum cliente cadastrado.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination meta={customers} />
            </div>
        </>
    );
}

CustomersIndex.layout = {
    breadcrumbs: [{ title: 'Clientes', href: index() }],
};
