<template>
    <div class="home-page">
        <!-- Переключатель режима для администратора -->
        <div v-if="userStore.hasAdminRole" class="mode-switch">
            <a-radio-group v-model:value="dashboardMode" button-style="solid" size="large">
                <a-radio-button value="user">Мои подписки</a-radio-button>
                <a-radio-button value="admin">Администрирование</a-radio-button>
            </a-radio-group>
        </div>

        <!-- Пользовательский режим -->
        <div v-if="dashboardMode === 'user'">
            <a-row :gutter="[24, 24]">
                <!-- Статистика -->
                <a-col :span="24">
                    <a-card title="Общая статистика">
                        <a-row :gutter="16">
                            <a-col :span="6">
                                <a-statistic title="Всего подписок" :value="userStats.total" />
                            </a-col>
                            <a-col :span="6">
                                <a-statistic title="Активных подписок" :value="userStats.active" />
                            </a-col>
                            <a-col :span="6">
                                <a-statistic 
                                    title="Следующий платеж" 
                                    :value="userStats.nextPayment || 'Нет'"
                                />
                            </a-col>
                            <a-col :span="6">
                                <a-statistic 
                                    title="Общая сумма в месяц" 
                                    :value="formatCurrency(userStats.monthlyTotal)" 
                                />
                            </a-col>
                        </a-row>
                    </a-card>
                </a-col>

                <!-- Ближайшие платежи -->
                <a-col :span="12">
                    <a-card title="Ближайшие платежи" class="upcoming-payments-card">
                        <a-list
                            :data-source="upcomingPayments"
                            :loading="loading"
                        >
                            <template #renderItem="{ item }">
                                <a-list-item>
                                    <a-list-item-meta
                                        :title="item.name"
                                        :description="`${formatCurrency(item.price)} ${item.currency} - ${formatPeriodicity(item.periodicity)}`"
                                    />
                                    <template #actions>
                                        <span>{{ formatDate(item.nextPaymentDate) }}</span>
                                    </template>
                                </a-list-item>
                            </template>
                        </a-list>
                    </a-card>
                </a-col>

                <!-- Уведомления -->
                <a-col :span="12">
                    <a-card title="Уведомления" class="notifications-card">
                        <div class="notifications-header">
                            <a-button 
                                type="link" 
                                @click="markAllAsRead"
                                :disabled="!unreadNotificationsCount"
                            >
                                Отметить все как прочитанные
                            </a-button>
                        </div>
                        <a-list
                            :data-source="notifications"
                            :loading="loading"
                        >
                            <template #renderItem="{ item }">
                                <a-list-item :class="{ 'unread-notification': !item.isRead }">
                                    <a-list-item-meta
                                        :title="item.message"
                                        :description="`${formatDateTime(item.createdAt)} • ${item.subscription || 'Система'}`"
                                    />
                                    <template #actions>
                                        <a-button 
                                            v-if="!item.isRead" 
                                            type="link" 
                                            size="small"
                                            @click="markAsRead(item.id)"
                                        >
                                            Прочитано
                                        </a-button>
                                    </template>
                                </a-list-item>
                            </template>
                        </a-list>
                    </a-card>
                </a-col>

                <!-- Последние платежи -->
                <a-col :span="24">
                    <a-card title="История платежей" class="recent-payments-card">
                        <div class="table-container">
                            <a-table 
                                :data-source="recentPayments"
                                :columns="paymentColumns"
                                :loading="loading"
                                :pagination="{ pageSize: 5 }"
                            >
                                <template #bodyCell="{ column, record }">
                                    <template v-if="column.key === 'amount'">
                                        {{ formatCurrency(record.amount, record.currency) }}
                                    </template>
                                    <template v-if="column.key === 'paymentDate'">
                                        {{ formatDate(record.paymentDate) }}
                                    </template>
                                    <template v-if="column.key === 'status'">
                                        <a-tag :color="getStatusColor(record.status)">
                                            {{ record.status }}
                                        </a-tag>
                                    </template>
                                </template>
                            </a-table>
                        </div>
                    </a-card>
                </a-col>
            </a-row>
        </div>

        <!-- Административный режим -->
        <div v-if="dashboardMode === 'admin' && userStore.hasAdminRole">
            <a-row :gutter="[24, 24]">
                <!-- Административная статистика -->
                <a-col :span="24">
                    <a-card title="Системная статистика">
                        <a-row :gutter="16">
                            <a-col :span="6">
                                <a-statistic title="Всего пользователей" :value="adminStats.totalUsers" />
                            </a-col>
                            <a-col :span="6">
                                <a-statistic title="Всего подписок" :value="adminStats.totalSubscriptions" />
                            </a-col>
                            <a-col :span="6">
                                <a-statistic title="Активных подписок" :value="adminStats.activeSubscriptions" />
                            </a-col>
                            <a-col :span="6">
                                <a-statistic 
                                    title="Выручка за месяц" 
                                    :value="formatCurrency(adminStats.monthlyRevenue)" 
                                />
                            </a-col>
                        </a-row>
                    </a-card>
                </a-col>

                <!-- Быстрые действия -->
                <a-col :span="24">
                    <a-card title="Быстрые действия">
                        <a-space :size="16">
                            <a-button type="primary" @click="showUserManagement">
                                <template #icon><UserOutlined /></template>
                                Управление пользователями
                            </a-button>
                            <a-button @click="showAllSubscriptions">
                                <template #icon><UnorderedListOutlined /></template>
                                Все подписки
                            </a-button>
                            <a-button @click="refreshAdminData">
                                <template #icon><ReloadOutlined /></template>
                                Обновить данные
                            </a-button>
                        </a-space>
                    </a-card>
                </a-col>

                <!-- Последние зарегистрированные пользователи -->
                <a-col :span="12">
                    <a-card title="Последние пользователи" :loading="adminLoading">
                        <a-list
                            :data-source="recentUsers"
                            :loading="adminLoading"
                        >
                            <template #renderItem="{ item }">
                                <a-list-item>
                                    <a-list-item-meta
                                        :title="item.email"
                                        :description="item.roles.join(', ') + (item.timezone ? ` • ${item.timezone}` : '')"
                                    />
                                    <template #actions>
                                        <a-button type="link" @click="viewUserDetails(item)">
                                            Детали
                                        </a-button>
                                    </template>
                                </a-list-item>
                            </template>
                        </a-list>
                    </a-card>
                </a-col>

                <!-- Системные подписки -->
                <a-col :span="12">
                    <a-card title="Недавние подписки" :loading="adminLoading">
                        <a-table 
                            :data-source="adminSubscriptions"
                            :columns="adminSubscriptionColumns"
                            :loading="adminLoading"
                            :pagination="false"
                            size="small"
                        >
                            <template #bodyCell="{ column, record }">
                                <template v-if="column.key === 'user'">
                                    {{ record.user?.email || 'Нет данных' }}
                                </template>
                                <template v-if="column.key === 'price'">
                                    {{ formatCurrency(record.price, record.currency) }}
                                </template>
                                <template v-if="column.key === 'nextPaymentDate'">
                                    {{ formatDate(record.nextPaymentDate) }}
                                </template>
                                <template v-if="column.key === 'active'">
                                    <a-tag :color="record.active ? 'green' : 'red'">
                                        {{ record.active ? 'Активна' : 'Неактивна' }}
                                    </a-tag>
                                </template>
                            </template>
                        </a-table>
                    </a-card>
                </a-col>

                <!-- Системные платежи -->
                <a-col :span="24">
                    <a-card title="Последние платежи в системе" :loading="adminLoading">
                        <div class="table-container">
                            <a-table 
                                :data-source="adminRecentPayments"
                                :columns="adminPaymentColumns"
                                :loading="adminLoading"
                                :pagination="{ pageSize: 5 }"
                            >
                                <template #bodyCell="{ column, record }">
                                    <template v-if="column.key === 'user'">
                                        {{ record.user?.email || 'Нет данных' }}
                                    </template>
                                    <template v-if="column.key === 'amount'">
                                        {{ formatCurrency(record.amount, record.currency) }}
                                    </template>
                                    <template v-if="column.key === 'paymentDate'">
                                        {{ formatDateTime(record.paymentDate) }}
                                    </template>
                                    <template v-if="column.key === 'status'">
                                        <a-tag :color="getStatusColor(record.status)">
                                            {{ record.status }}
                                        </a-tag>
                                    </template>
                                </template>
                            </a-table>
                        </div>
                    </a-card>
                </a-col>
            </a-row>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { message, Modal } from 'ant-design-vue'
