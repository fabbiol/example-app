<?php

namespace App\Http\Controllers;

use App\Actions\BuildFlowDiagram;
use App\Enums\FlowPeriod;
use App\Http\Requests\ShowFlowRequest;
use Inertia\Inertia;
use Inertia\Response;

class FlowController extends Controller
{
    public function __invoke(ShowFlowRequest $request, BuildFlowDiagram $diagram): Response
    {
        $range = $request->range();

        return Inertia::render('flow/index', [
            ...$diagram->handle($range['from'], $range['to']),
            'filters' => [
                'period' => $request->period()->value,
                'from' => $range['from']?->toDateString(),
                'to' => $range['to']?->toDateString(),
            ],
            'periods' => FlowPeriod::options(),
        ]);
    }
}
