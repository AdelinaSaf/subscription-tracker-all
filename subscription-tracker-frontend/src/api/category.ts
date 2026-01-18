import { api } from './index'
import type { CreateCategoryDto } from '../common/types/category/Category'

export default class CategoryApi {
    static async getCategories() {
        return api.get('/categories')
    }

    static async createCategory(payload: CreateCategoryDto) {
        return api.post('/categories', payload)
    }
}