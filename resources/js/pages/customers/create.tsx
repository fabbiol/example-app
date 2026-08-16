import { Form, Head, Link } from '@inertiajs/react';
import CustomerController from '@/actions/App/Http/Controllers/CustomerController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index } from '@/routes/customers';

export default function CustomersCreate() {
    return (
        <>
            <Head title="Novo cliente" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Novo cliente"
                    description="Use o código MarketUp para conciliar faturamento depois"
                />

                <Form
                    {...CustomerController.store.form()}
                    className="max-w-xl space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nome</Label>
                                <Input id="name" name="name" required />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="document">CNPJ/CPF</Label>
                                <Input id="document" name="document" />
                                <InputError message={errors.document} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="marketup_code">
                                    Código MarketUp
                                </Label>
                                <Input
                                    id="marketup_code"
                                    name="marketup_code"
                                />
                                <InputError message={errors.marketup_code} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Telefone</Label>
                                <Input id="phone" name="phone" />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="notes">Observações</Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows={3}
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

CustomersCreate.layout = {
    breadcrumbs: [
        { title: 'Clientes', href: index() },
        { title: 'Novo', href: create() },
    ],
};
