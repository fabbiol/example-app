import { Form, Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import TruckController from '@/actions/App/Http/Controllers/TruckController';
import FlashMessage from '@/components/flash-message';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, edit, index } from '@/routes/trucks';
import type { Paginated, Truck } from '@/types';

export default function TrucksIndex({ trucks }: { trucks: Paginated<Truck> }) {
    return (
        <>
            <Head title="Caminhões" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Caminhões da lavra"
                        description="Caçamba por caminhão para apontar viagens até o primário"
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            Novo caminhão
                        </Link>
                    </Button>
                </div>

                <FlashMessage />

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full min-w-[640px] text-left text-sm">
                        <thead className="border-b bg-muted/40">
                            <tr>
                                <th className="px-4 py-3 font-medium">Nome</th>
                                <th className="px-4 py-3 font-medium">Placa</th>
                                <th className="px-4 py-3 font-medium">
                                    Caçamba
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Status
                                </th>
                                <th className="px-4 py-3 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {trucks.data.map((truck) => (
                                <tr
                                    key={truck.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="px-4 py-3">{truck.name}</td>
                                    <td className="px-4 py-3 font-mono text-xs">
                                        {truck.plate}
                                    </td>
                                    <td className="px-4 py-3">
                                        {truck.capacity_m3} m³
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge
                                            variant={
                                                truck.is_active
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {truck.is_active
                                                ? 'Ativo'
                                                : 'Inativo'}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={edit(truck.id)}>
                                                    Editar
                                                </Link>
                                            </Button>
                                            <Form
                                                {...TruckController.destroy.form(
                                                    truck.id,
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
                                                        Excluir
                                                    </Button>
                                                )}
                                            </Form>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {trucks.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Nenhum caminhão cadastrado.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination meta={trucks} />
            </div>
        </>
    );
}

TrucksIndex.layout = {
    breadcrumbs: [{ title: 'Caminhões', href: index() }],
};
