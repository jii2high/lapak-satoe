export type Role = 'buyer' | 'seller' | 'admin';

export type User = {
    id: number;
    display_name: string;
    username: string;
    email_masked: string;
    email_verified: boolean;
    active_role: Role;
    wishlist_product_ids: number[];
    wishlist_store_ids: number[];
};

export type Auth = {
    user: User;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
