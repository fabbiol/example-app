import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/activities';
import type { ActivityLog, Paginated } from '@/types';

type Option = {
    value: string;
    label: string;
};

type Filters = {
    domain: string | null;
    action: string | null;
    user_id: string | null;
    period: string;
    from: string | null;
    to: string | null;
};

const visitOptions = {
    preserveState: true,
    preserveScroll: true,
    replace: true,
} as const;

const selectClassName =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs';

function formatWhen(value: string): string {
    return new Date(value).toLocaleString('pt-BR');
}

export default function ActivitiesIndex({
    activities,
    filters,
    domains,
    actions,
    periods,
    people,
}: {
    activities: Paginated<ActivityLog>;
    filters: Filters;
    domains: Option[];
    actions: Option[];
    periods: Option[];
    people: Option[];
}) {
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');

    useEffect(() => {
        setFrom(filters.from ?? '');
        setTo(filters.to ?? '');
    }, [filters.from, filters.to]);

    const visit = (next: Partial<Filters>) => {
        const merged: Filters = { ...filters, ...next };
        const query: Record<string, string> = {};

        if (merged.domain) {
            query.domain = merged.domain;
        }

        if (merged.action) {
            query.action = merged.action;
        }

        if (merged.user_id) {
            query.user_id = merged.user_id;
        }

        if (merged.period && merged.period !== 'all') {
            query.period = merged.period;
        }

        if (merged.period === 'custom') {
            if (merged.from) {
                query.from = merged.from;
            }

            if (merged.to) {
                query.to = merged.to;
            }
        }

        router.get(index.url({ query }), {}, visitOptions);
    };

    const applyCustomDates = (nextFrom: string, nextTo: string) => {
        const rangeFrom = nextFrom || nextTo;
        const rangeTo = nextTo || nextFrom;

        if (rangeFrom === '' || rangeTo === '') {
            visit({ period: 'all', from: null, to: null });

            return;
        }

        visit({
            period: 'custom',
            from: rangeFrom,
            to: rangeTo,
        });
    };

    return (
        <>
            <Head title="Atividades" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Atividades"
                    description="Registro do que aconteceu no sistema, separado entre operação e administração."
                />

                <div className="flex flex-col gap-3">
                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            size="sm"
                            variant={filters.domain === null ? 'default' : 'outline'}
                            onClick={() => visit({ domain: null })}
                        >
                            Todas
                        </Button>
                        {domains.map((domain) => (
                            <Button
                                key={domain.value}
                                type="button"
                                size="sm"
                                variant={
                                    filters.domain === domain.value
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() => visit({ domain: domain.value })}
                            >
                                {domain.label}
                            </Button>
                        ))}
                    </div>

                    <div className="flex flex-wrap gap-2">
                        {periods.map((period) => (
                            <Button
                                key={period.value}
                                type="button"
                                size="sm"
                                variant={
                                    filters.period === period.value
                                        ? 'default'
                                        : 'outline'
                                }
                                onClick={() => visit({ period: period.value })}
                            >
                                {period.label}
                            </Button>
                        ))}
                    </div>

                    <div className="flex flex-wrap items-end gap-3">
                        <div className="grid gap-2">
                            <Label htmlFor="activity-from">De</Label>
                            <Input
                                id="activity-from"
                                type="date"
                                className="w-40"
                                value={from}
                                onChange={(event) => {
                                    const value = event.target.value;
                                    setFrom(value);
                                    applyCustomDates(value, to);
                                }}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="activity-to">Até</Label>
                            <Input
                                id="activity-to"
                                type="date"
                                className="w-40"
                                value={to}
                                onChange={(event) => {
                                    const value = event.target.value;
                                    setTo(value);
                                    applyCustomDates(from, value);
                                }}
                            />
                        </div>
                        <div className="grid min-w-48 gap-2">
                            <Label htmlFor="activity-user">Pessoa</Label>
                            <select
                                id="activity-user"
                                className={selectClassName}
                                value={filters.user_id ?? ''}
                                onChange={(event) => {
                                    visit({
                                        user_id: event.target.value || null,
                                    });
                                }}
                            >
                                <option value="">Todas as pessoas</option>
                                {people.map((person) => (
                                    <option key={person.value} value={person.value}>
                                        {person.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="grid min-w-44 gap-2">
                            <Label htmlFor="activity-action">Ação</Label>
                            <select
                                id="activity-action"
                                className={selectClassName}
                                value={filters.action ?? ''}
                                onChange={(event) => {
                                    visit({
                                        action: event.target.value || null,
                                    });
                                }}
                            >
                                <option value="">Todas as ações</option>
                                {actions.map((item) => (
                                    <option key={item.value} value={item.value}>
                                        {item.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full min-w-[720px] text-left text-sm">
                        <thead className="border-b bg-muted/40">
                            <tr>
                                <th className="px-4 py-3 font-medium">Quando</th>
                                <th className="px-4 py-3 font-medium">Tipo</th>
                                <th className="px-4 py-3 font-medium">Ação</th>
                                <th className="px-4 py-3 font-medium">Atividade</th>
                                <th className="px-4 py-3 font-medium">Pessoa</th>
                            </tr>
                        </thead>
                        <tbody>
                            {activities.data.map((activity) => (
                                <tr
                                    key={activity.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="px-4 py-3 whitespace-nowrap text-muted-foreground tabular-nums">
                                        {formatWhen(activity.created_at)}
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge
                                            variant={
                                                activity.domain === 'operational'
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {activity.domain_label}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3">{activity.action_label}</td>
                                    <td className="px-4 py-3 font-medium">
                                        {activity.description}
                                    </td>
                                    <td className="px-4 py-3">
                                        {activity.user_name ?? 'Sistema'}
                                    </td>
                                </tr>
                            ))}
                            {activities.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Nenhuma atividade neste filtro.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pagination meta={activities} />
            </div>
        </>
    );
}

ActivitiesIndex.layout = {
    breadcrumbs: [{ title: 'Atividades', href: index() }],
};
