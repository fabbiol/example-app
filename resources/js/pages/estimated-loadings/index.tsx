import { Form, Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import EstimatedLoadingController from '@/actions/App/Http/Controllers/EstimatedLoadingController';
import FlashMessage from '@/components/flash-message';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { formatQty } from '@/lib/quantity';
import { create, index, show } from '@/routes/estimated-loadings';
import type { EstimatedLoading, Paginated } from '@/types';

export default function EstimatedLoadingsIndex({
    loadings,
}: {
    loadings: Paginated<EstimatedLoading>;
}) {
    return (
        <>
            <Head title="Carregamentos" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Carregamentos"
                        description="Saída pelo pátio, sem passar na balança."
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            Novo carregamento
                        </Link>
                    </Button>
                </div>

                <FlashMessage />

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full min-w-[880px] text-left text-sm">
                        <thead className="border-b bg-muted/40">
                            <tr>
                                <th className="px-4 py-3 font-medium">Nº</th>
                                <th className="px-4 py-3 font-medium">
                                    Cliente
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Produto
                                </th>
                                <th className="px-4 py-3 font-medium">m³</th>
                                <th className="px-4 py-3 font-medium">t</th>
                                <th className="px-4 py-3 font-medium">
                                    Conchas
                                </th>
                                <th className="px-4 py-3 font-medium">Data</th>
                                <th className="px-4 py-3 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {loadings.data.map((loading) => (
                                <tr
                                    key={loading.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="px-4 py-3 font-mono text-xs">
                                        {loading.number}
                                    </td>
                                    <td className="px-4 py-3">
                                        {loading.customer?.name}
                                    </td>
                                    <td className="px-4 py-3">
                                        {loading.product?.name}
                                    </td>
                                    <td className="px-4 py-3">
                                        {formatQty(loading.quantity_m3)}
                                    </td>
                                    <td className="px-4 py-3 font-medium">
                                        {formatQty(loading.quantity_ton)}
                                    </td>
                                    <td className="px-4 py-3">
                                        {loading.buckets_count ?? '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        {new Date(
                                            loading.loaded_at,
                                        ).toLocaleString('pt-BR')}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={show(loading.id)}>
                                                    Ver
                                                </Link>
                                            </Button>
                                            <Form
                                                {...EstimatedLoadingController.destroy.form(
                                                    loading.id,
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
                            {loadings.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={8}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Nenhum carregamento registrado.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination meta={loadings} />
            </div>
        </>
    );
}

EstimatedLoadingsIndex.layout = {
    breadcrumbs: [{ title: 'Carregamentos', href: index() }],
};
