import { Link } from '@inertiajs/react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center bg-background px-4 py-8 pb-[calc(2rem+env(safe-area-inset-bottom))] md:p-10">
            <div className="w-full max-w-[400px]">
                <Link
                    href={home()}
                    className="mb-6 inline-flex min-h-11 items-center font-display text-xl font-bold text-foreground focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                >
                    Lapak Satoe
                </Link>

                <div className="overflow-hidden rounded-md border border-border bg-card">
                    <div className="awning awning-sm awning-intro" />
                    <div className="flex flex-col gap-6 p-6">
                        <div className="space-y-2 text-center">
                            <h1 className="font-display text-2xl font-bold">
                                {title}
                            </h1>
                            {description && (
                                <p className="text-sm text-muted-foreground">
                                    {description}
                                </p>
                            )}
                        </div>
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
