<template>
    <div class="notifications-tab">
        <div class="notifications-header">
            <div class="header-left">
                <h2>Уведомления</h2>
                <a-tag v-if="unreadCount > 0" color="blue">
                    {{ unreadCount }} непрочитанных
                </a-tag>
            </div>
            
            <div class="header-right">
                <a-space>
                    <a-button
                        :disabled="unreadCount === 0"
                        @click="markAllAsRead"
                    >
                        Отметить все как прочитанные
                    </a-button>
                    <a-button
                        type="primary"
                        @click="showNotificationSettings"
                    >
                        Настройки уведомлений
                    </a-button>
                </a-space>
            </div>
        </div>

        <a-tabs v-model:activeKey="filterTab">
            <a-tab-pane key="all" tab="Все уведомления" />
            <a-tab-pane key="unread" tab="Непрочитанные" />
            <a-tab-pane key="system" tab="Системные" />
            <a-tab-pane key="payment" tab="Платежи" />
        </a-tabs>

        <div class="notifications-list">
            <a-list
                :data-source="filteredNotifications"
                :loading="loading"
                item-layout="vertical"
            >
                <template #renderItem="{ item }">
                    <a-list-item
                        :class="{
                            'notification-item': true,
                            'unread': !item.isRead,
                            'has-subscription': !!item.subscription
                        }"
                        @click="handleNotificationClick(item)"
                    >
                        <template #actions>
                            <span class="notification-time">
                                {{ formatTimeAgo(item.createdAt) }}
                            </span>
                            
                            <a-space>
                                <a-button
                                    v-if="!item.isRead"
                                    type="link"
                                    size="small"
                                    @click.stop="markAsRead(item.id)"
                                >
                                    Прочитано
                                </a-button>
                                
                                <a-button
                                    v-if="item.subscription"
                                    type="link"
                                    size="small"
                                    @click.stop="goToSubscription(item.subscriptionId)"
                                >
                                    К подписке
                                </a-button>
                                
                                <a-button
                                    type="link"
                                    danger
                                    size="small"
                                    @click.stop="deleteNotification(item.id)"
                                >
                                    Удалить
                                </a-button>
                            </a-space>
                        </template>
                        
                        <a-list-item-meta>
                            <template #title>
                                <div class="notification-title">
                                    <span v-if="!item.isRead" class="unread-dot" />
                                    <span>{{ item.message }}</span>
                                    
                                    <a-tag
                                        v-if="item.type"
                                        size="small"
                                        :color="getNotificationTypeColor(item.type)"
                                    >
                                        {{ getNotificationTypeLabel(item.type) }}
                                    </a-tag>
                                    
                                    <span
                                        v-if="item.amount"
                                        class="notification-amount"
                                    >
                                        {{ formatCurrency(item.amount) }}
                                    </span>
                                </div>
                            </template>
                            
                            <template #description>
                                <div class="notification-description">
                                    <span v-if="item.subscription">
                                        Подписка: {{ item.subscription }}
                                    </span>
                                    <span v-else>
                                        Системное уведомление
                                    </span>
                                </div>
                            </template>
                        </a-list-item-meta>
                    </a-list-item>
                </template>
            </a-list>
        </div>

        <!-- Настройки уведомлений -->
        <a-modal
            v-model:open="settingsModalVisible"
            title="Настройки уведомлений"
            width="600px"
            @ok="saveNotificationSettings"
        >
            <a-form layout="vertical">
                <a-form-item label="Email уведомления">
                    <a-checkbox-group v-model:value="emailSettings">
                        <a-space direction="vertical">
                            <a-checkbox value="payment_reminder">
                                Напоминание о платежах
                            </a-checkbox>
                            <a-checkbox value="payment_success">
                                Успешный платеж
                            </a-checkbox>
                            <a-checkbox value="payment_failed">
                                Неудачный платеж
                            </a-checkbox>
                            <a-checkbox value="subscription_changes">
                                Изменения в подписках
                            </a-checkbox>
                            <a-checkbox value="system_announcements">
                                Системные объявления
                            </a-checkbox>
                        </a-space>
                    </a-checkbox-group>
                </a-form-item>
                
                <a-form-item label="За сколько дней напоминать о платеже">
                    <a-select
                        v-model:value="reminderDays"
                        style="width: 200px"
                    >
                        <a-select-option :value="1">За 1 день</a-select-option>
                        <a-select-option :value="3">За 3 дня</a-select-option>
                        <a-select-option :value="7">За неделю</a-select-option>
                    </a-select>
                </a-form-item>
                
                <a-divider />
                
                <a-form-item label="Push-уведомления">
                    <a-switch v-model:checked="pushEnabled" />
                    <span class="setting-hint">
                        Получать уведомления в браузере
                    </span>
                </a-form-item>
            </a-form>
        </a-modal>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import { formatDistanceToNow, format } from 'date-fns'
import { ru } from 'date-fns/locale'
import NotificationApi from '../api/notification'
import type { Notification } from ../common/types/notification/Notification'

const loading = ref(false)
const notifications = ref<Notification[]>([])
const filterTab = ref('all')
const settingsModalVisible = ref(false)

// Настройки
const emailSettings = ref<string[]>([])
const reminderDays = ref(3)
const pushEnabled = ref(true)

