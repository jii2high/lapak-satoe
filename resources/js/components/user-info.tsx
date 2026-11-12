import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import type { User } from '@/types';

export function UserInfo({
    user,
    showEmail = false,
}: {
    user: User;
    showEmail?: boolean;
}) {
    const getInitials = useInitials();

    return (
        <>
            <Avatar className="size-8 overflow-hidden rounded-none">
                <AvatarFallback className="rounded-none bg-sidebar-primary text-sm font-semibold text-sidebar-primary-foreground">
                    {getInitials(user.display_name)}
                </AvatarFallback>
            </Avatar>
            <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-medium">
                    {user.display_name}
                </span>
                {showEmail && (
                    <span className="truncate text-xs text-sidebar-foreground/70">
                        {user.email_masked}
                    </span>
                )}
            </div>
        </>
    );
}
