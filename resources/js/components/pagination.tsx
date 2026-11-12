import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { PaginationLink } from '@/types';

function linkLabel(link: PaginationLink): string {
    if (/previous/i.test(link.label)) {
        return 'Sebelumnya';
    }

    if (/next/i.test(link.label)) {
        return 'Berikutnya';
    }

    return link.label.replace(/&[a-z]+;/gi, '').trim();
}

export function Pagination({
    links,
    lastPage,
}: {
    links: PaginationLink[];
    lastPage: number;
}) {
    if (lastPage <= 1) {
        return null;
    }

    return (
        <nav
            aria-label="Halaman hasil"
            className="mt-8 flex flex-wrap items-center justify-center gap-1"
        >
            {links.map((link, index) => {
                const label = linkLabel(link);
                const isPrevious = /previous/i.test(link.label);
                const isNext = /next/i.test(link.label);
                const isNumber = /^\d+$/.test(label);

                const className = cn(
                    'flex min-h-11 min-w-11 items-center justify-center rounded-md border px-3 text-sm transition-colors md:min-h-9 md:min-w-9',
                    link.active
                        ? 'border-primary bg-primary font-semibold text-primary-foreground'
                        : 'border-border bg-card hover:bg-accent',
                    link.url === null && 'opacity-50',
                );

                if (link.url === null) {
                    return (
                        <span
                            key={index}
                            aria-disabled="true"
                            className={className}
                        >
                            {isPrevious && <ChevronLeft aria-hidden />}
                            {isNext && <ChevronRight aria-hidden />}
                            <span className={isNumber ? undefined : 'sr-only'}>
                                {label}
                            </span>
                        </span>
                    );
                }

                return (
                    <Link
                        key={index}
                        href={link.url}
                        preserveScroll
                        aria-current={link.active ? 'page' : undefined}
                        className={className}
                    >
                        {isPrevious && <ChevronLeft aria-hidden />}
                        {isNext && <ChevronRight aria-hidden />}
                        <span className={isNumber ? undefined : 'sr-only'}>
                            {label}
                        </span>
                    </Link>
                );
            })}
        </nav>
    );
}
