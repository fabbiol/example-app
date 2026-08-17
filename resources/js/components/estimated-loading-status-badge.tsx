import { Badge } from '@/components/ui/badge';
import type { EstimatedLoadingStatus } from '@/types';

const statusLabel: Record<EstimatedLoadingStatus, string> = {
    released: 'Liberado',
    loading: 'Carregando',
    loaded: 'Carregado',
};

const statusVariant: Record<
    EstimatedLoadingStatus,
    'outline' | 'secondary' | 'default'
> = {
    released: 'outline',
    loading: 'secondary',
    loaded: 'default',
};

export function itemStatus(
    loaderLoadedAt: string | null | undefined,
): EstimatedLoadingStatus {
    return loaderLoadedAt ? 'loaded' : 'released';
}

export default function EstimatedLoadingStatusBadge({
    status,
}: {
    status: EstimatedLoadingStatus;
}) {
    return <Badge variant={statusVariant[status]}>{statusLabel[status]}</Badge>;
}
