import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '../stores/user'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        // Главный маршрут с ProtectedLayout для всех защищенных страниц
        {
            path: '/',
            component: () => import('../layouts/ProtectedLayout.vue'),
            meta: { requiresAuth: true },
            children: [
                {
                    path: '',
                    name: 'Home',
                    component: () => import('../pages/Home.vue'),
                    meta: { title: 'Главная' }
                },
                {
                    path: 'subscriptions',
                    name: 'Subscriptions',
                    component: () => import('../pages/Subscriptions.vue'),
                    meta: { title: 'Мои подписки' }
                },
                {
                    path: 'calendar',
                    name: 'Calendar',
                    component: () => import('../pages/Calendar.vue'),
                    meta: { title: 'Календарь' }
                },
                {
                    path: 'profile',
                    name: 'Profile',
                    component: () => import('../pages/Profile.vue'),
                    meta: { title: 'Профиль' }
                },
                {
                    path: 'admin/users',
                    name: 'AdminUsers',
                    component: () => import('../pages/admin/Users.vue'),
                    meta: { title: 'Пользователи', requiresAdmin: true }
                },
                // {
                //     path: 'admin/subscriptions',
                //     name: 'AdminSubscriptions',
                //     component: () => import('../pages/admin/Subscriptions.vue'),
                //     meta: { title: 'Все подписки', requiresAdmin: true }
                // },
                // // Root маршруты
                // {
                //     path: 'root/admins',
                //     name: 'RootAdmins',
                //     component: () => import('../pages/root/Admins.vue'),
                //     meta: { title: 'Администраторы', requiresRoot: true }
                // }
            ]
        },
        // Публичные маршруты (без авторизации)
        {
            path: '/login',
            name: 'Login',
            component: () => import('../pages/Login.vue'),
            meta: { requiresAuth: false, title: 'Вход' }
        },
        {
            path: '/register',
            name: 'Register',
            component: () => import('../pages/Register.vue'),
            meta: { requiresAuth: false, title: 'Регистрация' }
        },
        // 404 страница
        {
            path: '/:pathMatch(.*)*',
            name: 'NotFound',
            component: () => import('../pages/NotFound.vue'),
            meta: { title: 'Страница не найдена' }
        }
    ]
})

router.beforeEach(async (to, from, next) => {
    const userStore = useUserStore()
    const requiresAuth = to.meta.requiresAuth
    const requiresAdmin = to.meta.requiresAdmin
    const requiresRoot = to.meta.requiresRoot

    // Устанавливаем заголовок страницы
    document.title = to.meta.title ? `${to.meta.title} | Subscription Tracker` : 'Subscription Tracker'

    // Если есть токен в localStorage, но пользователь не авторизован
    if (!userStore.isAuthenticated && localStorage.getItem('token')) {
        try {
            await userStore.fetchMe()
        } catch (error) {
            // Если ошибка при получении данных пользователя, очищаем
            userStore.clearAuth()
        }
    }

    // Если маршрут требует авторизации, а пользователь не авторизован
    if (requiresAuth && !userStore.isAuthenticated) {
        next('/login')
        return
    }

    // Проверка на админские права
    if (requiresAdmin) {
        const isAdmin = userStore.user?.roles?.includes('ROLE_ADMIN') || 
                       userStore.user?.roles?.includes('ROLE_ROOT')
        if (!isAdmin) {
            // message.error('У вас нет прав для доступа к этой странице')
            next('/')
            return
        }
    }

    // Проверка на root права
    if (requiresRoot) {
        const isRoot = userStore.user?.roles?.includes('ROLE_ROOT')
        if (!isRoot) {
            // message.error('У вас нет прав для доступа к этой странице')
            next('/')
            return
        }
    }

    // Если пользователь авторизован и пытается зайти на публичные страницы логина/регистрации
    if (userStore.isAuthenticated && (to.path === '/login' || to.path === '/register')) {
        next('/')
        return
    }

    next()
})

// Глобальный хук после навигации для скролла вверх
router.afterEach((to, from) => {
    window.scrollTo(0, 0)
})

export default router