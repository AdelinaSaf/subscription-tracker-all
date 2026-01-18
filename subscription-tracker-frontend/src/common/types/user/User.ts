export interface User {
    id: number;
    email: string;
    roles: string[];
    timezone?: string;
}

export interface AdminStats {
    totalUsers: number
    totalSubscriptions: number
    activeSubscriptions: number
    monthlyRevenue: number
}