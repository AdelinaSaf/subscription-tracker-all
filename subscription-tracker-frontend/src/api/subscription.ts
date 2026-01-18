import { api } from './index'
import type { CreateSubscriptionDto } from '../common/types/subscription/Subscription'
import type { UpdateSubscriptionDto } from '../common/types/subscription/Subscription'

export default class SubscriptionApi {
    static async getSubscriptions() {
        return api.get('/subscriptions', {
            params: { onlyActive: false } // Всегда показывать все
        })
    }

    static async createSubscription(payload: CreateSubscriptionDto) {
        return api.post('/subscriptions', payload)
    }

    static async updateSubscription(id: number, payload: UpdateSubscriptionDto) {
        return api.patch(`/subscriptions/${id}`, payload)
    }

    static async toggleSubscriptionStatus(id: number) {
        return api.post(`/subscriptions/${id}/toggle-status`)
    }

    static async deleteSubscription(id: number) {
        return api.delete(`/subscriptions/${id}`)
    }

    static async getAllSubscriptions() {
        return api.get('/admin/subscriptions')
    }
}