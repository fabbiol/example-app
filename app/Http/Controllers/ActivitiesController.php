<?php

namespace App\Http\Controllers;

use App\Enums\ActivityAction;
use App\Enums\ActivityDomain;
use App\Enums\FlowPeriod;
use App\Http\Requests\IndexActivitiesRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class ActivitiesController extends Controller
{
    public function index(IndexActivitiesRequest $request): Response
    {
        $domain = $request->domain();
        $action = $request->action();
        $period = $request->period();
        $range = $request->range();
        $from = $range['from'];
        $to = $range['to'];
        $userFilter = $request->userFilter();

        return Inertia::render('activities/index', [
            'activities' => ActivityLog::query()
                ->select([
                    'id',
                    'user_id',
                    'domain',
                    'action',
                    'description',
                    'created_at',
                ])
                ->with('user:id,name')
                ->when(
                    $domain !== null,
                    fn ($query) => $query->where('domain', $domain->value),
                )
                ->when(
                    $action !== null,
                    fn ($query) => $query->where('action', $action->value),
                )
                ->when(
                    $userFilter === 'system',
                    fn ($query) => $query->whereNull('user_id'),
                )
                ->when(
                    is_int($userFilter),
                    fn ($query) => $query->where('user_id', $userFilter),
                )
                ->when(
                    $from !== null,
                    fn ($query) => $query->whereDate('created_at', '>=', $from->toDateString()),
                )
                ->when(
                    $to !== null,
                    fn ($query) => $query->whereDate('created_at', '<=', $to->toDateString()),
                )
                ->latest()
                ->paginate(15)
                ->withQueryString()
                ->through(fn (ActivityLog $log) => [
                    'id' => $log->id,
                    'domain' => $log->domain->value,
                    'domain_label' => $log->domain->label(),
                    'action' => $log->action->value,
                    'action_label' => $log->action->label(),
                    'description' => $log->description,
                    'user_name' => $log->user?->name,
                    'created_at' => $log->created_at?->toIso8601String(),
                ]),
            'filters' => [
                'domain' => $domain?->value,
                'action' => $action?->value,
                'user_id' => is_int($userFilter) ? (string) $userFilter : $userFilter,
                'period' => $period->value,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
            'domains' => ActivityDomain::options(),
            'actions' => ActivityAction::options(),
            'periods' => FlowPeriod::options(),
            'people' => $this->people(),
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function people(): array
    {
        $people = [
            ['value' => 'system', 'label' => 'Sistema'],
        ];

        foreach (User::query()->orderBy('name')->get(['id', 'name']) as $person) {
            $people[] = [
                'value' => sprintf('%d', $person->id),
                'label' => $person->name,
            ];
        }

        return $people;
    }
}
