export interface Subscription {
    id: number;
    name: string;
    price: number;
    currency: string;
    periodicity: 'month' | 'year' | 'week';
    nextPaymentDate: string;
    active: boolean;
}

export interface CreateSubscriptionDto {
    name: string;
    price: number;
    currency: string;
    periodicity: 'month' | 'year' | 'week';
    nextPaymentDate: string;
}

export interface UpdateSubscriptionDto {
    name?: string;
    price?: number;
    currency?: string;
    periodicity?: 'month' | 'year' | 'week';
    nextPaymentDate?: string;
    active?: boolean;
}