import {
    CartesianGrid,
    Line,
    LineChart,
    ReferenceDot,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { formatBucketFull, formatBucketLabel } from '@/lib/format';
import type { ReactNode } from 'react';

type Point = Record<string, string | number>;

type Props = {
    data: Point[];
    dataKey: string;
    name: string;
    formatValue: (value: number) => string;
    formatTick: (value: number) => string;
};

/**
 * Chart garis dashboard (DESIGN 8.7): garis 2px hijau, tanpa area, grid horizontal
 * saja, tooltip persegi putih, satu titik oranye di titik terakhir.
 */
export function StatsChart({
    data,
    dataKey,
    name,
    formatValue,
    formatTick,
}: Props) {
    const last = data.at(-1);

    return (
        <div className="h-[200px] w-full md:h-[280px]">
            <ResponsiveContainer width="100%" height="100%">
                <LineChart
                    data={data}
                    margin={{ top: 8, right: 12, bottom: 0, left: 0 }}
                >
                    <CartesianGrid
                        stroke="var(--color-border)"
                        vertical={false}
                    />
                    <XAxis
                        dataKey="key"
                        tickFormatter={(key: string) => formatBucketLabel(key)}
                        tickLine={false}
                        axisLine={false}
                        minTickGap={16}
                        tick={{
                            fontSize: 11,
                            fill: 'var(--color-muted-foreground)',
                        }}
                    />
                    <YAxis
                        width={52}
                        allowDecimals={false}
                        tickFormatter={(value: number) => formatTick(value)}
                        tickLine={false}
                        axisLine={false}
                        tick={{
                            fontSize: 11,
                            fill: 'var(--color-muted-foreground)',
                        }}
                    />
                    <Tooltip
                        labelFormatter={(label: ReactNode) =>
                            typeof label === 'string' ||
                            typeof label === 'number'
                                ? formatBucketFull(String(label))
                                : null
                        }
                        formatter={(value: unknown) => [
                            formatValue(Number(value)),
                            name,
                        ]}
                        contentStyle={{
                            background: 'var(--color-card)',
                            border: '1px solid var(--color-border)',
                            borderRadius: 2,
                            fontSize: 12,
                        }}
                        labelStyle={{
                            color: 'var(--color-muted-foreground)',
                        }}
                    />
                    <Line
                        type="monotone"
                        dataKey={dataKey}
                        stroke="var(--color-chart-1)"
                        strokeWidth={2}
                        isAnimationActive={false}
                        dot={false}
                    />
                    {last !== undefined && (
                        <ReferenceDot
                            x={String(last.key)}
                            y={Number(last[dataKey])}
                            r={3.5}
                            fill="var(--color-chart-4)"
                            stroke="var(--color-chart-4)"
                        />
                    )}
                </LineChart>
            </ResponsiveContainer>
        </div>
    );
}
