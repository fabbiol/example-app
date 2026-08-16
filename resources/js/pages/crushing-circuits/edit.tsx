import { Form, Head, Link } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import CrushingCircuitController from '@/actions/App/Http/Controllers/CrushingCircuitController';
import FlashMessage from '@/components/flash-message';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatQty } from '@/lib/quantity';
import { edit } from '@/routes/crushing-circuits';
import { index as productionIndex } from '@/routes/production';
import type { CrushingCircuit, CrushingCircuitYield, Product } from '@/types';

type YieldDraft = {
    key: string;
    id?: number;
    product_id: string;
    group_name: string;
    percent: string;
    percent_min: string;
    percent_max: string;
    sort_order: string;
};

function toDraft(yieldItem: CrushingCircuitYield, index: number): YieldDraft {
    return {
        key: `existing-${yieldItem.id}`,
        id: yieldItem.id,
        product_id: String(yieldItem.product_id),
        group_name: yieldItem.group_name ?? '',
        percent: yieldItem.percent,
        percent_min: yieldItem.percent_min ?? '',
        percent_max: yieldItem.percent_max ?? '',
        sort_order: String(yieldItem.sort_order ?? index + 1),
    };
}

export default function CrushingCircuitsEdit({
    circuit,
    products,
}: {
    circuit: CrushingCircuit;
    products: Pick<Product, 'id' | 'name' | 'code'>[];
}) {
    const [name, setName] = useState(circuit.name);
    const [notes, setNotes] = useState(circuit.notes ?? '');
    const [isActive, setIsActive] = useState(circuit.is_active);
    const [yields, setYields] = useState<YieldDraft[]>(
        (circuit.yields ?? []).map(toDraft),
    );

    const totalPercent = useMemo(
        () => yields.reduce((sum, item) => sum + (Number(item.percent) || 0), 0),
        [yields],
    );

    const updateYield = (key: string, patch: Partial<YieldDraft>) => {
        setYields((current) =>
            current.map((item) => (item.key === key ? { ...item, ...patch } : item)),
        );
    };

    const addYield = () => {
        setYields((current) => [
            ...current,
            {
                key: `new-${Date.now()}`,
                product_id: '',
                group_name: '',
                percent: '',
                percent_min: '',
                percent_max: '',
                sort_order: String(current.length + 1),
            },
        ]);
    };

    const removeYield = (key: string) => {
        setYields((current) => current.filter((item) => item.key !== key));
    };

    return (
        <>
            <Head title="Circuito de britagem" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Circuito secundário"
                        description="Percentuais médios da rocha detonada convertida em agregados (Brita 3/4, 1/2, pedrisco e pó)"
                    />
                    <Button variant="outline" asChild>
                        <Link href={productionIndex()}>Produção</Link>
                    </Button>
                </div>

                <FlashMessage />

                <Form
                    {...CrushingCircuitController.update.form(circuit.id)}
                    className="max-w-4xl space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <input type="hidden" name="is_active" value={isActive ? '1' : '0'} />

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Nome do circuito</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        value={name}
                                        onChange={(event) => setName(event.target.value)}
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="flex items-end gap-2 pb-1">
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={isActive}
                                            onChange={(event) =>
                                                setIsActive(event.target.checked)
                                            }
                                            className="size-4 rounded border"
                                        />
                                        Circuito ativo
                                    </label>
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="notes">Observações</Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows={2}
                                    value={notes}
                                    onChange={(event) => setNotes(event.target.value)}
                                    className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                                />
                                <InputError message={errors.notes} />
                            </div>

                            <div className="space-y-3">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <h2 className="text-sm font-medium">
                                            Distribuição proporcional
                                        </h2>
                                        <p className="text-xs text-muted-foreground">
                                            Soma atual:{' '}
                                            <span
                                                className={
                                                    Math.abs(totalPercent - 100) > 0.05
                                                        ? 'text-destructive'
                                                        : 'text-foreground'
                                                }
                                            >
                                                {formatQty(totalPercent)}%
                                            </span>{' '}
                                            (meta 100%)
                                        </p>
                                    </div>
                                    <Button type="button" variant="outline" size="sm" onClick={addYield}>
                                        <Plus />
                                        Produto
                                    </Button>
                                </div>

                                <InputError message={errors.yields} />

                                <div className="space-y-3">
                                    {yields.map((item, index) => (
                                        <div
                                            key={item.key}
                                            className="grid gap-3 rounded-lg border p-3 sm:grid-cols-12"
                                        >
                                            {item.id !== undefined && (
                                                <input
                                                    type="hidden"
                                                    name={`yields[${index}][id]`}
                                                    value={item.id}
                                                />
                                            )}
                                            <input
                                                type="hidden"
                                                name={`yields[${index}][sort_order]`}
                                                value={item.sort_order || String(index + 1)}
                                            />

                                            <div className="grid gap-2 sm:col-span-4">
                                                <Label>Produto</Label>
                                                <select
                                                    name={`yields[${index}][product_id]`}
                                                    required
                                                    value={item.product_id}
                                                    onChange={(event) =>
                                                        updateYield(item.key, {
                                                            product_id: event.target.value,
                                                        })
                                                    }
                                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                                >
                                                    <option value="" disabled>
                                                        Selecione
                                                    </option>
                                                    {products.map((product) => (
                                                        <option key={product.id} value={product.id}>
                                                            {product.name} ({product.code})
                                                        </option>
                                                    ))}
                                                </select>
                                                <InputError
                                                    message={
                                                        errors[`yields.${index}.product_id`]
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2 sm:col-span-3">
                                                <Label>Grupo</Label>
                                                <Input
                                                    name={`yields[${index}][group_name]`}
                                                    value={item.group_name}
                                                    onChange={(event) =>
                                                        updateYield(item.key, {
                                                            group_name: event.target.value,
                                                        })
                                                    }
                                                    placeholder="Ex.: Brita 1 e 2"
                                                />
                                            </div>

                                            <div className="grid gap-2 sm:col-span-2">
                                                <Label>% médio</Label>
                                                <Input
                                                    name={`yields[${index}][percent]`}
                                                    type="number"
                                                    step="0.001"
                                                    min="0.001"
                                                    max="100"
                                                    required
                                                    value={item.percent}
                                                    onChange={(event) =>
                                                        updateYield(item.key, {
                                                            percent: event.target.value,
                                                        })
                                                    }
                                                />
                                                <InputError
                                                    message={errors[`yields.${index}.percent`]}
                                                />
                                            </div>

                                            <div className="grid gap-2 sm:col-span-1">
                                                <Label>Mín</Label>
                                                <Input
                                                    name={`yields[${index}][percent_min]`}
                                                    type="number"
                                                    step="0.001"
                                                    min="0"
                                                    max="100"
                                                    value={item.percent_min}
                                                    onChange={(event) =>
                                                        updateYield(item.key, {
                                                            percent_min: event.target.value,
                                                        })
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2 sm:col-span-1">
                                                <Label>Máx</Label>
                                                <Input
                                                    name={`yields[${index}][percent_max]`}
                                                    type="number"
                                                    step="0.001"
                                                    min="0"
                                                    max="100"
                                                    value={item.percent_max}
                                                    onChange={(event) =>
                                                        updateYield(item.key, {
                                                            percent_max: event.target.value,
                                                        })
                                                    }
                                                />
                                            </div>

                                            <div className="flex items-end sm:col-span-1">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => removeYield(item.key)}
                                                    aria-label="Remover produto"
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    ))}

                                    {yields.length === 0 && (
                                        <p className="rounded-md border border-dashed px-3 py-6 text-center text-sm text-muted-foreground">
                                            Adicione os produtos do circuito e os percentuais médios.
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing || yields.length === 0}>
                                    Salvar circuito
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CrushingCircuitsEdit.layout = {
    breadcrumbs: [
        { title: 'Produção', href: productionIndex() },
        { title: 'Circuito', href: edit() },
    ],
};
