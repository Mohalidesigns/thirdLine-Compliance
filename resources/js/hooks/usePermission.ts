import { usePage } from '@inertiajs/react';
import type { PageProps } from '@/types';

export function usePermissions(): string[] {
    const { auth } = usePage<PageProps>().props;
    return auth.permissions ?? [];
}

/**
 * Returns true if the current user has the given permission (or any of the
 * given permissions when an array is passed — OR semantics).
 */
export function useCan(permission: string | string[]): boolean {
    const permissions = usePermissions();
    if (Array.isArray(permission)) {
        return permission.some((p) => permissions.includes(p));
    }
    return permissions.includes(permission);
}
