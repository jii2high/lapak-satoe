import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import type { HTMLAttributes } from 'react';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

export default function AppearanceToggleTab({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { appearance, updateAppearance } = useAppearance();

    const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
        { value: 'light', icon: Sun, label: 'Terang' },
        { value: 'dark', icon: Moon, label: 'Gelap' },
        { value: 'system', icon: Monitor, label: 'Sistem' },
    ];

    return (
        <div
            className={cn(
                'inline-flex w-full gap-1 rounded-md border border-border bg-card p-1 sm:w-auto',
                className,
            )}
            role="group"
            aria-label="Pilihan tema"
            {...props}
        >
            {tabs.map(({ value, icon: Icon, label }) => (
                <button
                    key={value}
                    type="button"
                    onClick={() => updateAppearance(value)}
                    aria-pressed={appearance === value}
                    className={cn(
                        'flex min-h-11 flex-1 items-center justify-center gap-2 rounded-md px-3 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring sm:flex-none',
                        appearance === value
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                    )}
                >
                    <Icon className="size-4" aria-hidden />
                    {label}
                </button>
            ))}
        </div>
    );
}
