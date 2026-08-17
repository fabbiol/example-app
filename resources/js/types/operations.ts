export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
};

export type Option = {
    value: string;
    label: string;
};

export type Product = {
    id: number;
    name: string;
    code: string;
    unit: 'ton' | 'm3';
    density: string;
    bucket_capacity_m3: string;
    stock_quantity: string;
    is_active: boolean;
    notes: string | null;
    created_at: string;
    updated_at: string;
};

export type Customer = {
    id: number;
    name: string;
    document: string | null;
    marketup_code: string | null;
    phone: string | null;
    is_active: boolean;
    notes: string | null;
    created_at: string;
    updated_at: string;
};

export type OrderStatus =
    'open' | 'scheduled' | 'loading' | 'completed' | 'cancelled';

export type Order = {
    id: number;
    customer_id: number;
    product_id: number;
    quantity_requested: string;
    quantity_loaded: string;
    status: OrderStatus;
    destination: string | null;
    vehicle_plate: string | null;
    scheduled_at: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    customer?: Pick<Customer, 'id' | 'name'>;
    product?: Pick<Product, 'id' | 'name' | 'unit' | 'density'>;
};

export type WeighTicket = {
    id: number;
    number: string;
    order_id: number | null;
    customer_id: number;
    product_id: number;
    user_id: number | null;
    vehicle_plate: string;
    tare_weight: string;
    gross_weight: string;
    net_weight: string;
    quantity: string;
    quantity_m3: string | null;
    density: string | null;
    weighed_at: string;
    notes: string | null;
    created_at: string;
    updated_at: string;
    customer?: Pick<Customer, 'id' | 'name'>;
    product?: Pick<Product, 'id' | 'name' | 'unit'>;
    order?: Pick<Order, 'id' | 'status'> | null;
};

export type EstimatedLoadingStatus = 'released' | 'loading' | 'loaded';

export type EstimatedLoadingItem = {
    id: number;
    product_id: number;
    sort_order: number;
    input_unit: 'ton' | 'm3';
    quantity_m3: string;
    quantity_ton: string;
    quantity: string;
    density: string;
    loader_loaded_at?: string | null;
    product?: Pick<Product, 'id' | 'name' | 'unit'>;
};

export type EstimatedLoading = {
    id: number;
    number: string;
    order_id: number | null;
    caixa_id: number | null;
    caixa_number: string | null;
    customer_id: number;
    product_id: number;
    user_id: number | null;
    vehicle_plate: string;
    buckets_count: number | null;
    bucket_capacity_m3: string | null;
    input_unit: 'ton' | 'm3';
    quantity_m3: string;
    quantity_ton: string;
    quantity: string;
    density: string;
    loaded_at: string;
    notes: string | null;
    created_at: string;
    updated_at: string;
    status: EstimatedLoadingStatus;
    customer?: Pick<Customer, 'id' | 'name'>;
    product?: Pick<Product, 'id' | 'name' | 'unit'>;
    order?: Pick<Order, 'id' | 'status'> | null;
    items?: EstimatedLoadingItem[];
};

export type CaixaEntry = {
    id: number;
    data: string;
    descricao: string;
    valor: string;
    tipo: string;
    tipo_label: string;
    metodo_pagamento: string | null;
    cliente_haver: string | null;
};

export type CrushingCircuitYield = {
    id: number;
    crushing_circuit_id: number;
    product_id: number;
    group_name: string | null;
    percent: string;
    percent_min: string | null;
    percent_max: string | null;
    sort_order: number;
    product?: Pick<Product, 'id' | 'name' | 'code'>;
};

export type CrushingCircuit = {
    id: number;
    name: string;
    is_default: boolean;
    is_active: boolean;
    notes: string | null;
    created_at: string;
    updated_at: string;
    yields?: CrushingCircuitYield[];
};

export type ProductionEntry = {
    id: number;
    parent_id: number | null;
    crushing_circuit_id: number | null;
    affects_stock: boolean;
    yield_percent: string | null;
    product_id: number;
    user_id: number | null;
    method: 'trips' | 'quantity' | 'scale';
    truck_id: number | null;
    trips_count: number | null;
    truck_capacity_m3: string | null;
    input_unit: 'ton' | 'm3' | null;
    quantity: string;
    quantity_m3: string | null;
    quantity_ton: string | null;
    density: string | null;
    stage: 'quarry_to_primary' | 'plant';
    shift: 'morning' | 'afternoon' | 'night';
    produced_on: string;
    loaded_at: string | null;
    unloaded_at: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    product?: Pick<Product, 'id' | 'name' | 'unit'>;
    truck?: Pick<Truck, 'id' | 'name' | 'plate' | 'capacity_m3'> | null;
    user?: { id: number; name: string } | null;
    children?: ProductionEntry[];
};

export type HaulageTruckSummary = {
    truck_id: number;
    name: string | null;
    plate: string | null;
    trips: number;
    volume_m3: string;
};

export type HaulageProductionSummary = {
    trips: number;
    volume_m3: string;
    volume_ton: string;
    trucks: HaulageTruckSummary[];
};

export type Truck = {
    id: number;
    name: string;
    plate: string;
    capacity_m3: string;
    is_active: boolean;
    notes: string | null;
    created_at: string;
    updated_at: string;
};

export type ActivityDomain = 'operational' | 'administrative';

export type ActivityAction =
    'created' | 'updated' | 'deleted' | 'logged_in' | 'logged_out';

export type ActivityLog = {
    id: number;
    domain: ActivityDomain;
    domain_label: string;
    action: ActivityAction;
    action_label: string;
    description: string;
    user_name: string | null;
    created_at: string;
};
