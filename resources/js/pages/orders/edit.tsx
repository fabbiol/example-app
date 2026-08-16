import { Form, Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import OrderController from '@/actions/App/Http/Controllers/OrderController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    formatQty,
    formatQtyInput,
    toDisplayUnit,
    unitLabel,
} from '@/lib/quantity';
import { index } from '@/routes/orders';
import type { Option, Order, Product } from '@/types';

type CustomerOption = { id: number; name: string };
type ProductOption = Pick<
    Product,
    'id' | 'name' | 'unit' | 'density' | 'stock_quantity'
>;

export default function OrdersEdit({
    order,
    customers,
    products,
    units,
    statuses,
}: {
    order: Order & { product?: ProductOption };
    customers: CustomerOption[];
    products: ProductOption[];
    units: Option[];
    statuses: Option[];
}) {
    const scheduledAt = order.scheduled_at
        ? order.scheduled_at.slice(0, 16)
        : '';
    const [productId, setProductId] = useState(String(order.product_id));
    const [inputUnit, setInputUnit] = useState<'m3' | 'ton'>(
        order.product?.unit === 'm3' ? 'm3' : 'm3',
    );
    const [quantityInput, setQuantityInput] = useState(() => {
        const product = order.product;

        if (!product) {
            return order.quantity_requested;
        }

        // Editar partindo do equivalente em m³ (estimativa operacional)
        if (product.unit === 'm3') {
            return order.quantity_requested;
        }

        const density = Number(product.density) || 1.45;

        return formatQtyInput(Number(order.quantity_requested) / density);
    });

    const selectedProduct =
        products.find((product) => String(product.id) === productId) ??
        order.product;
    const density = selectedProduct ? Number(selectedProduct.density) : 1.45;

    const preview = useMemo(() => {
        const value = Number(quantityInput);

        if (!value || !selectedProduct) {
            return null;
        }

        if (inputUnit === 'm3') {
            return {
                m3: formatQty(value),
                ton: formatQty(value * density),
                product:
                    selectedProduct.unit === 'm3'
                        ? formatQty(value)
                        : formatQty(value * density),
            };
        }

        return {
            m3: formatQty(value / density),
            ton: formatQty(value),
            product:
                selectedProduct.unit === 'ton'
                    ? formatQty(value)
                    : formatQty(value / density),
        };
    }, [quantityInput, inputUnit, selectedProduct, density]);

    return (
        <>
            <Head title={`Editar pedido #${order.id}`} />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title={`Editar pedido #${order.id}`}
                    description="Ajuste a quantidade estimada em m³ ou t"
                />

                <Form
                    {...OrderController.update.form(order.id)}
                    className="max-w-xl space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="customer_id">Cliente</Label>
                                <select
                                    id="customer_id"
                                    name="customer_id"
                                    required
                                    defaultValue={order.customer_id}
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                >
                                    {customers.map((customer) => (
                                        <option
                                            key={customer.id}
                                            value={customer.id}
                                        >
                                            {customer.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.customer_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="input_unit">
                                    Unidade estimada
                                </Label>
                                <select
                                    id="input_unit"
                                    name="input_unit"
                                    value={inputUnit}
                                    onChange={(event) =>
                                        setInputUnit(
                                            event.target.value as 'm3' | 'ton',
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
                                <InputError message={errors.input_unit} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="product_id">
                                    Produto (estoque em {unitLabel(inputUnit)})
                                </Label>
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
                                    {products.map((product) => {
                                        const displayStock = toDisplayUnit(
                                            Number(product.stock_quantity),
                                            product.unit,
                                            Number(product.density),
                                            inputUnit,
                                        );

                                        return (
                                            <option
                                                key={product.id}
                                                value={product.id}
                                            >
                                                {product.name} (
                                                {formatQty(displayStock)}{' '}
                                                {unitLabel(inputUnit)})
                                            </option>
                                        );
                                    })}
                                </select>
                                <InputError message={errors.product_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="quantity_input">
                                    Quantidade estimada ({unitLabel(inputUnit)})
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
                                        setQuantityInput(event.target.value)
                                    }
                                />
                                <InputError message={errors.quantity_input} />
                            </div>

                            {preview && selectedProduct && (
                                <p className="rounded-md border bg-muted/30 px-3 py-2 text-sm text-muted-foreground">
                                    Prévia: {preview.m3} m³ ≈ {preview.ton} t →
                                    pedido em {preview.product}{' '}
                                    {unitLabel(selectedProduct.unit)} (densidade{' '}
                                    {density.toLocaleString('pt-BR', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 3,
                                    })}
                                    )
                                </p>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <select
                                    id="status"
                                    name="status"
                                    defaultValue={order.status}
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                >
                                    {statuses.map((status) => (
                                        <option
                                            key={status.value}
                                            value={status.value}
                                        >
                                            {status.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.status} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="destination">
                                    Destino / obra
                                </Label>
                                <Input
                                    id="destination"
                                    name="destination"
                                    defaultValue={order.destination ?? ''}
                                />
                                <InputError message={errors.destination} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="vehicle_plate">Placa</Label>
                                <Input
                                    id="vehicle_plate"
                                    name="vehicle_plate"
                                    defaultValue={order.vehicle_plate ?? ''}
                                />
                                <InputError message={errors.vehicle_plate} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="scheduled_at">
                                    Agendamento
                                </Label>
                                <Input
                                    id="scheduled_at"
                                    name="scheduled_at"
                                    type="datetime-local"
                                    defaultValue={scheduledAt}
                                />
                                <InputError message={errors.scheduled_at} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="notes">Observações</Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows={3}
                                    defaultValue={order.notes ?? ''}
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

OrdersEdit.layout = {
    breadcrumbs: [
        { title: 'Pedidos', href: index() },
        { title: 'Editar', href: index() },
    ],
};