import { 
    UserOutlined, 
    UnorderedListOutlined, 
    ReloadOutlined 
} from '@ant-design/icons-vue'
import SubscriptionApi from '../api/subscription'
import NotificationApi from '../api/notification'
import PaymentApi from '../api/payment'
import AdminApi from '../api/admin'
import { useUserStore } from '../stores/user'
import type { Subscription } from '../common/types/subscription/Subscription'
import type { Notification } from '../common/types/notification/Notification'
import type { Payment } from '../common/types/payment/Payment'

const userStore = useUserStore()
const loading = ref(false)
const adminLoading = ref(false)
const dashboardMode = ref('user')

// Пользовательские данные
const subscriptions = ref<Subscription[]>([])
const notifications = ref<Notification[]>([])
const payments = ref<Payment[]>([])

// Административные данные
const adminUsers = ref<any[]>([])
const adminSubscriptions = ref<any[]>([])
const adminRecentPayments = ref<any[]>([])
const recentUsers = ref<any[]>([])

// Статистика пользователя
const userStats = ref({
    total: 0,
    active: 0,
    nextPayment: '',
    monthlyTotal: 0
})

// Статистика администратора
const adminStats = ref({
    totalUsers: 0,
    totalSubscriptions: 0,
    activeSubscriptions: 0,
    monthlyRevenue: 0
})

