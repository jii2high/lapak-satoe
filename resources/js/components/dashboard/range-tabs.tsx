import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';

export const RANGE_OPTIONS = [
    { value: '7d', label: '7 hari', period: '7 hari terakhir' },
    { value: '1m', label: 'Bulan', period: '30 hari terakhir' },
    { value: '6m', label: '6 bulan', period: '6 bulan terakhir' },
    { value: 'all', label: 'Total', period: 'seluruh periode' },
] as const;

export type Range = (typeof RANGE_OPTIONS)[number]['value'];

export function rangePeriod(range: string): string {
    return RANGE_OPTIONS.find((option) => option.value === range)?.period ?? '';
}

/** Filter rentang chart (DESIGN 8.7): tab bergaris bawah hijau, target sentuh 44px. */
export function RangeTabs({
    value,
    onChange,
}: {
    value: Range;
    onChange: (value: Range) => void;
}) {
    return (
        <Tabs value={value} onValueChange={(next) => onChange(next as Range)}>
            <TabsList variant="line" className="h-11 w-full gap-0 sm:w-fit">
                {RANGE_OPTIONS.map((option) => (
                    <TabsTrigger
                        key={option.value}
                        value={option.value}
                        className="flex-1 after:bg-primary data-[state=active]:text-primary sm:flex-none sm:px-3"
                    >
                        {option.label}
                    </TabsTrigger>
                ))}
            </TabsList>
        </Tabs>
    );
}
