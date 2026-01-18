export interface Payment {
    id: number;
    subscription: string;
    amount: number;
    status: string;
    paymentDate: string;
}

export interface CreatePaymentDto {
    subscriptionId: number;
    amount: number;
    statusId: number;
}
export interface UpdatePaymentDto {
    statusId?: number;
    amount?: number;
    currency?: string;
    paymentDate?: string; // ISO string
    notes?: string;
    transactionId?: string;
    metadata?: Record<string, any>;
}