// Ближайшие платежи пользователя
const upcomingPayments = ref<Subscription[]>([])

// Последние платежи пользователя
const recentPayments = ref<Payment[]>([])

// Счетчик непрочитанных уведомлений
const unreadNotificationsCount = computed(() => {
    return notifications.value.filter(n => !n.isRead).length
})

// Колонки для платежей пользователя
const paymentColumns = [
    {
        title: 'Подписка',
        dataIndex: 'subscription',
        key: 'subscription'
    },
    {
        title: 'Сумма',
        key: 'amount'
    },
    {
        title: 'Дата',
        key: 'paymentDate'
    },
    {
        title: 'Статус',
        key: 'status'
    }
]

// Колонки для административных подписок
const adminSubscriptionColumns = [
    {
        title: 'Название',
        dataIndex: 'name',
        key: 'name'
    },
    {
        title: 'Пользователь',
        key: 'user'
    },
    {
        title: 'Цена',
        key: 'price'
    },
    {
        title: 'Следующий платеж',
        key: 'nextPaymentDate'
    },
    {
        title: 'Статус',
        key: 'active'
    }
]

// Колонки для административных платежей
const adminPaymentColumns = [
    {
        title: 'Пользователь',
        key: 'user'
    },
    {
        title: 'Подписка',
        dataIndex: 'subscription',
        key: 'subscription'
    },
    {
        title: 'Сумма',
        key: 'amount'
    },
    {
        title: 'Дата',
        key: 'paymentDate'
    },
    {
        title: 'Статус',
        key: 'status'
    }
]

