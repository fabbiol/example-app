import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import WeighTicketController from '@/actions/App/Http/Controllers/WeighTicketController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatQtyWithUnit } from '@/lib/quantity';
import { create, index } from '@/routes/weigh-tickets';
import type { Order, Product } from '@/types';

type CustomerOption = { id: number; name: string };
type ProductOption = Pick<Product, 'id' | 'name' | 'unit' | 'stock_quantity'>;
type OpenOrder = Pick<
    Order,
    | 'id'
    | 'customer_id'
    | 'product_id'
    | 'quantity_requested'
    | 'quantity_loaded'
    | 'vehicle_plate'
    | 'status'
> & {
    customer?: CustomerOption;
    product?: Pick<Product, 'id' | 'name'>;
};

export default function WeighTicketsCreate({
    customers,
    products,
    orders,
}: {
    customers: CustomerOption[];
    products: ProductOption[];
    orders: OpenOrder[];
}) {
    const [orderId, setOrderId] = useState('');
    const selectedOrder = orders.find((order) => String(order.id) === orderId);

    return (
        <>
            <Head title="Nova pesagem" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Nova pesagem"
                    description="Baixa estoque automaticamente; use o ticket para faturar no MarketUp"
                />

                <Form
                    {...WeighTicketController.store.form()}
                    className="max-w-xl space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="order_id">
                                    Pedido (opcional)
                                </Label>
                                <select
                                    id="order_id"
                                    name="order_id"
                                    value={orderId}
                                    onChange={(event) =>
                                        setOrderId(event.target.value)
                                    }
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                >
                                    <option value="">Pesagem avulsa</option>
                                    {orders.map((order) => (
                                        <option key={order.id} value={order.id}>
                                            #{order.id} · {order.customer?.name}{' '}
                                            · {order.product?.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.order_id} />
                            </div>

                            {!selectedOrder && (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="customer_id">
                                            Cliente
                                        </Label>
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
                                                <option
                                                    key={customer.id}
                                                    value={customer.id}
                                                >
                                                    {customer.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={errors.customer_id}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="product_id">
                                            Produto
                                        </Label>
                                        <select
                                            id="product_id"
                                            name="product_id"
                                            required
                                            defaultValue=""
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
                                                    {formatQtyWithUnit(
                                                        product.stock_quantity,
                                                        product.unit,
                                                    )}
                                                    )
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={errors.product_id}
                                        />
                                    </div>
                                </>
                            )}

                            {selectedOrder && (
                                <p className="rounded-md border bg-muted/30 px-3 py-2 text-sm text-muted-foreground">
                                    Cliente e produto serão herdados do pedido #
                                    {selectedOrder.id}.
                                </p>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="vehicle_plate">Placa</Label>
                                <Input
                                    id="vehicle_plate"
                                    name="vehicle_plate"
                                    required
                                    defaultValue={
                                        selectedOrder?.vehicle_plate ?? ''
                                    }
                                />
                                <InputError message={errors.vehicle_plate} />
                            </div>

                            <div className="grid gap-2 sm:grid-cols-2 sm:gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="tare_weight">Tara</Label>
                                    <Input
                                        id="tare_weight"
                                        name="tare_weight"
                                        type="number"
                                        step="0.001"
                                        min="0"
                                        required
                                    />
                                    <InputError message={errors.tare_weight} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="gross_weight">
                                        Peso bruto
                                    </Label>
                                    <Input
                                        id="gross_weight"
                                        name="gross_weight"
                                        type="number"
                                        step="0.001"
                                        min="0"
                                        required
                                    />
                                    <InputError message={errors.gross_weight} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="weighed_at">Data/hora</Label>
                                <Input
                                    id="weighed_at"
                                    name="weighed_at"
                                    type="datetime-local"
                                />
                                <InputError message={errors.weighed_at} />
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
                                    Registrar
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

WeighTicketsCreate.layout = {
    breadcrumbs: [
        { title: 'Balança', href: index() },
        { title: 'Nova', href: create() },
    ],
};
