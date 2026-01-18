export const RolesEnum = {
    ROLE_ROOT: 'ROLE_ROOT',
    ROLE_ADMIN: 'ROLE_ADMIN',
    ROLE_USER: 'ROLE_USER'
} as const;

export type RolesEnum = (typeof RolesEnum)[keyof typeof RolesEnum];