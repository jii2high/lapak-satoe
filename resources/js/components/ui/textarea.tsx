import * as React from 'react';
import { cn } from '@/lib/utils';

function Textarea({ className, ...props }: React.ComponentProps<'textarea'>) {
    return (
        <textarea
            data-slot="textarea"
            className={cn(
                'flex min-h-28 w-full rounded-md border border-input bg-card px-3 py-2 text-base text-foreground transition-colors outline-none placeholder:text-muted-foreground md:text-sm',
                'focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30',
                'aria-invalid:border-destructive disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
                className,
            )}
            {...props}
        />
    );
}

export { Textarea };
