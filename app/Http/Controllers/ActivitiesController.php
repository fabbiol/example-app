<?php

namespace App\Http\Controllers;

use App\Enums\ActivityDomain;
use App\Http\Requests\IndexActivitiesRequest;
use App\Models\ActivityLog;
use Inertia\Inertia;
use Inertia\Response;

class ActivitiesController extends Controller
{
    public function index(IndexActivitiesRequest $request): Response
    {
        $domain = $request->domain();

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
            ],
            'domains' => ActivityDomain::options(),
        ]);
    }
}
