import { Form, Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import RoleController from '@/actions/App/Http/Controllers/RoleController';
import FlashMessage from '@/components/flash-message';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, edit, index } from '@/routes/roles';
import type { Paginated, Role } from '@/types';

export default function RolesIndex({
    roles,
}: {
    roles: Paginated<Role>;
}) {
    return (
        <>
            <Head title="Papéis" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Papéis"
                        description="Defina o que cada papel pode ver no menu e usar no sistema."
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            Novo papel
                        </Link>
                    </Button>
                </div>

                <FlashMessage />

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full min-w-[720px] text-left text-sm">
                        <thead className="border-b bg-muted/40">
                            <tr>
                                <th className="px-4 py-3 font-medium">Nome</th>
                                <th className="px-4 py-3 font-medium">Acessos</th>
                                <th className="px-4 py-3 font-medium">Pessoas</th>
                                <th className="px-4 py-3 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {roles.data.map((role) => (
                                <tr key={role.id} className="border-b last:border-0">
                                    <td className="px-4 py-3 font-medium">
                                        <div className="flex items-center gap-2">
                                            {role.name}
                                            {role.is_system && (
                                                <Badge variant="secondary">
                                                    Sistema
                                                </Badge>
                                            )}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {role.permissions.length}{' '}
                                        {role.permissions.length === 1
                                            ? 'tela'
                                            : 'telas'}
                                    </td>
                                    <td className="px-4 py-3">
                                        {role.users_count ?? 0}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={edit(role.id)}>
                                                    Editar
                                                </Link>
                                            </Button>
                                            {! role.is_system && (
                                                <Form
                                                    {...RoleController.destroy.form(role.id)}
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
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {roles.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Nenhum papel cadastrado.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination meta={roles} />
            </div>
        </>
    );
}

RolesIndex.layout = {
    breadcrumbs: [{ title: 'Papéis', href: index() }],
};
