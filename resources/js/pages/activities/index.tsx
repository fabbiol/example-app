import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/activities';
import type { ActivityLog, Paginated } from '@/types';

type DomainOption = {
    value: string;
    label: string;
};

type Filters = {
    domain: string | null;
};

const visitOptions = {
    preserveState: true,
    preserveScroll: true,
    replace: true,
} as const;

function formatWhen(value: string): string {
    return new Date(value).toLocaleString('pt-BR');
}

export default function ActivitiesIndex({
    activities,
    filters,
    domains,
}: {
    activities: Paginated<ActivityLog>;
    filters: Filters;
    domains: DomainOption[];
}) {
    const applyDomain = (domain: string | null) => {
        if (domain === null) {
            router.get(index.url(), {}, visitOptions);

            return;
        }

        router.get(index.url({ query: { domain } }), {}, visitOptions);
    };

    return (
        <>
            <Head title="Atividades" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Atividades"
                    description="Registro do que aconteceu no sistema, separado entre operação e administração."
                />

                <div className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant={filters.domain === null ? 'default' : 'outline'}
                        onClick={() => applyDomain(null)}
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
                            onClick={() => applyDomain(domain.value)}
                        >
                            {domain.label}
                        </Button>
                    ))}
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
