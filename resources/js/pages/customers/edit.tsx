import { Form, Head, Link } from '@inertiajs/react';
import CustomerController from '@/actions/App/Http/Controllers/CustomerController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/customers';
import type { Customer } from '@/types';

export default function CustomersEdit({ customer }: { customer: Customer }) {
    return (
        <>
            <Head title={`Editar ${customer.name}`} />

            <div className="flex flex-col gap-6 p-4">
                <Heading title={`Editar ${customer.name}`} />

                <Form
                    {...CustomerController.update.form(customer.id)}
                    className="max-w-xl space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nome</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    defaultValue={customer.name}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="document">CNPJ/CPF</Label>
                                <Input
                                    id="document"
                                    name="document"
                                    defaultValue={customer.document ?? ''}
                                />
                                <InputError message={errors.document} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="marketup_code">
                                    Código MarketUp
                                </Label>
                                <Input
                                    id="marketup_code"
                                    name="marketup_code"
                                    defaultValue={customer.marketup_code ?? ''}
                                />
                                <InputError message={errors.marketup_code} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Telefone</Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    defaultValue={customer.phone ?? ''}
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    id="is_active"
                                    name="is_active"
                                    type="checkbox"
                                    value="1"
                                    defaultChecked={customer.is_active}
                                    className="size-4 rounded border border-input"
                                />
                                <Label htmlFor="is_active">Cliente ativo</Label>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="notes">Observações</Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows={3}
                                    defaultValue={customer.notes ?? ''}
                                    className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                                />
                                <InputError message={errors.notes} />
                            </div>

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

CustomersEdit.layout = {
    breadcrumbs: [
        { title: 'Clientes', href: index() },
        { title: 'Editar', href: index() },
    ],
};
