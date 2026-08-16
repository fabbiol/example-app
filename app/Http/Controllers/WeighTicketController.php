<?php

namespace App\Http\Controllers;

use App\Actions\RecordWeighTicketAction;
use App\Actions\ReverseProductOutbound;
use App\Http\Requests\StoreWeighTicketRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\WeighTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WeighTicketController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('weigh-tickets/index', [
            'tickets' => WeighTicket::query()
                ->with(['customer', 'product', 'order'])
                ->latest('weighed_at')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('weigh-tickets/create', [
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit', 'stock_quantity']),
            'orders' => Order::query()
                ->with(['customer:id,name', 'product:id,name'])
                ->whereIn('status', ['open', 'scheduled', 'loading'])
                ->latest()
                ->get(['id', 'customer_id', 'product_id', 'quantity_requested', 'quantity_loaded', 'vehicle_plate', 'status']),
            'density' => (float) config('operations.stone_density'),
        ]);
    }

    public function store(StoreWeighTicketRequest $request, RecordWeighTicketAction $action): RedirectResponse
    {
        $ticket = $action->handle([
            ...$request->validated(),
            'user_id' => $request->user()?->id,
        ]);

        return redirect()
            ->route('weigh-tickets.show', $ticket)
            ->with('success', 'Ticket de balança registrado.');
    }

    public function show(WeighTicket $weighTicket): Response
    {
        $weighTicket->load(['customer', 'product', 'order', 'user']);

        return Inertia::render('weigh-tickets/show', [
            'ticket' => $weighTicket,
        ]);
    }

    public function destroy(WeighTicket $weighTicket, ReverseProductOutbound $reverseOutbound): RedirectResponse
    {
        DB::transaction(function () use ($weighTicket, $reverseOutbound): void {
            $ticket = WeighTicket::query()
                ->whereKey($weighTicket->id)
                ->lockForUpdate()
                ->firstOrFail();

            $product = Product::query()
                ->whereKey($ticket->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $order = $ticket->order_id
                ? Order::query()->whereKey($ticket->order_id)->lockForUpdate()->first()
                : null;

            $reverseOutbound->handle($product, (string) $ticket->quantity, $order);
            $ticket->delete();
        });

        return redirect()
            ->route('weigh-tickets.index')
            ->with('success', 'Ticket removido e estoque estornado.');
    }
}
