import { Form, Head, Link } from '@inertiajs/react';
import RoleController from '@/actions/App/Http/Controllers/RoleController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import RolePermissionFields from '@/pages/roles/permission-fields';
import { create, index } from '@/routes/roles';
import type { Option } from '@/types';

export default function RolesCreate({
    permissionGroups,
}: {
    permissionGroups: Record<string, Option[]>;
}) {
    return (
        <>
            <Head title="Novo papel" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Novo papel"
                    description="Dê um nome e marque as telas que esse papel pode usar."
                />

                <Form
                    {...RoleController.store.form()}
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
                                    placeholder="Ex.: Expedição"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <RolePermissionFields
                                groups={permissionGroups}
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

RolesCreate.layout = {
    breadcrumbs: [
        { title: 'Papéis', href: index() },
        { title: 'Novo', href: create() },
    ],
};
