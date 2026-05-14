export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export interface Flash {
    success?: string | null;
    error?: string | null;
    warning?: string | null;
    info?: string | null;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    flash: Flash;
};
