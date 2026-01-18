import { api } from './index'
import type { 
    Notification, 
    CreateNotificationDto 
} from '../common/types/notification/Notification'


export default class NotificationApi {
    static async getNotifications(params?: {
        page?: number
        limit?: number
        unread?: boolean
        type?: string
    }) {
        return api.get<{ notifications: Notification[]; total: number }>(
            '/notifications',
            { params }
        )
    }

    // // Получить настройки уведомлений
    // static async getSettings() {
    //     return api.get<NotificationSettings>('/notifications/settings')
    // }

    // // Обновить настройки уведомлений
    // static async updateSettings(settings: Partial<NotificationSettings>) {
    //     return api.patch('/notifications/settings', settings)
    // }

    // Пометить как прочитанное
    static async markAsRead(id: number) {
        return api.post(`/notifications/${id}/read`)
    }

    // Пометить все как прочитанные
    static async markAllAsRead() {
        return api.post('/notifications/read-all')
    }

    // Удалить уведомление
    static async deleteNotification(id: number) {
        return api.delete(`/notifications/${id}`)
    }

    // Для администратора: получить все уведомления
    static async getAllNotifications(params?: {
        page?: number
        limit?: number
        userId?: number
    }) {
        return api.get('/admin/notifications', { params })
    }

    // Для администратора: создать уведомление
    static async createAdminNotification(dto: CreateNotificationDto) {
        return api.post('/admin/notifications', dto)
    }
}