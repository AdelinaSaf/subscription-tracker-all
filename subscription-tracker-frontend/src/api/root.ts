import { api } from './index'

export default class RootApi {
    static async getAdmins() {
        return api.get('/root/admins')
    }

    static async createAdmin(email: string, password: string) {
        return api.post('/root/admins', { email, password })
    }

    static async deleteAdmin(id: number) {
        return api.delete(`/root/admins/${id}`)
    }
}