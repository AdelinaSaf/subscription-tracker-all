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

        <a-button
            type="primary"
            block
            html-type="submit"
            :disabled="!isFormValid"
        >
            Войти
        </a-button>
    </a-form>
</template>

<script setup lang="ts">
import { reactive, computed, watch } from 'vue';
import type { LoginRequest } from '../../common/types/auth/login/LoginRequest';

const emit = defineEmits<{
    submit: [payload: LoginRequest]
}>();

const form = reactive({
    email: '',
    password: ''
});

const touched = reactive({
    email: false,
    password: false
});

const errors = reactive({
    email: '',
    password: ''
});

const validate = () => {
    // email
    if (form.email) {
        if (!/\S+@\S+\.\S+/.test(form.email)) errors.email = 'Некорректный email';
        else errors.email = '';
    } else {
        errors.email = 'Введите email';
    }

    // password
    if (form.password) {
        if (form.password.length < 6) errors.password = 'Минимальная длина — 6 символов';
        else errors.password = '';
    } else {
        errors.password = 'Введите пароль';
    }
};

watch(form, validate, { deep: true });

const isFormValid = computed(() => {
    validate();
    return form.email && form.password && !errors.email && !errors.password;
});

const onSubmit = () => {
    touched.email = true;
    touched.password = true;
    validate();

    if (!isFormValid.value) return;

    emit('submit', { ...form });
};
</script>