// Загружаем уведомления
const loadNotifications = async () => {
    loading.value = true
    try {
        const response = await NotificationApi.getNotifications()
        notifications.value = response.data
    } catch (error) {
        message.error('Ошибка загрузки уведомлений')
    } finally {
        loading.value = false
    }
}

// Счетчик непрочитанных
const unreadCount = computed(() => {
    return notifications.value.filter(n => !n.isRead).length
})

// Фильтрованные уведомления
const filteredNotifications = computed(() => {
    let filtered = [...notifications.value]
    
    switch (filterTab.value) {
        case 'unread':
            filtered = filtered.filter(n => !n.isRead)
            break
        case 'system':
            filtered = filtered.filter(n => n.type === 'system')
            break
        case 'payment':
            filtered = filtered.filter(n => n.type === 'payment')
            break
    }
    
    return filtered.sort((a, b) => 
        new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
    )
})

// Форматирование времени
const formatTimeAgo = (dateString: string) => {
    try {
        const date = new Date(dateString)
        return formatDistanceToNow(date, { 
            addSuffix: true,
            locale: ru 
        })
    } catch {
        return dateString
    }
}

// Форматирование валюты
const formatCurrency = (amount: number, currency: string = 'RUB') => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: currency
    }).format(amount)
}

// Получение цвета для типа уведомления
const getNotificationTypeColor = (type: string) => {
    const colors: Record<string, string> = {
        'system': 'blue',
        'payment': 'green',
        'reminder': 'orange',
        'warning': 'red'
    }
    return colors[type] || 'default'
}

// Получение метки для типа уведомления
const getNotificationTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
        'system': 'Системное',
        'payment': 'Платеж',
        'reminder': 'Напоминание',
        'warning': 'Предупреждение'
    }
    return labels[type] || type
}

// Обработка клика по уведомлению
const handleNotificationClick = (notification: Notification) => {
    if (!notification.isRead) {
        markAsRead(notification.id)
    }
}

// Пометить как прочитанное
const markAsRead = async (id: number) => {
    try {
        await NotificationApi.markAsRead(id)
        
        // Обновляем локально
        const index = notifications.value.findIndex(n => n.id === id)
        if (index !== -1) {
            notifications.value[index].isRead = true
        }
        
        message.success('Уведомление помечено как прочитанное')
    } catch (error) {
        message.error('Ошибка обновления уведомления')
    }
}

// Пометить все как прочитанные
const markAllAsRead = async () => {
    try {
        const unreadIds = notifications.value
            .filter(n => !n.isRead)
            .map(n => n.id)
        
        for (const id of unreadIds) {
            await NotificationApi.markAsRead(id)
        }
        
        // Обновляем локально
        notifications.value.forEach(n => { n.isRead = true })
        
        message.success(`Отмечено ${unreadIds.length} уведомлений как прочитанные`)
    } catch (error) {
        message.error('Ошибка обновления уведомлений')
    }
}

// Удалить уведомление
const deleteNotification = async (id: number) => {
    try {
        await NotificationApi.deleteNotification(id)
        
        // Удаляем локально
        notifications.value = notifications.value.filter(n => n.id !== id)
        
        message.success('Уведомление удалено')
    } catch (error) {
        message.error('Ошибка удаления уведомления')
    }
}

// Перейти к подписке
const goToSubscription = (subscriptionId: number) => {
    // Навигация к подписке
    console.log('Go to subscription:', subscriptionId)
}

// Показать настройки
const showNotificationSettings = () => {
    // Загружаем текущие настройки
    loadNotificationSettings()
    settingsModalVisible.value = true
}

// Загрузить настройки
const loadNotificationSettings = async () => {
    try {
        const response = await NotificationApi.getSettings()
        emailSettings.value = response.data.emailSettings || []
        reminderDays.value = response.data.reminderDays || 3
        pushEnabled.value = response.data.pushEnabled !== false
    } catch (error) {
        console.error('Ошибка загрузки настроек', error)
    }
}

// Сохранить настройки
const saveNotificationSettings = async () => {
    try {
        await NotificationApi.updateSettings({
            emailSettings: emailSettings.value,
            reminderDays: reminderDays.value,
            pushEnabled: pushEnabled.value
        })
        
        message.success('Настройки сохранены')
        settingsModalVisible.value = false
    } catch (error) {
        message.error('Ошибка сохранения настроек')
    }
}

onMounted(() => {
    loadNotifications()
})
</script>

<style scoped>
.notifications-tab {
    background: white;
    border-radius: 8px;
    padding: 24px;
}

.notifications-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-left h2 {
    margin: 0;
}

.notifications-list {
    margin-top: 20px;
}

.notification-item {
    padding: 16px;
    border-radius: 6px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.notification-item:hover {
    background: #fafafa;
}

.notification-item.unread {
    background: #f0f7ff;
    border-left: 3px solid #1890ff;
}

.unread-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    background: #1890ff;
    border-radius: 50%;
    margin-right: 8px;
}

.notification-title {
    display: flex;
    align-items: center;
    gap: 8px;
}

.notification-amount {
    font-weight: 600;
    color: #52c41a;
    margin-left: auto;
}

.notification-description {
    color: rgba(0, 0, 0, 0.45);
    font-size: 12px;
}

.notification-time {
    color: rgba(0, 0, 0, 0.45);
    font-size: 12px;
}

.setting-hint {
    margin-left: 8px;
    color: rgba(0, 0, 0, 0.45);
    font-size: 12px;
}
</style>