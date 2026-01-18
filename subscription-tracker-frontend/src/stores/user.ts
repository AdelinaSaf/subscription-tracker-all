import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import AuthApi from '../api/auth';
import type { LoginRequest } from '../common/types/auth/login/LoginRequest';
import { RolesEnum } from '../common/consts/Roles.ts';
import type {User} from "../common/types/user/User.ts";

export const useUserStore = defineStore('user', () => {
    const user = ref<User | null>(null);
    const isAuthenticated = ref(false);
    const role = ref<RolesEnum>(RolesEnum.ROLE_USER);

    const hasRole = (roleName: string) => {
        return user.value?.roles?.includes(roleName) || false;
    };
    
    const hasAdminRole = computed(() => hasRole('ROLE_ADMIN'));
    const hasRootRole = computed(() => hasRole('ROLE_ROOT'));
    const isAdmin = computed(() => hasRole('ROLE_ADMIN'));
    const isRoot = computed(() => hasRole('ROLE_ROOT'));
    
    // Добавляем токен для удобства
    const token = ref<string | null>(localStorage.getItem('token'));
    
    const userInitials = computed(() => {
        const email = user.value?.email;
        if (email) {
            // Безопасное извлечение первого символа до @
            const atIndex = email.indexOf('@');
            if (atIndex > 0) {
                // Берем первый символ до @
                return email.charAt(0).toUpperCase();
            }
            // Если почему-то нет @, берем первый символ email
            return email.charAt(0).toUpperCase();
        }
        return 'U'; // По умолчанию
    });

    const router = useRouter();

    const login = async (payload: LoginRequest) => {
        try {
            const { data } = await AuthApi.login(payload);
            token.value = data.token;
            localStorage.setItem('token', data.token);
            await fetchMe();
            await router.replace('/');
        } catch (error) {
            // В случае ошибки очищаем
            clearAuth();
            throw error;
        }
    };

    const logout = async () => {
        clearAuth();
        await router.replace('/login');
    };

    const fetchMe = async () => {
        try {
            const { data } = await AuthApi.me();
            user.value = data;
            role.value = data.roles[0] || RolesEnum.ROLE_USER;
            isAuthenticated.value = true;
        } catch {
            clearAuth();
            throw new Error('Ошибка получения данных пользователя');
        }
    };

    const clearAuth = () => {
        token.value = null;
        localStorage.removeItem('token');
        $reset();
    };

    const $reset = () => {
        user.value = null;
        role.value = RolesEnum.ROLE_USER;
        isAuthenticated.value = false;
    };

    return {
        user,
        role,
        isAuthenticated,
        token,
        userInitials,
        hasAdminRole,
        hasRootRole,
        isAdmin,
        isRoot,
        hasRole,
        login,
        logout,
        fetchMe,
        clearAuth,
        $reset,
    };
});