import { Form, Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import UserController from '@/actions/App/Http/Controllers/UserController';
import FlashMessage from '@/components/flash-message';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, edit, index } from '@/routes/users';
import type { Paginated, User } from '@/types';

export default function UsersIndex({ users }: { users: Paginated<User> }) {
    return (
        <>
            <Head title="Pessoas" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Pessoas"
                        description="Quem usa o sistema. O acesso de cada pessoa vem do papel cadastrado."
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            Nova pessoa
                        </Link>
                    </Button>
                </div>

                <FlashMessage />

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full min-w-[720px] text-left text-sm">
                        <thead className="border-b bg-muted/40">
                            <tr>
                                <th className="px-4 py-3 font-medium">Nome</th>
                                <th className="px-4 py-3 font-medium">
                                    E-mail
                                </th>
                                <th className="px-4 py-3 font-medium">Papel</th>
                                <th className="px-4 py-3 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {users.data.map((person) => (
                                <tr
                                    key={person.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="px-4 py-3 font-medium">
                                        {person.name}
                                    </td>
                                    <td className="px-4 py-3">
                                        {person.email}
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge
                                            variant={
                                                person.role?.slug === 'admin'
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {person.role?.name ?? '—'}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={edit(person.id)}>
                                                    Editar
                                                </Link>
                                            </Button>
                                            <Form
                                                {...UserController.destroy.form(
                                                    person.id,
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
                            {users.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Nenhuma pessoa cadastrada.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination meta={users} />
            </div>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [{ title: 'Pessoas', href: index() }],
};
