import { Form, Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import ProductionEntryController from '@/actions/App/Http/Controllers/ProductionEntryController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatQty, unitLabel } from '@/lib/quantity';
import { edit as crushingCircuitsEdit } from '@/routes/crushing-circuits';
import { create, index } from '@/routes/production';
import { index as trucksIndex } from '@/routes/trucks';
import type { CrushingCircuit, Option, Product, Truck } from '@/types';

type ProductOption = Pick<
    Product,
    'id' | 'name' | 'code' | 'unit' | 'density' | 'bucket_capacity_m3'
>;
type TruckOption = Pick<Truck, 'id' | 'name' | 'plate' | 'capacity_m3'>;
type MethodOption = Option & { available: boolean };

export default function ProductionCreate({
    products,
    trucks,
    circuits,
    defaultCircuitId,
    methods,
    stages,
    units,
    shifts,
    defaults,
}: {
    products: ProductOption[];
    trucks: TruckOption[];
    circuits: CrushingCircuit[];
    defaultCircuitId: number | null;
    methods: MethodOption[];
    stages: Option[];
    units: Option[];
    shifts: Option[];
    defaults: { density: number; truck_capacity_m3: number };
}) {
    const today = new Date().toISOString().slice(0, 10);
    const [productId, setProductId] = useState('');
    const [method, setMethod] = useState<'trips' | 'quantity'>('trips');
    const [stage, setStage] = useState('quarry_to_primary');
    const [truckId, setTruckId] = useState('');
    const [tripsCount, setTripsCount] = useState('');
    const [truckCapacity, setTruckCapacity] = useState(
        String(defaults.truck_capacity_m3),
    );
    const [inputUnit, setInputUnit] = useState<'m3' | 'ton'>('m3');
    const [quantityInput, setQuantityInput] = useState('');
    const [applyCircuit, setApplyCircuit] = useState(true);
    const [circuitId, setCircuitId] = useState(
        defaultCircuitId ? String(defaultCircuitId) : '',
    );

    const selectedProduct = products.find(
        (product) => String(product.id) === productId,
    );
    const selectedTruck = trucks.find((truck) => String(truck.id) === truckId);
    const selectedCircuit = circuits.find(
        (circuit) => String(circuit.id) === circuitId,
    );
    const density = selectedProduct
        ? Number(selectedProduct.density)
        : defaults.density;
    const showCircuit = stage === 'quarry_to_primary';

    const preview = useMemo(() => {
        if (method === 'trips') {
            const trips = Number(tripsCount);
            const capacity = Number(truckCapacity);

            if (!trips || !capacity) {
                return null;
            }

            const m3 = trips * capacity;

            return {
                m3: formatQty(m3),
                ton: formatQty(m3 * density),
            };
        }

        const value = Number(quantityInput);

        if (!value) {
            return null;
        }

        if (inputUnit === 'm3') {
            return {
                m3: formatQty(value),
                ton: formatQty(value * density),
            };
        }

        return {
            m3: formatQty(value / density),
            ton: formatQty(value),
        };
    }, [method, tripsCount, truckCapacity, quantityInput, inputUnit, density]);

    const distributionPreview = useMemo(() => {
        if (
            !showCircuit ||
            !applyCircuit ||
            !selectedCircuit?.yields ||
            !preview
        ) {
            return [];
        }

        const feedTons = Number(preview.ton);

        return selectedCircuit.yields.map((yieldItem) => {
            const percent = Number(yieldItem.percent);
            const tons = (feedTons * percent) / 100;

            return {
                id: yieldItem.id,
                name:
                    yieldItem.product?.name ??
                    `Produto #${yieldItem.product_id}`,
                group: yieldItem.group_name,
                percent: yieldItem.percent,
                range:
                    yieldItem.percent_min && yieldItem.percent_max
                        ? `${yieldItem.percent_min}–${yieldItem.percent_max}%`
                        : null,
                tons: formatQty(tons),
            };
        });
    }, [showCircuit, applyCircuit, selectedCircuit, preview]);

    return (
        <>
            <Head title="Novo apontamento" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Novo apontamento de produção"
                        description="Alimentação do primário pode ser distribuída no circuito secundário conforme os percentuais médios"
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={crushingCircuitsEdit()}>Circuito</Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={trucksIndex()}>Caminhões</Link>
                        </Button>
                    </div>
                </div>

                <Form
                    {...ProductionEntryController.store.form()}
                    className="max-w-xl space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <input type="hidden" name="method" value={method} />
                            <input
                                type="hidden"
                                name="apply_circuit"
                                value={showCircuit && applyCircuit ? '1' : '0'}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="stage">Etapa</Label>
                                <select
                                    id="stage"
                                    name="stage"
                                    value={stage}
                                    onChange={(event) => {
                                        const nextStage = event.target.value;
                                        setStage(nextStage);

                                        if (nextStage !== 'quarry_to_primary') {
                                            setApplyCircuit(false);
                                        } else if (defaultCircuitId) {
                                            setApplyCircuit(true);
                                        }
                                    }}
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                >
                                    {stages.map((item) => (
                                        <option
                                            key={item.value}
                                            value={item.value}
                                        >
                                            {item.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.stage} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="product_id">
                                    {showCircuit && applyCircuit
                                        ? 'Alimentação (rocha detonada / rachão)'
                                        : 'Produto'}
                                </Label>
                                <select
                                    id="product_id"
                                    name="product_id"
                                    required
                                    value={productId}
                                    onChange={(event) => {
                                        const nextProductId =
                                            event.target.value;
                                        setProductId(nextProductId);
                                        const product = products.find(
                                            (item) =>
                                                String(item.id) ===
                                                nextProductId,
                                        );

                                        if (product) {
                                            setInputUnit(product.unit);

                                            if (!truckId) {
                                                setTruckCapacity(
                                                    String(
                                                        product.bucket_capacity_m3,
                                                    ),
                                                );
                                            }
                                        }
                                    }}
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                >
                                    <option value="" disabled>
                                        Selecione
                                    </option>
                                    {products.map((product) => (
                                        <option
                                            key={product.id}
                                            value={product.id}
                                        >
                                            {product.name} (
                                            {unitLabel(product.unit)})
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.product_id} />
                            </div>

                            {selectedProduct && (
                                <p className="rounded-md border bg-muted/30 px-3 py-2 text-sm text-muted-foreground">
                                    Densidade {selectedProduct.density} t/m³
                                </p>
                            )}

                            {showCircuit && (
                                <div className="space-y-3 rounded-md border p-3">
                                    <label className="flex items-start gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            className="mt-0.5 size-4 rounded border"
                                            checked={applyCircuit}
                                            onChange={(event) =>
                                                setApplyCircuit(
                                                    event.target.checked,
                                                )
                                            }
                                        />
                                        <span>
                                            Distribuir no circuito secundário
                                            (agregados)
                                            <span className="mt-1 block text-xs text-muted-foreground">
                                                A alimentação não entra no
                                                estoque; o estoque sobe nos
                                                produtos do circuito (Brita 3/4,
                                                1/2, pedrisco, pó).
                                            </span>
                                        </span>
                                    </label>
                                    <InputError
                                        message={errors.apply_circuit}
                                    />

                                    {applyCircuit && (
                                        <div className="grid gap-2">
                                            <Label htmlFor="crushing_circuit_id">
                                                Circuito
                                            </Label>
                                            <select
                                                id="crushing_circuit_id"
                                                name="crushing_circuit_id"
                                                value={circuitId}
                                                onChange={(event) =>
                                                    setCircuitId(
                                                        event.target.value,
                                                    )
                                                }
                                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                            >
                                                <option value="">
                                                    Circuito padrão
                                                </option>
                                                {circuits.map((circuit) => (
                                                    <option
                                                        key={circuit.id}
                                                        value={circuit.id}
                                                    >
                                                        {circuit.name}
                                                        {circuit.is_default
                                                            ? ' (padrão)'
                                                            : ''}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={
                                                    errors.crushing_circuit_id
                                                }
                                            />
                                        </div>
                                    )}
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label>Método</Label>
                                <div className="flex flex-wrap gap-2">
                                    {methods.map((item) => (
                                        <Button
                                            key={item.value}
                                            type="button"
                                            variant={
                                                method === item.value
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            disabled={!item.available}
                                            onClick={() => {
                                                if (item.available) {
                                                    setMethod(
                                                        item.value as
                                                            | 'trips'
                                                            | 'quantity',
                                                    );
                                                }
                                            }}
                                        >
                                            {item.label}
                                            {!item.available
                                                ? ' (em breve)'
                                                : ''}
                                        </Button>
                                    ))}
                                </div>
                                <InputError message={errors.method} />
                            </div>

                            {method === 'trips' ? (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="truck_id">
                                            Caminhão
                                        </Label>
                                        <select
                                            id="truck_id"
                                            name="truck_id"
                                            value={truckId}
                                            onChange={(event) => {
                                                const nextTruckId =
                                                    event.target.value;
                                                setTruckId(nextTruckId);
                                                const truck = trucks.find(
                                                    (item) =>
                                                        String(item.id) ===
                                                        nextTruckId,
                                                );

                                                if (truck) {
                                                    setTruckCapacity(
                                                        String(
                                                            truck.capacity_m3,
                                                        ),
                                                    );
                                                }
                                            }}
                                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                        >
                                            <option value="">
                                                Informar caçamba manualmente
                                            </option>
                                            {trucks.map((truck) => (
                                                <option
                                                    key={truck.id}
                                                    value={truck.id}
                                                >
                                                    {truck.name} · {truck.plate}{' '}
                                                    ·{' '}
                                                    {formatQty(
                                                        truck.capacity_m3,
                                                    )}{' '}
                                                    m³
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.truck_id} />
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="trips_count">
                                                Nº de viagens
                                            </Label>
                                            <Input
                                                id="trips_count"
                                                name="trips_count"
                                                type="number"
                                                min="1"
                                                required
                                                value={tripsCount}
                                                onChange={(event) =>
                                                    setTripsCount(
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={errors.trips_count}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="truck_capacity_m3">
                                                Caçamba (m³)
                                            </Label>
                                            <Input
                                                id="truck_capacity_m3"
                                                name="truck_capacity_m3"
                                                type="number"
                                                step="0.001"
                                                min="0.001"
                                                required={!truckId}
                                                value={truckCapacity}
                                                onChange={(event) =>
                                                    setTruckCapacity(
                                                        event.target.value,
                                                    )
                                                }
                                                readOnly={Boolean(
                                                    selectedTruck,
                                                )}
                                            />
                                            <InputError
                                                message={
                                                    errors.truck_capacity_m3
                                                }
                                            />
                                        </div>
                                    </div>
                                </>
                            ) : (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="input_unit">
                                            Unidade informada
                                        </Label>
                                        <select
                                            id="input_unit"
                                            name="input_unit"
                                            value={inputUnit}
                                            onChange={(event) =>
                                                setInputUnit(
                                                    event.target.value as
                                                        'm3' | 'ton',
                                                )
                                            }
                                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                        >
                                            {units.map((unit) => (
                                                <option
                                                    key={unit.value}
                                                    value={unit.value}
                                                >
                                                    {unit.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={errors.input_unit}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="quantity_input">
                                            Quantidade
                                        </Label>
                                        <Input
                                            id="quantity_input"
                                            name="quantity_input"
                                            type="number"
                                            step="0.001"
                                            min="0.001"
                                            required
                                            value={quantityInput}
                                            onChange={(event) =>
                                                setQuantityInput(
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={errors.quantity_input}
                                        />
                                    </div>
                                </div>
                            )}

                            {preview && (
                                <div className="rounded-md border bg-muted/30 px-3 py-3 text-sm">
                                    <p className="font-medium">
                                        Prévia da alimentação
                                    </p>
                                    <p className="mt-1 text-muted-foreground">
                                        {preview.m3} m³ ≈ {preview.ton} t
                                        (densidade {density})
                                    </p>
                                </div>
                            )}

                            {distributionPreview.length > 0 && (
                                <div className="rounded-md border px-3 py-3 text-sm">
                                    <p className="font-medium">
                                        Prévia do circuito
                                    </p>
                                    <ul className="mt-2 space-y-1 text-muted-foreground">
                                        {distributionPreview.map((item) => (
                                            <li
                                                key={item.id}
                                                className="flex flex-wrap justify-between gap-2"
                                            >
                                                <span>
                                                    {item.name}
                                                    {item.group
                                                        ? ` · ${item.group}`
                                                        : ''}{' '}
                                                    ({item.percent}%
                                                    {item.range
                                                        ? ` · faixa ${item.range}`
                                                        : ''}
                                                    )
                                                </span>
                                                <span className="font-medium text-foreground">
                                                    {item.tons} t
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="shift">Turno</Label>
                                <select
                                    id="shift"
                                    name="shift"
                                    defaultValue="morning"
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                >
                                    {shifts.map((shift) => (
                                        <option
                                            key={shift.value}
                                            value={shift.value}
                                        >
                                            {shift.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.shift} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="produced_on">Data</Label>
                                <Input
                                    id="produced_on"
                                    name="produced_on"
                                    type="date"
                                    required
                                    defaultValue={today}
                                />
                                <InputError message={errors.produced_on} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="notes">Observações</Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows={3}
                                    placeholder="Ex.: 12 viagens do basculante 02 para o britador primário"
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

ProductionCreate.layout = {
    breadcrumbs: [
        { title: 'Produção', href: index() },
        { title: 'Novo', href: create() },
    ],
};
