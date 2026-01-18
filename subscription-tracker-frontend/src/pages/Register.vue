<template>
    <div class="flex justify-center mt-32">
        <a-card title="Регистрация" class="w-96">
            <RegisterForm @submit="register" />
            <p class="mt-4 text-center">
                Уже есть аккаунт? <router-link to="/login">Войти</router-link>
            </p>
        </a-card>
    </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import RegisterForm from '../components/forms/RegisterForm.vue'
import AuthApi from '../api/auth'
import type { RegisterRequest } from '../common/types/auth/register/RegisterRequest'

const router = useRouter()

async function register(payload: RegisterRequest) {
    try {
        const { data } = await AuthApi.register(payload)
        message.success(data.message || 'Пользователь успешно зарегистрирован')
        router.push('/login')
    } catch (e: any) {
        message.error(e.response?.data?.error || e.message)
        console.error('Регистрация не удалась', e)
    }
}
</script>
