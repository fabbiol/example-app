import { Form, Head, Link } from '@inertiajs/react';
import TruckController from '@/actions/App/Http/Controllers/TruckController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index } from '@/routes/trucks';

export default function TrucksCreate() {
    return (
        <>
            <Head title="Novo caminhão" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Novo caminhão"
                    description="Cadastre a capacidade da caçamba usada nas viagens da lavra"
                />

                <Form {...TruckController.store.form()} className="max-w-xl space-y-6">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nome</Label>
                                <Input id="name" name="name" required placeholder="Basculante 01" />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="plate">Placa</Label>
                                <Input id="plate" name="plate" required placeholder="LAV-0001" />
                                <InputError message={errors.plate} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="capacity_m3">Capacidade da caçamba (m³)</Label>
                                <Input
                                    id="capacity_m3"
                                    name="capacity_m3"
                                    type="number"
                                    step="0.001"
                                    min="0.001"
                                    required
                                    defaultValue="10"
                                />
                                <InputError message={errors.capacity_m3} />
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

TrucksCreate.layout = {
    breadcrumbs: [
        { title: 'Caminhões', href: index() },
        { title: 'Novo', href: create() },
    ],
};
