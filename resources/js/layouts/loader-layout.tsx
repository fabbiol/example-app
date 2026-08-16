import type { PropsWithChildren } from 'react';

export default function LoaderLayout({ children }: PropsWithChildren) {
    return (
        <div className="loader-shell min-h-svh bg-stone-100 text-stone-900 antialiased">
            <div className="mx-auto flex min-h-svh w-full max-w-3xl flex-col">
                {children}
            </div>
        </div>
    );
}
