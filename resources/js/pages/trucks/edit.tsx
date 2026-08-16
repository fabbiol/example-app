import { Form, Head, Link } from '@inertiajs/react';
import TruckController from '@/actions/App/Http/Controllers/TruckController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/trucks';
import type { Truck } from '@/types';

export default function TrucksEdit({ truck }: { truck: Truck }) {
    return (
        <>
            <Head title={`Editar ${truck.name}`} />

            <div className="flex flex-col gap-6 p-4">
                <Heading title={`Editar ${truck.name}`} />

                <Form
                    {...TruckController.update.form(truck.id)}
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
                                    defaultValue={truck.name}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="plate">Placa</Label>
                                <Input
                                    id="plate"
                                    name="plate"
                                    required
                                    defaultValue={truck.plate}
                                />
                                <InputError message={errors.plate} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="capacity_m3">
                                    Capacidade da caçamba (m³)
                                </Label>
                                <Input
                                    id="capacity_m3"
                                    name="capacity_m3"
                                    type="number"
                                    step="0.001"
                                    min="0.001"
                                    required
                                    defaultValue={truck.capacity_m3}
                                />
                                <InputError message={errors.capacity_m3} />
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    id="is_active"
                                    name="is_active"
                                    type="checkbox"
                                    value="1"
                                    defaultChecked={truck.is_active}
                                    className="size-4 rounded border border-input"
                                />
                                <Label htmlFor="is_active">
                                    Caminhão ativo
                                </Label>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="notes">Observações</Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows={3}
                                    defaultValue={truck.notes ?? ''}
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

TrucksEdit.layout = {
    breadcrumbs: [
        { title: 'Caminhões', href: index() },
        { title: 'Editar', href: index() },
    ],
};