// Загрузка данных пользователя
const loadUserData = async () => {
    loading.value = true
    try {
        // Загружаем подписки
        const subsResponse = await SubscriptionApi.getSubscriptions()
        subscriptions.value = subsResponse.data
        
        // Загружаем уведомления
        const notifResponse = await NotificationApi.getNotifications()
        notifications.value = notifResponse.data
        
        // Загружаем платежи
        const paymentsResponse = await PaymentApi.getPayments()
        payments.value = paymentsResponse.data
        
        // Рассчитываем статистику
        calculateUserStats()
        
        // Ближайшие платежи
        upcomingPayments.value = [...subscriptions.value]
            .filter(s => s.active)
            .sort((a, b) => new Date(a.nextPaymentDate).getTime() - new Date(b.nextPaymentDate).getTime())
            .slice(0, 5)
        
        // Последние платежи
        recentPayments.value = [...payments.value]
            .sort((a, b) => new Date(b.paymentDate).getTime() - new Date(a.paymentDate).getTime())
            .slice(0, 10)
            
    } catch (error) {
        message.error('Ошибка загрузки данных')
        console.error(error)
    } finally {
        loading.value = false
    }
}

// Загрузка административных данных
const loadAdminData = async () => {
    adminLoading.value = true
    try {
        // Загружаем пользователей
        const usersResponse = await AdminApi.getUsers()
        adminUsers.value = usersResponse.data
        recentUsers.value = usersResponse.data.slice(0, 5)
        
        // Загружаем все подписки
        const subsResponse = await AdminApi.getAllSubscriptions()
        adminSubscriptions.value = subsResponse.data.slice(0, 10)
        
        // Загружаем платежи (нужен отдельный эндпоинт для администратора)
        try {
            const paymentsResponse = await PaymentApi.getPayments()
            adminRecentPayments.value = paymentsResponse.data.slice(0, 10)
        } catch (error) {
            console.warn('Административные платежи недоступны:', error)
        }
        
        // Рассчитываем статистику
        calculateAdminStats()
        
    } catch (error) {
        message.error('Ошибка загрузки административных данных')
        console.error(error)
    } finally {
        adminLoading.value = false
    }
}

// Рассчет статистики пользователя
const calculateUserStats = () => {
    const total = subscriptions.value.length
    const active = subscriptions.value.filter(s => s.active).length
    
    // Находим ближайшую дату платежа
    let nextPayment = ''
    const activeSubs = subscriptions.value.filter(s => s.active)
    if (activeSubs.length > 0) {
        const nearest = activeSubs.reduce((prev, current) => 
            new Date(prev.nextPaymentDate) < new Date(current.nextPaymentDate) ? prev : current
        )
        nextPayment = formatDate(nearest.nextPaymentDate)
    }
    
    // Сумма в месяц для активных подписок
    const monthlyTotal = activeSubs.reduce((sum, sub) => {
        if (sub.periodicity === 'month') {
            return sum + sub.price
        } else if (sub.periodicity === 'year') {
            return sum + (sub.price / 12)
        } else if (sub.periodicity === 'week') {
            return sum + (sub.price * 4.33)
        }
        return sum
    }, 0)
    
    userStats.value = { total, active, nextPayment, monthlyTotal }
}

// Рассчет административной статистики
const calculateAdminStats = () => {
    const totalUsers = adminUsers.value.length
    const totalSubscriptions = adminSubscriptions.value.length
    const activeSubscriptions = adminSubscriptions.value.filter(s => s.active).length
    
    // Примерная выручка за месяц (нужно будет доработать с реальными данными)
    const monthlyRevenue = adminSubscriptions.value
        .filter(s => s.active)
        .reduce((sum, sub) => {
            if (sub.periodicity === 'month') {
                return sum + sub.price
            } else if (sub.periodicity === 'year') {
                return sum + (sub.price / 12)
            }
            return sum
        }, 0)
    
    adminStats.value = {
        totalUsers,
        totalSubscriptions,
        activeSubscriptions,
        monthlyRevenue
    }
}

// Форматирование даты
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}

// Форматирование даты и времени
const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Форматирование валюты
const formatCurrency = (amount: number, currency: string = 'RUB') => {
    const formatter = new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: currency || 'RUB',
        minimumFractionDigits: 2
    })
    return formatter.format(amount)
}

