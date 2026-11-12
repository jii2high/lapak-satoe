import { CircleAlert } from 'lucide-react';
import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export default function InputError({
    message,
    className = '',
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    return message ? (
        <p
            {...props}
            role="alert"
            className={cn(
                'flex items-start gap-1 text-sm text-destructive',
                className,
            )}
        >
            <CircleAlert className="mt-0.5 size-4 shrink-0" aria-hidden />
            <span>{message}</span>
        </p>
    ) : null;
}
