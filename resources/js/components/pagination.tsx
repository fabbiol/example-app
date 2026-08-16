import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

export default function Pagination({ meta }: { meta: Paginated<unknown> }) {
    if (meta.last_page <= 1) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3">
            <p className="text-sm text-muted-foreground">
                {meta.from}-{meta.to} de {meta.total}
            </p>
            <div className="flex flex-wrap gap-1">
                {meta.links.map((link, index) => {
                    if (!link.url) {
                        return (
                            <Button
                                key={`${link.label}-${index}`}
                                variant="outline"
                                size="sm"
                                disabled
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        );
                    }

                    return (
                        <Button
                            key={`${link.label}-${index}`}
                            variant={link.active ? 'default' : 'outline'}
                            size="sm"
                            asChild
                        >
                            <Link
                                href={link.url}
                                preserveScroll
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        </Button>
                    );
                })}
            </div>
        </div>
    );
}
