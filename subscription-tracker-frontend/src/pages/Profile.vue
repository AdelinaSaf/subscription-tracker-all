<template>
    <div class="profile-page">
        <a-card title="Профиль пользователя">
            <a-form
                :model="formState"
                :rules="rules"
                layout="vertical"
                @finish="updateProfile"
            >
                <a-form-item label="Email" name="email">
                    <a-input
                        v-model:value="formState.email"
                        disabled
                    />
                </a-form-item>

                <a-form-item label="Часовой пояс" name="timezone">
                    <a-select
                        v-model:value="formState.timezone"
                        style="width: 100%"
                    >
                        <a-select-option value="UTC">UTC</a-select-option>
                        <a-select-option value="Europe/Moscow">Москва (Europe/Moscow)</a-select-option>
                        <a-select-option value="America/New_York">Нью-Йорк (America/New_York)</a-select-option>
                        <a-select-option value="Asia/Tokyo">Токио (Asia/Tokyo)</a-select-option>
                    </a-select>
                </a-form-item>

                <a-form-item>
                    <a-button type="primary" html-type="submit" :loading="loading">
                        Сохранить изменения
                    </a-button>
                </a-form-item>
            </a-form>
        </a-card>

        <a-card title="Смена пароля" style="margin-top: 24px">
            <a-form
                :model="passwordForm"
                :rules="passwordRules"
                layout="vertical"
                @finish="changePassword"
            >
                <a-form-item label="Текущий пароль" name="oldPassword">
                    <a-input-password
                        v-model:value="passwordForm.oldPassword"
                    />
                </a-form-item>

                <a-form-item label="Новый пароль" name="newPassword">
                    <a-input-password
                        v-model:value="passwordForm.newPassword"
                    />
                </a-form-item>

                <a-form-item label="Подтвердите новый пароль" name="confirmPassword">
                    <a-input-password
                        v-model:value="passwordForm.confirmPassword"
                    />
                </a-form-item>

                <a-form-item>
                    <a-button type="primary" html-type="submit" :loading="passwordLoading">
                        Сменить пароль
                    </a-button>
                </a-form-item>
            </a-form>
        </a-card>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import { useUserStore } from '../stores/user'
import AuthApi from '../api/auth'

const userStore = useUserStore()
const loading = ref(false)
const passwordLoading = ref(false)

const formState = reactive({
    email: '',
    timezone: 'UTC'
})

const passwordForm = reactive({
    oldPassword: '',
    newPassword: '',
    confirmPassword: ''
})

const rules = {
    timezone: [{ required: true, message: 'Выберите часовой пояс' }]
}

const passwordRules = {
    oldPassword: [{ required: true, message: 'Введите текущий пароль' }],
    newPassword: [{ required: true, message: 'Введите новый пароль' }],
    confirmPassword: [
        { required: true, message: 'Подтвердите новый пароль' },
        {
            validator: (_: any, value: string) => {
                if (value !== passwordForm.newPassword) {
                    return Promise.reject('Пароли не совпадают')
                }
                return Promise.resolve()
            }
        }
    ]
}

const loadProfile = async () => {
    try {
        const response = await AuthApi.me()
        formState.email = response.data.email
        formState.timezone = response.data.timezone || 'UTC'
    } catch (error) {
        message.error('Ошибка загрузки профиля')
    }
}

const updateProfile = async () => {
    loading.value = true
    try {
        // Здесь нужно вызвать API обновления профиля
        // Пока что используем текущий эндпоинт /api/me
        message.success('Профиль обновлен')
        await userStore.fetchMe() // Обновляем данные в хранилище
    } catch (error) {
        message.error('Ошибка обновления профиля')
    } finally {
        loading.value = false
    }
}

const changePassword = async () => {
    passwordLoading.value = true
    try {
        // Здесь нужно вызвать API смены пароля
        message.success('Пароль успешно изменен')
        Object.assign(passwordForm, {
            oldPassword: '',
            newPassword: '',
            confirmPassword: ''
        })
    } catch (error) {
        message.error('Ошибка при смене пароля')
    } finally {
        passwordLoading.value = false
    }
}

onMounted(() => {
    loadProfile()
})
</script>

<style scoped>
.profile-page {
    padding: 20px;
    max-width: 600px;
    margin: 0 auto;
}
</style>