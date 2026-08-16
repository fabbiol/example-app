import { Form, Head, Link } from '@inertiajs/react';
import RoleController from '@/actions/App/Http/Controllers/RoleController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import RolePermissionFields from '@/pages/roles/permission-fields';
import { index } from '@/routes/roles';
import type { Option, Role } from '@/types';

export default function RolesEdit({
    role,
    permissionGroups,
}: {
    role: Role;
    permissionGroups: Record<string, Option[]>;
}) {
    return (
        <>
            <Head title={`Editar ${role.name}`} />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title={role.name}
                    description={
                        role.is_system
                            ? 'Papel do sistema: você pode mudar o nome e as telas, mas não excluir.'
                            : 'Altere o nome e as telas que este papel pode usar.'
                    }
                />

                <Form
                    {...RoleController.update.form(role.id)}
                    className="max-w-3xl space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid max-w-xl gap-2">
                                <Label htmlFor="name">Nome</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    defaultValue={role.name}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <RolePermissionFields
                                groups={permissionGroups}
                                selected={role.permissions}
                                error={errors.permissions}
                            />

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Salvar
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={index()}>Cancelar</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

RolesEdit.layout = {
    breadcrumbs: [
        { title: 'Papéis', href: index() },
        { title: 'Editar', href: index() },
    ],
};
