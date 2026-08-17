import { Form, Head, Link } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import EstimatedLoadingController from '@/actions/App/Http/Controllers/EstimatedLoadingController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatQty, formatQtyInput, formatQtyWithUnit } from '@/lib/quantity';
import { create, index } from '@/routes/estimated-loadings';
import type { CaixaEntry, Option, Order, Product } from '@/types';

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
type ProductLine = {
    key: string;
    product_id: string;
    input_unit: 'm3' | 'ton';
    quantity_input: string;
};

const statusLabel = {
    open: 'Aberto',
    scheduled: 'Agendado',
    loading: 'Em carregamento',
    completed: 'Concluído',
    cancelled: 'Cancelado',
} as const;

function formatMoney(value: string): string {
    return Number(value).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });
}

function formatCaixaDate(value: string): string {
    return new Date(`${value}T12:00:00`).toLocaleDateString('pt-BR');
}

function caixaEntryLabel(entry: CaixaEntry): string {
    return `#${entry.descricao} · ${formatCaixaDate(entry.data)} · ${entry.tipo_label} · ${formatMoney(entry.valor)}`;
}

function normalizePedidoQuery(value: string): string {
    return value.trim().replace(/^#/, '');
}

function caixaEntryMatches(entry: CaixaEntry, query: string): boolean {
    const normalized = normalizePedidoQuery(query).toLowerCase();

    if (normalized === '') {
        return true;
    }

    return [
        entry.descricao,
        `#${entry.descricao}`,
        String(entry.id),
        entry.tipo,
        entry.tipo_label,
        entry.valor,
        formatMoney(entry.valor),
        formatCaixaDate(entry.data),
        entry.metodo_pagamento ?? '',
    ]
        .join(' ')
        .toLowerCase()
        .includes(normalized);
}

function nextLineKey(): string {
    return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

function emptyLine(): ProductLine {
    return {
        key: nextLineKey(),
        product_id: '',
        input_unit: 'm3',
        quantity_input: '',
    };
}

function linePreview(
    line: ProductLine,
    products: ProductOption[],
): { m3: string; ton: string } | null {
    const product = products.find(
        (item) => String(item.id) === line.product_id,
    );
    const quantity = Number(line.quantity_input);
    const density = Number(product?.density ?? 0);

    if (!product || !quantity || density <= 0) {
        return null;
    }

    if (line.input_unit === 'm3') {
        return {
            m3: formatQty(quantity),
            ton: formatQty(quantity * density),
        };
    }

    return {
        m3: formatQty(quantity / density),
        ton: formatQty(quantity),
    };
}

export default function EstimatedLoadingsCreate({
    customers,
    products,
    caixa_entries: caixaEntries,
    caixa_error: caixaError,
    orders,
    units,
}: {
    customers: CustomerOption[];
    products: ProductOption[];
    caixa_entries: CaixaEntry[];
    caixa_error: string | null;
    orders: OpenOrder[];
    units: Option[];
    defaults: { density: number; bucket_capacity_m3: number };
}) {
    const [caixaId, setCaixaId] = useState('');
    const [caixaQuery, setCaixaQuery] = useState('');
    const [orderId, setOrderId] = useState('');
    const [lines, setLines] = useState<ProductLine[]>([emptyLine()]);
    const [vehiclePlate, setVehiclePlate] = useState('');

    const selectedCaixa = caixaEntries.find(
        (entry) => String(entry.id) === caixaId,
    );
    const filteredCaixaEntries = useMemo(() => {
        return caixaEntries.filter((entry) =>
            caixaEntryMatches(entry, caixaQuery),
        );
    }, [caixaEntries, caixaQuery]);
    const selectedOrder = orders.find((order) => String(order.id) === orderId);

    const orderSummary = useMemo(() => {
        if (!selectedOrder || !selectedOrder.product) {
            return null;
        }

        const product = selectedOrder.product;
        const requested = Number(selectedOrder.quantity_requested);
        const loaded = Number(selectedOrder.quantity_loaded);
        const remaining = Math.max(0, requested - loaded);
        const productDensity = Number(product.density);

        return {
            requested,
            loaded,
            remaining,
            remainingM3:
                product.unit === 'm3' ? remaining : remaining / productDensity,
            remainingTon:
                product.unit === 'ton' ? remaining : remaining * productDensity,
            unit: product.unit,
            stock: Number(product.stock_quantity),
        };
    }, [selectedOrder]);

    const updateLine = (key: string, patch: Partial<ProductLine>) => {
        setLines((current) =>
            current.map((line) =>
                line.key === key ? { ...line, ...patch } : line,
            ),
        );
    };

    return (
        <>
            <Head title="Novo carregamento" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Novo carregamento"
                    description="O número do pedido vem do caixa e só pode ser usado uma vez. Informe um ou mais produtos apenas com a quantidade."
                />

                <Form
                    {...EstimatedLoadingController.store.form()}
                    className="max-w-2xl space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="caixa_query">
                                    Pedido do caixa (número)
                                </Label>
                                <input
                                    type="hidden"
                                    name="caixa_id"
                                    value={caixaId}
                                />
                                <Input
                                    id="caixa_query"
                                    value={caixaQuery}
                                    onChange={(event) => {
                                        const value = event.target.value;
                                        setCaixaQuery(value);
                                        const typed =
                                            normalizePedidoQuery(value);
                                        const exact = caixaEntries.find(
                                            (entry) =>
                                                entry.descricao.trim() ===
                                                typed,
                                        );
                                        setCaixaId(
                                            exact ? String(exact.id) : '',
                                        );
                                    }}
                                    placeholder="Digite o número do pedido no MarketUp"
                                    autoComplete="off"
                                />
                                <div
                                    className="max-h-64 overflow-y-auto rounded-md border border-input bg-background"
                                    role="listbox"
                                    aria-label="Pedidos do caixa"
                                >
                                    <button
                                        type="button"
                                        role="option"
                                        aria-selected={caixaId === ''}
                                        onClick={() => {
                                            setCaixaId('');
                                            setCaixaQuery('');
                                        }}
                                        className={`flex w-full px-3 py-2.5 text-left text-sm ${
                                            caixaId === ''
                                                ? 'bg-muted'
                                                : 'hover:bg-muted/60'
                                        }`}
                                    >
                                        Carregamento avulso (sem pedido)
                                    </button>
                                    {filteredCaixaEntries.map((entry) => {
                                        const selected =
                                            String(entry.id) === caixaId;

                                        return (
                                            <button
                                                key={entry.id}
                                                type="button"
                                                role="option"
                                                aria-selected={selected}
                                                onClick={() => {
                                                    setCaixaId(
                                                        String(entry.id),
                                                    );
                                                    setCaixaQuery(
                                                        entry.descricao,
                                                    );
                                                }}
                                                className={`flex w-full border-t border-input px-3 py-2.5 text-left text-sm ${
                                                    selected
                                                        ? 'bg-muted'
                                                        : 'hover:bg-muted/60'
                                                }`}
                                            >
                                                {caixaEntryLabel(entry)}
                                            </button>
                                        );
                                    })}
                                    {caixaEntries.length > 0 &&
                                        filteredCaixaEntries.length === 0 && (
                                            <p className="border-t border-input px-3 py-2.5 text-sm text-muted-foreground">
                                                Nenhum pedido encontrado para
                                                &ldquo;{caixaQuery}&rdquo;.
                                            </p>
                                        )}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {caixaError
                                        ? caixaError
                                        : caixaEntries.length === 0
                                          ? 'Nenhum número disponível no caixa. Siga com o carregamento avulso.'
                                          : 'Só entram lançamentos com tipo diferente de saída. Cada número some depois de usado. Lista do mais novo para o mais antigo; digite para filtrar.'}
                                </p>
                                <InputError message={errors.caixa_id} />
                            </div>

                            {selectedCaixa && (
                                <div className="space-y-2 rounded-md border bg-muted/30 px-3 py-3 text-sm">
                                    <p className="font-medium">
                                        Pedido #{selectedCaixa.descricao} ·{' '}
                                        {selectedCaixa.tipo_label}
                                    </p>
                                    <p className="text-muted-foreground">
                                        {formatCaixaDate(selectedCaixa.data)}
                                        {selectedCaixa.metodo_pagamento
                                            ? ` · ${selectedCaixa.metodo_pagamento}`
                                            : ''}
                                    </p>
                                    <p className="font-medium">
                                        {formatMoney(selectedCaixa.valor)}
                                    </p>
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="order_id">
                                    Pedido interno da pá (opcional)
                                </Label>
                                <select
                                    id="order_id"
                                    name="order_id"
                                    value={orderId}
                                    onChange={(event) => {
                                        const nextOrderId = event.target.value;
                                        setOrderId(nextOrderId);

                                        if (!nextOrderId) {
                                            setVehiclePlate('');

                                            return;
                                        }

                                        const order = orders.find(
                                            (item) =>
                                                String(item.id) === nextOrderId,
                                        );
                                        const product = order?.product;

                                        if (!order || !product) {
                                            return;
                                        }

                                        const remaining = Math.max(
                                            0,
                                            Number(order.quantity_requested) -
                                                Number(order.quantity_loaded),
                                        );

                                        setVehiclePlate(
                                            order.vehicle_plate ?? '',
                                        );
                                        setLines([
                                            {
                                                key: nextLineKey(),
                                                product_id: String(product.id),
                                                input_unit: product.unit,
                                                quantity_input:
                                                    remaining > 0
                                                        ? formatQtyInput(
                                                              remaining,
                                                          )
                                                        : '',
                                            },
                                        ]);
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
                                            <option
                                                key={order.id}
                                                value={order.id}
                                            >
                                                #{order.id} ·{' '}
                                                {order.customer?.name} ·{' '}
                                                {order.product?.name} · resta{' '}
                                                {order.product
                                                    ? formatQtyWithUnit(
                                                          remaining,
                                                          order.product.unit,
                                                      )
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
                                            <dt className="text-xs tracking-wide uppercase">
                                                Cliente
                                            </dt>
                                            <dd className="text-foreground">
                                                {selectedOrder.customer?.name}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs tracking-wide uppercase">
                                                Produto do pedido
                                            </dt>
                                            <dd className="text-foreground">
                                                {selectedOrder.product?.name}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs tracking-wide uppercase">
                                                Restante
                                            </dt>
                                            <dd className="font-medium text-foreground">
                                                {formatQtyWithUnit(
                                                    orderSummary.remaining,
                                                    orderSummary.unit,
                                                )}
                                                <span className="ml-1 font-normal text-muted-foreground">
                                                    (
                                                    {formatQty(
                                                        orderSummary.remainingM3,
                                                    )}{' '}
                                                    m³ ≈{' '}
                                                    {formatQty(
                                                        orderSummary.remainingTon,
                                                    )}{' '}
                                                    t)
                                                </span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs tracking-wide uppercase">
                                                Estoque atual
                                            </dt>
                                            <dd className="text-foreground">
                                                {formatQtyWithUnit(
                                                    orderSummary.stock,
                                                    orderSummary.unit,
                                                )}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            )}

                            {!selectedOrder && (
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
                            )}

                            <div className="space-y-3">
                                <div className="flex items-center justify-between gap-2">
                                    <Label>Produtos</Label>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            setLines((current) => [
                                                ...current,
                                                emptyLine(),
                                            ])
                                        }
                                    >
                                        <Plus />
                                        Adicionar produto
                                    </Button>
                                </div>
                                <InputError message={errors.items} />

                                {lines.map((line, index) => {
                                    const product = products.find(
                                        (item) =>
                                            String(item.id) === line.product_id,
                                    );
                                    const preview = linePreview(line, products);
                                    const usedIds = new Set(
                                        lines
                                            .filter(
                                                (item) =>
                                                    item.key !== line.key &&
                                                    item.product_id !== '',
                                            )
                                            .map((item) => item.product_id),
                                    );

                                    return (
                                        <div
                                            key={line.key}
                                            className="space-y-3 rounded-md border p-3"
                                        >
                                            <div className="flex items-start justify-between gap-2">
                                                <p className="text-sm font-medium">
                                                    Produto {index + 1}
                                                </p>
                                                {lines.length > 1 && (
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            setLines((current) =>
                                                                current.filter(
                                                                    (item) =>
                                                                        item.key !==
                                                                        line.key,
                                                                ),
                                                            )
                                                        }
                                                    >
                                                        <Trash2 />
                                                        Remover
                                                    </Button>
                                                )}
                                            </div>

                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`items-${index}-product`}
                                                >
                                                    Produto
                                                </Label>
                                                <select
                                                    id={`items-${index}-product`}
                                                    name={`items[${index}][product_id]`}
                                                    required
                                                    value={line.product_id}
                                                    onChange={(event) => {
                                                        const nextProductId =
                                                            event.target.value;
                                                        const nextProduct =
                                                            products.find(
                                                                (item) =>
                                                                    String(
                                                                        item.id,
                                                                    ) ===
                                                                    nextProductId,
                                                            );

                                                        updateLine(line.key, {
                                                            product_id:
                                                                nextProductId,
                                                            input_unit:
                                                                nextProduct?.unit ??
                                                                line.input_unit,
                                                        });
                                                    }}
                                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                                >
                                                    <option value="" disabled>
                                                        Selecione
                                                    </option>
                                                    {products.map((item) => (
                                                        <option
                                                            key={item.id}
                                                            value={item.id}
                                                            disabled={usedIds.has(
                                                                String(item.id),
                                                            )}
                                                        >
                                                            {item.name} (
                                                            {formatQtyWithUnit(
                                                                item.stock_quantity,
                                                                item.unit,
                                                            )}
                                                            )
                                                        </option>
                                                    ))}
                                                </select>
                                                <InputError
                                                    message={
                                                        errors[
                                                            `items.${index}.product_id`
                                                        ]
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-4 sm:grid-cols-2">
                                                <div className="grid gap-2">
                                                    <Label
                                                        htmlFor={`items-${index}-unit`}
                                                    >
                                                        Unidade
                                                    </Label>
                                                    <select
                                                        id={`items-${index}-unit`}
                                                        name={`items[${index}][input_unit]`}
                                                        value={line.input_unit}
                                                        onChange={(event) =>
                                                            updateLine(
                                                                line.key,
                                                                {
                                                                    input_unit:
                                                                        event
                                                                            .target
                                                                            .value as
                                                                            | 'm3'
                                                                            | 'ton',
                                                                },
                                                            )
                                                        }
                                                        className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                                    >
                                                        {units.map((unit) => (
                                                            <option
                                                                key={unit.value}
                                                                value={
                                                                    unit.value
                                                                }
                                                            >
                                                                {unit.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                    <InputError
                                                        message={
                                                            errors[
                                                                `items.${index}.input_unit`
                                                            ]
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label
                                                        htmlFor={`items-${index}-qty`}
                                                    >
                                                        Quantidade
                                                    </Label>
                                                    <Input
                                                        id={`items-${index}-qty`}
                                                        name={`items[${index}][quantity_input]`}
                                                        type="number"
                                                        step="0.001"
                                                        min="0.001"
                                                        required
                                                        value={
                                                            line.quantity_input
                                                        }
                                                        onChange={(event) =>
                                                            updateLine(
                                                                line.key,
                                                                {
                                                                    quantity_input:
                                                                        event
                                                                            .target
                                                                            .value,
                                                                },
                                                            )
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            errors[
                                                                `items.${index}.quantity_input`
                                                            ]
                                                        }
                                                    />
                                                </div>
                                            </div>

                                            {product && preview && (
                                                <p className="text-xs text-muted-foreground">
                                                    {product.name}: {preview.m3}{' '}
                                                    m³ ≈ {preview.ton} t
                                                    (densidade {product.density}
                                                    )
                                                </p>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="vehicle_plate">Placa</Label>
                                <Input
                                    id="vehicle_plate"
                                    name="vehicle_plate"
                                    required
                                    value={vehiclePlate}
                                    onChange={(event) =>
                                        setVehiclePlate(event.target.value)
                                    }
                                />
                                <InputError message={errors.vehicle_plate} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="loaded_at">Data/hora</Label>
                                <Input
                                    id="loaded_at"
                                    name="loaded_at"
                                    type="datetime-local"
                                />
                                <InputError message={errors.loaded_at} />
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
        { title: 'Carregamentos', href: index() },
        { title: 'Novo', href: create() },
    ],
};
