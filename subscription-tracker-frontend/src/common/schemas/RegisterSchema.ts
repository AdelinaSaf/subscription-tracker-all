import { toTypedSchema } from '@vee-validate/zod';
import * as z from 'zod';

const commonPasswords = ['password', '123456', '123456789', 'qwerty', '111111'];

export const registerSchema = toTypedSchema(
    z.object({
        email: z
            .string()
            .min(1, 'Email не может быть пустым')
            .email('Некорректный email'),
        password: z
            .string()
            .min(6, 'Пароль должен быть минимум 6 символов')
            .refine(
                val => /[A-Za-z]/.test(val) && /\d/.test(val) && /[\W_]/.test(val),
                'Пароль должен содержать буквы, цифры и спецсимволы'
            )
            .refine(val => !commonPasswords.includes(val.toLowerCase()), 'Слишком простой пароль'),
        password_confirm: z.string().min(1, 'Подтверждение пароля обязательно'),
        timezone: z.string().min(1, 'Часовой пояс обязателен'),
    }).refine(data => data.password === data.password_confirm, {
        message: 'Пароли не совпадают',
        path: ['password_confirm'],
    })
);
