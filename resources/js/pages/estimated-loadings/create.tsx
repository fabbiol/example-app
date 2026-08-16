import { Form, Head, Link } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import EstimatedLoadingController from '@/actions/App/Http/Controllers/EstimatedLoadingController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatQty, formatQtyInput, formatQtyWithUnit } from '@/lib/quantity';
import { create, index } from '@/routes/estimated-loadings';
import type { Option, Order, Product } from '@/types';

type CustomerOption = { id: number; name: string };
type ProductOption = Pick<
    Product,
    'id' | 'name' | 'unit' | 'stock_quantity' | 'density' | 'bucket_capacity_m3'
>;
type OpenOrder = Pick<
    Order,
    | 'id'
    | 'customer_id'
    | 'product_id'
    | 'quantity_requested'
    | 'quantity_loaded'
    | 'vehicle_plate'
    | 'status'
    | 'destination'
> & {
    customer?: CustomerOption;
    product?: ProductOption;
};

const statusLabel = {
    open: 'Aberto',
    scheduled: 'Agendado',
    loading: 'Em carregamento',
    completed: 'Concluído',
    cancelled: 'Cancelado',
} as const;

function suggestedBuckets(
    remainingInProductUnit: number,
    productUnit: 'ton' | 'm3',
    density: number,
    bucketCapacityM3: number,
): number {
    if (remainingInProductUnit <= 0 || bucketCapacityM3 <= 0 || density <= 0) {
        return 0;
    }

    const remainingM3 =
        productUnit === 'm3'
            ? remainingInProductUnit
            : remainingInProductUnit / density;

    return Math.max(1, Math.ceil(remainingM3 / bucketCapacityM3 - 1e-9));
}

