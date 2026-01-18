import { api } from './index'
import type { 
    Payment, 
    CreatePaymentDto,
    UpdatePaymentDto 
} from '../common/types/payment/Payment'
export default class PaymentApi {
    static async getPayments(params?: {
        page?: number
        limit?: number
        startDate?: string
        endDate?: string
        status?: string
        subscriptionId?: number
    }) {
        return api.get<{ payments: Payment[]; total: number }>(
            '/payments',
            { params }
        )
    }

    // Получить статистику
    static async getStats(params?: {
        period?: 'week' | 'month' | 'year'
        startDate?: string
        endDate?: string
    }) {
        return api.get('/payments/stats', { params })
    }

    // Создать платеж
    static async createPayment(dto: CreatePaymentDto) {
        return api.post('/payments', dto)
    }

    // Обновить платеж
    static async updatePayment(id: number, dto: UpdatePaymentDto) {
        return api.patch(`/payments/${id}`, dto)
    }

    // Удалить платеж
    static async deletePayment(id: number) {
        return api.delete(`/payments/${id}`)
    }

    // Повторить платеж
    static async retryPayment(id: number) {
        return api.post(`/payments/${id}/retry`)
    }

    // Экспорт платежей
    static async exportPayments(params?: {
        startDate?: string
        endDate?: string
        status?: string
        format?: 'csv' | 'excel'
    }) {
        return api.get('/payments/export', { 
            params,
            responseType: 'blob'
        })
    }

    // Получить статусы
    static async getStatuses() {
        return api.get('/payments/statuses')
    }

    // Для администратора: получить все платежи
    static async getAllPayments(params?: {
        page?: number
        limit?: number
        userId?: number
        startDate?: string
        endDate?: string
        status?: string
    }) {
        return api.get('/admin/payments', { params })
    }

    // Для администратора: получить платежи пользователя
    static async getUserPayments(userId: number, params?: any) {
        return api.get(`/admin/users/${userId}/payments`, { params })
    }
}