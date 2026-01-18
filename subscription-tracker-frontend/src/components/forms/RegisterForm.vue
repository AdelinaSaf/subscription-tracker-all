<template>
    <a-form layout="vertical" @submit.prevent="onSubmit">
        <a-form-item
            label="Электронная почта"
            :validate-status="touched.email && errors.email ? 'error' : ''"
            :help="touched.email ? errors.email : ''"
        >
            <a-input
                v-model:value="form.email"
                placeholder="Введите email"
                @blur="touched.email = true"
            />
        </a-form-item>

        <a-form-item
            label="Пароль"
            :validate-status="touched.password && errors.password ? 'error' : ''"
            :help="touched.password ? errors.password : ''"
        >
            <a-input-password
                v-model:value="form.password"
                placeholder="Введите пароль"
                @blur="touched.password = true"
            />
        </a-form-item>

        <a-form-item
            label="Подтверждение пароля"
            :validate-status="touched.password_confirm && errors.password_confirm ? 'error' : ''"
            :help="touched.password_confirm ? errors.password_confirm : ''"
        >
            <a-input-password
                v-model:value="form.password_confirm"
                placeholder="Повторите пароль"
                @blur="touched.password_confirm = true"
            />
        </a-form-item>

        <a-form-item
            label="Часовой пояс"
            :validate-status="touched.timezone && errors.timezone ? 'error' : ''"
            :help="touched.timezone ? errors.timezone : ''"
        >
            <a-select
                v-model:value="form.timezone"
                placeholder="Выберите часовой пояс"
                @blur="touched.timezone = true"
            >
                <a-select-option v-for="tz in timezones" :key="tz" :value="tz">
                    {{ tz }}
                </a-select-option>
            </a-select>
        </a-form-item>

        <a-button
            type="primary"
            block
            html-type="submit"
            :disabled="!isFormValid"
        >
            Зарегистрироваться
        </a-button>
    </a-form>
</template>

<script setup lang="ts">
import { reactive, computed, watch, defineEmits } from 'vue';
import type {RegisterRequest} from "../../common/types/auth/register/RegisterRequest.ts";


const timezones = ['UTC', 'Europe/Moscow', 'America/New_York', 'Asia/Tokyo', 'Europe/London'];
const commonPasswords = ['password', '123456', '123456789', 'qwerty', '111111'];

const emit = defineEmits<{
    submit: [payload: RegisterRequest];
}>();

const form = reactive({
    email: '',
    password: '',
    password_confirm: '',
    timezone: 'UTC'
});

const touched = reactive({
    email: false,
    password: false,
    password_confirm: false,
    timezone: false
});

const errors = reactive({
    email: '',
    password: '',
    password_confirm: '',
    timezone: ''
});

const validate = () => {
    if (form.email && !/\S+@\S+\.\S+/.test(form.email)) errors.email = 'Некорректный email';
    else errors.email = '';

    if (form.password) {
        if (form.password.length < 6) errors.password = 'Пароль должен быть минимум 6 символов';
        else if (commonPasswords.includes(form.password.toLowerCase())) errors.password = 'Пароль слишком простой';
        else if (!/[A-Za-z]/.test(form.password) || !/\d/.test(form.password) || !/[\W_]/.test(form.password)) {
            errors.password = 'Пароль должен содержать буквы, цифры и спецсимволы';
        } else errors.password = '';
    } else errors.password = '';

    if (form.password_confirm && form.password_confirm !== form.password) errors.password_confirm = 'Пароли не совпадают';
    else errors.password_confirm = '';

    errors.timezone = form.timezone ? '' : 'Часовой пояс обязателен';
};

const isFormValid = computed(() => {
    validate();
    return (
        form.email &&
        form.password &&
        form.password_confirm &&
        form.timezone &&
        !errors.email &&
        !errors.password &&
        !errors.password_confirm &&
        !errors.timezone
    );
});

watch(form, validate, { deep: true });

const onSubmit = async () => {
    Object.keys(touched).forEach(k => touched[k] = true);
    validate();
    if (!isFormValid.value) return;

    emit('submit', { ...form });
};
</script>
