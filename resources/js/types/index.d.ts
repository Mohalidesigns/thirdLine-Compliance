export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string | null;
}

export interface Flash {
    success?: string | null;
    error?: string | null;
    warning?: string | null;
    info?: string | null;
}

export interface SharedAuthProps {
    user: User | null;
    permissions: string[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: SharedAuthProps;
    flash: Flash;
};