// Форматирование периодичности
const formatPeriodicity = (periodicity: string) => {
    const map: Record<string, string> = {
        'month': 'месяц',
        'year': 'год',
        'week': 'неделя',
        'day': 'день'
    }
    return map[periodicity] || periodicity
}

// Получение цвета для статуса
const getStatusColor = (status: string) => {
    const statusMap: Record<string, string> = {
        'успешно': 'green',
        'ожидание': 'yellow',
        'отклонено': 'red',
        'active': 'green',
        'inactive': 'red'
    }
    return statusMap[status.toLowerCase()] || 'blue'
}

// Отметить уведомление как прочитанное
const markAsRead = async (id: number) => {
    try {
        await NotificationApi.markAsRead(id)
        message.success('Уведомление помечено как прочитанное')
        
        // Обновляем локально
        const notification = notifications.value.find(n => n.id === id)
        if (notification) {
            notification.isRead = true
        }
    } catch (error) {
        message.error('Ошибка при обновлении уведомления')
    }
}

// Отметить все как прочитанные
const markAllAsRead = async () => {
    try {
        const unreadIds = notifications.value
            .filter(n => !n.isRead)
            .map(n => n.id)
        
        for (const id of unreadIds) {
            await NotificationApi.markAsRead(id)
        }
        
        // Обновляем локально
        notifications.value.forEach(n => {
            if (!n.isRead) n.isRead = true
        })
        
        message.success(`Отмечено ${unreadIds.length} уведомлений как прочитанные`)
    } catch (error) {
        message.error('Ошибка при обновлении уведомлений')
    }
}

// Показать управление пользователями
const showUserManagement = () => {
    Modal.info({
        title: 'Управление пользователями',
        content: 'Эта функция откроет полный интерфейс управления пользователями. В разработке.',
        okText: 'Понятно',
        width: 600
    })
}

// Показать все подписки
const showAllSubscriptions = () => {
    Modal.info({
        title: 'Все подписки в системе',
        content: `Всего подписок: ${adminStats.value.totalSubscriptions}, Активных: ${adminStats.value.activeSubscriptions}`,
        okText: 'Понятно',
        width: 800
    })
}

// Обновить административные данные
const refreshAdminData = async () => {
    await loadAdminData()
    message.success('Данные обновлены')
}

// Просмотр деталей пользователя
const viewUserDetails = (user: any) => {
    Modal.info({
        title: `Детали пользователя: ${user.email}`,
        content: `
            <div style="margin: 10px 0;">
                <p><strong>ID:</strong> ${user.id}</p>
                <p><strong>Роли:</strong> ${user.roles.join(', ')}</p>
                <p><strong>Часовой пояс:</strong> ${user.timezone}</p>
            </div>
        `,
        okText: 'Закрыть'
    })
}

// Следим за изменением режима
watch(dashboardMode, (newMode) => {
    if (newMode === 'admin' && userStore.hasAdminRole) {
        loadAdminData()
    } else {
        loadUserData()
    }
})

onMounted(() => {
    loadUserData()
    
    // Если пользователь - администратор, загружаем данные для администратора
    if (userStore.hasAdminRole) {
        loadAdminData()
    }
})
</script>

<style scoped>
.home-page {
    padding: 20px;
    min-height: calc(100vh - 64px);
    box-sizing: border-box;
}

.mode-switch {
    margin-bottom: 24px;
    text-align: center;
}

.ant-card {
    margin-bottom: 20px;
}

.upcoming-payments-card,
.notifications-card,
.recent-payments-card {
    height: 100%;
}

.notifications-header {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 16px;
}

.unread-notification {
    background-color: #f6ffed;
    border-left: 3px solid #52c41a;
    margin-left: -3px;
}

.table-container {
    max-height: 400px;
    overflow-y: auto;
}

/* Стили для скроллбара */
.table-container::-webkit-scrollbar {
    width: 6px;
}

.table-container::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.table-container::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.table-container::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Адаптивность */
@media (max-width: 768px) {
    .home-page {
        padding: 10px;
    }
    
    .ant-col {
        width: 100%;
        margin-bottom: 16px;
    }
}
</style>