export default function EstimatedLoadingsCreate({
    customers,
    products,
    orders,
    units,
    defaults,
}: {
    customers: CustomerOption[];
    products: ProductOption[];
    orders: OpenOrder[];
    units: Option[];
    defaults: { density: number; bucket_capacity_m3: number };
}) {
    const [orderId, setOrderId] = useState('');
    const [productId, setProductId] = useState('');
    const [mode, setMode] = useState<'quantity' | 'buckets'>('buckets');
    const [inputUnit, setInputUnit] = useState<'m3' | 'ton'>('m3');
    const [quantityInput, setQuantityInput] = useState('');
    const [bucketsCount, setBucketsCount] = useState('');
    const [bucketCapacity, setBucketCapacity] = useState(
        String(defaults.bucket_capacity_m3),
    );
    const [vehiclePlate, setVehiclePlate] = useState('');

    const selectedOrder = orders.find((order) => String(order.id) === orderId);
    const selectedProduct =
        selectedOrder?.product ??
        products.find((product) => String(product.id) === productId);

    const density = selectedProduct
        ? Number(selectedProduct.density)
        : defaults.density;

    const orderSummary = useMemo(() => {
        if (!selectedOrder || !selectedOrder.product) {
            return null;
        }

        const product = selectedOrder.product;
        const requested = Number(selectedOrder.quantity_requested);
        const loaded = Number(selectedOrder.quantity_loaded);
        const remaining = Math.max(0, requested - loaded);
        const capacity = Number(product.bucket_capacity_m3);
        const productDensity = Number(product.density);
        const buckets = suggestedBuckets(
            remaining,
            product.unit,
            productDensity,
            capacity,
        );

        const remainingM3 =
            product.unit === 'm3' ? remaining : remaining / productDensity;
        const remainingTon =
            product.unit === 'ton' ? remaining : remaining * productDensity;

        return {
            requested,
            loaded,
            remaining,
            remainingM3,
            remainingTon,
            buckets,
            capacity,
            unit: product.unit,
            stock: Number(product.stock_quantity),
        };
    }, [selectedOrder]);

    useEffect(() => {
        if (!selectedOrder || !selectedOrder.product || !orderSummary) {
            return;
        }

        const product = selectedOrder.product;

        setBucketCapacity(String(product.bucket_capacity_m3));
        setInputUnit(product.unit);
        setVehiclePlate(selectedOrder.vehicle_plate ?? '');
        setMode('buckets');
        setBucketsCount(
            orderSummary.buckets > 0 ? String(orderSummary.buckets) : '',
        );
        setQuantityInput(
            orderSummary.remaining > 0 ? formatQtyInput(orderSummary.remaining) : '',
        );
    }, [selectedOrder?.id]);

    useEffect(() => {
        if (selectedOrder || !selectedProduct) {
            return;
        }

        setBucketCapacity(String(selectedProduct.bucket_capacity_m3));
        setInputUnit(selectedProduct.unit);
    }, [selectedProduct?.id, selectedOrder]);

    const preview = useMemo(() => {
        if (mode === 'buckets') {
            const buckets = Number(bucketsCount);
            const capacity = Number(bucketCapacity);

            if (!buckets || !capacity) {
                return null;
            }

            const m3 = buckets * capacity;

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
    }, [mode, bucketsCount, bucketCapacity, quantityInput, inputUnit, density]);

    return (
        <>
            <Head title="Novo carregamento" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Novo carregamento"
                    description="Ao escolher o pedido, a quantidade de conchas e os dados restantes são preenchidos automaticamente"
                />

                <Form
                    {...EstimatedLoadingController.store.form()}
                    className="max-w-xl space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <input type="hidden" name="mode" value={mode} />

                            <div className="grid gap-2">
                                <Label htmlFor="order_id">Pedido (opcional)</Label>
                                <select
                                    id="order_id"
                                    name="order_id"
                                    value={orderId}
                                    onChange={(event) => {
                                        const nextOrderId = event.target.value;
                                        setOrderId(nextOrderId);
                                        setProductId('');

                                        if (!nextOrderId) {
                                            setBucketsCount('');
                                            setQuantityInput('');
                                            setVehiclePlate('');
                                            setBucketCapacity(
                                                String(defaults.bucket_capacity_m3),
                                            );
                                        }
                                    }}
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                >
                                    <option value="">Expedição avulsa</option>
                                    {orders.map((order) => {
                                        const remaining = Math.max(
                                            0,
                                            Number(order.quantity_requested) -
                                                Number(order.quantity_loaded),
                                        );

                                        return (
                                            <option key={order.id} value={order.id}>
                                                #{order.id} · {order.customer?.name} ·{' '}
                                                {order.product?.name} · resta{' '}
                                                {order.product
                                                    ? formatQtyWithUnit(remaining, order.product.unit)
                                                    : formatQty(remaining)}
                                            </option>
                                        );
                                    })}
                                </select>
                                <InputError message={errors.order_id} />
                            </div>

                            {selectedOrder && orderSummary && (
                                <div className="space-y-2 rounded-md border bg-muted/30 px-3 py-3 text-sm">
                                    <p className="font-medium">
                                        Pedido #{selectedOrder.id} ·{' '}
                                        {statusLabel[selectedOrder.status]}
                                    </p>
                                    <dl className="grid gap-1 text-muted-foreground sm:grid-cols-2">
                                        <div>
                                            <dt className="text-xs uppercase tracking-wide">
                                                Cliente
                                            </dt>
                                            <dd className="text-foreground">
                                                {selectedOrder.customer?.name}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs uppercase tracking-wide">
                                                Produto
                                            </dt>
                                            <dd className="text-foreground">
                                                {selectedOrder.product?.name}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs uppercase tracking-wide">
                                                Solicitado
                                            </dt>
                                            <dd className="text-foreground">
                                                {formatQtyWithUnit(
                                                    orderSummary.requested,
                                                    orderSummary.unit,
                                                )}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs uppercase tracking-wide">
                                                Já carregado
                                            </dt>
                                            <dd className="text-foreground">
                                                {formatQtyWithUnit(
                                                    orderSummary.loaded,
                                                    orderSummary.unit,
                                                )}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs uppercase tracking-wide">
                                                Restante
                                            </dt>
                                            <dd className="font-medium text-foreground">
                                                {formatQtyWithUnit(
                                                    orderSummary.remaining,
                                                    orderSummary.unit,
                                                )}
                                                <span className="ml-1 font-normal text-muted-foreground">
                                                    ({formatQty(orderSummary.remainingM3)} m³ ≈{' '}
                                                    {formatQty(orderSummary.remainingTon)} t)
                                                </span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs uppercase tracking-wide">
                                                Conchas sugeridas
                                            </dt>
                                            <dd className="font-medium text-foreground">
                                                {orderSummary.buckets} ×{' '}
                                                {formatQty(orderSummary.capacity)} m³
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs uppercase tracking-wide">
                                                Estoque atual
                                            </dt>
                                            <dd className="text-foreground">
                                                {formatQtyWithUnit(
                                                    orderSummary.stock,
                                                    orderSummary.unit,
                                                )}
                                            </dd>
                                        </div>
                                        {selectedOrder.destination && (
                                            <div>
                                                <dt className="text-xs uppercase tracking-wide">
                                                    Destino
                                                </dt>
                                                <dd className="text-foreground">
                                                    {selectedOrder.destination}
                                                </dd>
                                            </div>
                                        )}
                                    </dl>
                                </div>
                            )}

                            {!selectedOrder && (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="customer_id">Cliente</Label>
                                        <select
                                            id="customer_id"
                                            name="customer_id"
                                            required
                                            defaultValue=""
                                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                        >
                                            <option value="" disabled>
                                                Selecione
                                            </option>
                                            {customers.map((customer) => (
                                                <option key={customer.id} value={customer.id}>
                                                    {customer.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.customer_id} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="product_id">Produto</Label>
                                        <select
                                            id="product_id"
                                            name="product_id"
                                            required
                                            value={productId}
                                            onChange={(event) =>
                                                setProductId(event.target.value)
                                            }
                                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                        >
                                            <option value="" disabled>
                                                Selecione
                                            </option>
                                            {products.map((product) => (
                                                <option key={product.id} value={product.id}>
                                                    {product.name} (
                                                    {formatQtyWithUnit(
                                                        product.stock_quantity,
                                                        product.unit,
                                                    )}
                                                    )
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.product_id} />
                                    </div>
                                </>
                            )}

                            {selectedProduct && !selectedOrder && (
                                <p className="rounded-md border bg-muted/30 px-3 py-2 text-sm text-muted-foreground">
                                    {selectedProduct.name}: densidade {selectedProduct.density}{' '}
                                    t/m³ · concha padrão {selectedProduct.bucket_capacity_m3} m³
                                </p>
                            )}

                            <div className="grid gap-2">
                                <Label>Como estimar?</Label>
                                <div className="flex gap-2">
                                    <Button
                                        type="button"
                                        variant={mode === 'buckets' ? 'default' : 'outline'}
                                        onClick={() => setMode('buckets')}
                                    >
                                        Por conchas
                                    </Button>
                                    <Button
                                        type="button"
                                        variant={mode === 'quantity' ? 'default' : 'outline'}
                                        onClick={() => setMode('quantity')}
                                    >
                                        Por quantidade
                                    </Button>
                                </div>
                            </div>

                            {mode === 'buckets' ? (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="buckets_count">Nº de conchas</Label>
                                        <Input
                                            id="buckets_count"
                                            name="buckets_count"
                                            type="number"
                                            min="1"
                                            required
                                            value={bucketsCount}
                                            onChange={(event) =>
                                                setBucketsCount(event.target.value)
                                            }
                                        />
                                        <InputError message={errors.buckets_count} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="bucket_capacity_m3">
                                            Capacidade da concha (m³)
                                        </Label>
                                        <Input
                                            id="bucket_capacity_m3"
                                            name="bucket_capacity_m3"
                                            type="number"
                                            step="0.001"
                                            min="0.001"
                                            required
                                            value={bucketCapacity}
                                            onChange={(event) =>
                                                setBucketCapacity(event.target.value)
                                            }
                                        />
                                        <InputError message={errors.bucket_capacity_m3} />
                                    </div>
                                </div>
                            ) : (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="input_unit">Unidade informada</Label>
                                        <select
                                            id="input_unit"
                                            name="input_unit"
                                            value={inputUnit}
                                            onChange={(event) =>
                                                setInputUnit(event.target.value as 'm3' | 'ton')
                                            }
                                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                        >
                                            {units.map((unit) => (
                                                <option key={unit.value} value={unit.value}>
                                                    {unit.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.input_unit} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="quantity_input">Quantidade</Label>
                                        <Input
                                            id="quantity_input"
                                            name="quantity_input"
                                            type="number"
                                            step="0.001"
                                            min="0.001"
                                            required
                                            value={quantityInput}
                                            onChange={(event) =>
                                                setQuantityInput(event.target.value)
                                            }
                                        />
                                        <InputError message={errors.quantity_input} />
                                    </div>
                                </div>
                            )}

                            {preview && (
                                <div className="rounded-md border bg-muted/30 px-3 py-3 text-sm">
                                    <p className="font-medium">Prévia da conversão</p>
                                    <p className="mt-1 text-muted-foreground">
                                        {preview.m3} m³ ≈ {preview.ton} t (densidade {density})
                                    </p>
                                    <p className="mt-1 text-muted-foreground">
                                        O estoque e o pedido serão baixados na unidade do produto.
                                    </p>
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="vehicle_plate">Placa</Label>
                                <Input
                                    id="vehicle_plate"
                                    name="vehicle_plate"
                                    required
                                    value={vehiclePlate}
                                    onChange={(event) => setVehiclePlate(event.target.value)}
                                />
                                <InputError message={errors.vehicle_plate} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="loaded_at">Data/hora</Label>
                                <Input id="loaded_at" name="loaded_at" type="datetime-local" />
                                <InputError message={errors.loaded_at} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="notes">Observações</Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows={3}
                                    placeholder="Ex.: operador João, pilha norte, 8 conchas cheias"
                                    className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                                />
                                <InputError message={errors.notes} />
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Registrar carregamento
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

EstimatedLoadingsCreate.layout = {
    breadcrumbs: [
        { title: 'Carregamento', href: index() },
        { title: 'Novo', href: create() },
    ],
};
