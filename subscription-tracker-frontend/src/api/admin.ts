import { api } from './index'

export default class AdminApi {
    static async getUsers() {
        return api.get('/admin/users')
    }

    static async updateUser(id: number, data: any) {
        return api.patch(`/admin/users/${id}`, data)
    }

    static async deleteUser(id: number) {
        return api.delete(`/admin/users/${id}`)
    }

    static async getAllSubscriptions() {
        return api.get('/admin/subscriptions')
    }

    static async getAdminStats() {
        return api.get('/admin/stats')
    }
    
    // Новый метод для всех платежей
    static async getAllPayments() {
        return api.get('/admin/payments')
    }
}