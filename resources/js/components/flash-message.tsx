import { usePage } from '@inertiajs/react';

type FlashProps = {
    flash?: {
        success?: string | null;
    };
};

export default function FlashMessage() {
    const { flash } = usePage<FlashProps>().props;

    if (!flash?.success) {
        return null;
    }

    return (
        <div className="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
            {flash.success}
        </div>
    );
}
