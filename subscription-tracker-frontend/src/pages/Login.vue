<template>
    <div class="flex justify-center mt-32">
        <a-card title="Вход" class="w-96">
            <LoginForm @submit="login" />
            <p class="text-center mt-4">
                Нет аккаунта? <router-link to="/register">Регистрация</router-link>
            </p>
        </a-card>
    </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router';
import { message } from 'ant-design-vue';
import { useUserStore } from '../stores/user';
import LoginForm from '../components/forms/LoginForm.vue';
import type { LoginRequest } from '../common/types/auth/login/LoginRequest';

const router = useRouter();
const userStore = useUserStore();

async function login(payload: LoginRequest) {
    try {
        await userStore.login(payload);
        message.success('Вы вошли в систему');
        router.push('/');
    } catch (e: any) {
        if (e.response?.status === 401) {
            message.error('Неверная почта или пароль');
        } else {
            // Для других ошибок показываем стандартное сообщение
            message.error('Не удалось войти: ' + (e.response?.data?.error || e.message));
        }
    }
}
</script>
