<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\ProductUnit;
use App\Models\EstimatedLoading;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\WeighTicket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BuildOperationalDashboard
{
    /**
     * @return array{
     *     totals: array{
     *         active_products: int,
     *         total_stock_ton: string,
     *         total_stock_m3: string,
     *         open_orders: int,
     *         queue_count: int,
     *         weighed_today_ton: string,
     *         weighed_today_m3: string,
     *         tickets_today: int,
     *         estimated_today_ton: string,
     *         estimated_today_m3: string,
     *         estimates_today: int,
     *         produced_today_ton: string,
     *         produced_today_m3: string
     *     },
     *     stocks: Collection<int, Product>,
     *     queue: Collection<int, Order>,
     *     recent_tickets: Collection<int, WeighTicket>,
     *     recent_estimates: Collection<int, EstimatedLoading>,
     *     date: string
     * }
     */
    public function handle(?Carbon $date = null): array
    {
        $day = ($date ?? now())->startOfDay();
        $dayEnd = $day->copy()->endOfDay();

        $stocks = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'unit', 'density', 'stock_quantity']);

        $queue = Order::query()
            ->with(['customer:id,name', 'product:id,name,unit,density'])
            ->whereIn('status', [
                OrderStatus::Open,
                OrderStatus::Scheduled,
                OrderStatus::Loading,
            ])
            ->where(function ($query) use ($day): void {
                $query->whereNull('scheduled_at')
                    ->orWhereDate('scheduled_at', '<=', $day);
            })
            ->orderByRaw('case when scheduled_at is null then 1 else 0 end')
            ->orderBy('scheduled_at')
            ->orderBy('created_at')
            ->limit(20)
            ->get();

        $recentTickets = WeighTicket::query()
            ->with(['customer:id,name', 'product:id,name,unit,density'])
            ->whereBetween('weighed_at', [$day, $dayEnd])
            ->latest('weighed_at')
            ->limit(8)
            ->get();

        $recentEstimates = EstimatedLoading::query()
            ->with(['customer:id,name', 'product:id,name,unit'])
            ->whereBetween('loaded_at', [$day, $dayEnd])
            ->latest('loaded_at')
            ->limit(8)
            ->get();

        $ticketsToday = WeighTicket::query()
            ->whereBetween('weighed_at', [$day, $dayEnd])
            ->count();

        $estimatesToday = EstimatedLoading::query()
            ->whereBetween('loaded_at', [$day, $dayEnd])
            ->count();

        $weighedTodayTon = (string) (WeighTicket::query()
            ->whereBetween('weighed_at', [$day, $dayEnd])
            ->sum('net_weight') ?: '0');

        $weighedTodayM3 = (string) (WeighTicket::query()
            ->whereBetween('weighed_at', [$day, $dayEnd])
            ->sum('quantity_m3') ?: '0');

        $estimatedTodayTon = (string) (EstimatedLoading::query()
            ->whereBetween('loaded_at', [$day, $dayEnd])
            ->sum('quantity_ton') ?: '0');

        $estimatedTodayM3 = (string) (EstimatedLoading::query()
            ->whereBetween('loaded_at', [$day, $dayEnd])
            ->sum('quantity_m3') ?: '0');

        $producedTodayTon = (string) (ProductionEntry::query()
            ->whereDate('produced_on', $day)
            ->whereNull('parent_id')
            ->sum('quantity_ton') ?: '0');

        $producedTodayM3 = (string) (ProductionEntry::query()
            ->whereDate('produced_on', $day)
            ->whereNull('parent_id')
            ->sum('quantity_m3') ?: '0');

        $openOrders = Order::query()
            ->whereIn('status', [
                OrderStatus::Open,
                OrderStatus::Scheduled,
                OrderStatus::Loading,
            ])
            ->count();

        $totalStockTon = '0.000';
        $totalStockM3 = '0.000';

        foreach ($stocks as $product) {
            $density = max((float) $product->density, 0.001);
            $stock = (string) $product->stock_quantity;

            if ($product->unit === ProductUnit::Ton) {
                $totalStockTon = bcadd($totalStockTon, $stock, 3);
                $totalStockM3 = bcadd($totalStockM3, bcdiv($stock, number_format($density, 3, '.', ''), 3), 3);
            } else {
                $totalStockM3 = bcadd($totalStockM3, $stock, 3);
                $totalStockTon = bcadd($totalStockTon, bcmul($stock, number_format($density, 3, '.', ''), 3), 3);
            }
        }

        return [
            'totals' => [
                'active_products' => $stocks->count(),
                'total_stock_ton' => $totalStockTon,
                'total_stock_m3' => $totalStockM3,
                'open_orders' => $openOrders,
                'queue_count' => $queue->count(),
                'weighed_today_ton' => number_format((float) $weighedTodayTon, 3, '.', ''),
                'weighed_today_m3' => number_format((float) $weighedTodayM3, 3, '.', ''),
                'tickets_today' => $ticketsToday,
                'estimated_today_ton' => number_format((float) $estimatedTodayTon, 3, '.', ''),
                'estimated_today_m3' => number_format((float) $estimatedTodayM3, 3, '.', ''),
                'estimates_today' => $estimatesToday,
                'produced_today_ton' => number_format((float) $producedTodayTon, 3, '.', ''),
                'produced_today_m3' => number_format((float) $producedTodayM3, 3, '.', ''),
            ],
            'stocks' => $stocks,
            'queue' => $queue,
            'recent_tickets' => $recentTickets,
            'recent_estimates' => $recentEstimates,
            'date' => $day->toDateString(),
        ];
    }
}
