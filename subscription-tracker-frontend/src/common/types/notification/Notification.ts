export interface Notification {
    id: number;
    message: string;
    subscription?: string;
    isRead: boolean;
    createdAt: string;
}

export interface CreateNotificationDto {
    message: string;
    subscriptionId?: number;
}