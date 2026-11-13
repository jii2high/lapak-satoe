import { cn } from '@/lib/utils';

export type KpiItem = {
    label: string;
    value: string;
    hint?: string;
};

/**
 * Strip KPI bergaya nota (DESIGN 8.7): satu kotak bergaris, dipisah garis tipis,
 * bukan kartu terpisah. Satu kolom di HP, satu baris di md.
 */
export function KpiStrip({ items }: { items: KpiItem[] }) {
    return (
        <dl className="flex flex-col border border-border bg-card md:flex-row">
            {items.map((item, index) => (
                <div
                    key={item.label}
                    className={cn(
                        'flex-1 px-4 py-4',
                        index > 0 &&
                            'border-t border-border md:border-t-0 md:border-l',
                    )}
                >
                    <dt className="text-sm text-muted-foreground">
                        {item.label}
                    </dt>
                    <dd className="mt-1 font-display text-2xl font-bold tabular-nums">
                        {item.value}
                    </dd>
                    {item.hint !== undefined && (
                        <p className="mt-1 text-xs text-muted-foreground">
                            {item.hint}
                        </p>
                    )}
                </div>
            ))}
        </dl>
    );
}
