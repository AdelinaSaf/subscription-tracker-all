<!-- src/components/payments/PaymentsTab.vue -->
<template>
    <div class="payments-tab">
        <div class="payments-header">
            <h2>Платежи</h2>
            
            <div class="header-actions">
                <a-space>
                    <!-- Фильтры -->
                    <a-range-picker
                        v-model:value="dateRange"
                        :format="'DD.MM.YYYY'"
                        @change="handleDateChange"
                    />
                    
                    <a-select
                        v-model:value="statusFilter"
                        placeholder="Статус"
                        style="width: 150px"
                        allow-clear
                    >
                        <a-select-option value="Успешно">Успешно</a-select-option>
                        <a-select-option value="Ошибка">Ошибка</a-select-option>
                        <a-select-option value="Ожидание">Ожидание</a-select-option>
                    </a-select>
                    
                    <!-- Экспорт -->
                    <a-button @click="exportPayments">
                        <template #icon><ExportOutlined /></template>
                        Экспорт
                    </a-button>
                    
                    <!-- Создать платеж (для админа) -->
                    <a-button
                        v-if="userStore.isAdmin"
                        type="primary"
                        @click="showCreatePaymentModal"
                    >
                        Создать платеж
                    </a-button>
                </a-space>
            </div>
        </div>

        <!-- Статистика -->
        <div class="payment-stats">
            <a-row :gutter="16">
                <a-col :span="6">
                    <a-statistic
                        title="Всего платежей"
                        :value="paymentStats.total"
                    />
                </a-col>
                <a-col :span="6">
                    <a-statistic
                        title="Успешных"
                        :value="paymentStats.successful"
                        :value-style="{ color: '#52c41a' }"
                    />
                </a-col>
                <a-col :span="6">
                    <a-statistic
                        title="Общая сумма"
                        :value="paymentStats.totalAmount"
                        :precision="2"
                    />
                </a-col>
                <a-col :span="6">
                    <a-statistic
                        title="Средний чек"
                        :value="paymentStats.averageAmount"
                        :precision="2"
                    />
                </a-col>
            </a-row>
        </div>

        <!-- Таблица платежей -->
        <div class="payments-table">
            <a-table
                :data-source="filteredPayments"
                :columns="paymentColumns"
                :loading="loading"
                :pagination="pagination"
                @change="handleTableChange"
                row-key="id"
            >
                <template #bodyCell="{ column, record }">
                    <!-- Дата платежа -->
                    <template v-if="column.key === 'paymentDate'">
                        {{ formatDate(record.paymentDate) }}
                    </template>
                    
                    <!-- Сумма -->
                    <template v-if="column.key === 'amount'">
                        <div class="amount-cell">
                            <span class="amount-value">
                                {{ formatCurrency(record.amount, record.currency) }}
                            </span>
                            <span v-if="record.currency !== 'RUB'" class="currency-badge">
                                {{ record.currency }}
                            </span>
                        </div>
                    </template>
                    
                    <!-- Статус -->
                    <template v-if="column.key === 'status'">
                        <a-tag :color="getStatusColor(record.status)">
                            {{ record.status }}
                        </a-tag>
                        <div
                            v-if="record.notes"
                            class="payment-notes"
                            :title="record.notes"
                        >
                            <InfoCircleOutlined /> {{ record.notes }}
                        </div>
                    </template>
                    
                    <!-- Подписка -->
                    <template v-if="column.key === 'subscription'">
                        <a-button
                            type="link"
                            @click="goToSubscription(record.subscriptionId)"
                        >
                            {{ record.subscription }}
                        </a-button>
                    </template>
                    
                    <!-- Пользователь (для админа) -->
                    <template v-if="column.key === 'user' && userStore.isAdmin">
                        {{ record.user?.email || 'Неизвестно' }}
                    </template>
                    
                    <!-- Действия -->
                    <template v-if="column.key === 'actions'">
                        <a-space>
                            <a-button
                                type="link"
                                size="small"
                                @click="showPaymentDetails(record)"
                            >
                                Детали
                            </a-button>
                            
                            <a-button
                                v-if="userStore.isAdmin && record.status !== 'Успешно'"
                                type="link"
                                size="small"
                                @click="retryPayment(record)"
                            >
                                Повторить
                            </a-button>
                            
                            <a-button
                                v-if="userStore.isAdmin"
                                type="link"
                                danger
                                size="small"
                                @click="deletePayment(record.id)"
                            >
                                Удалить
                            </a-button>
                        </a-space>
                    </template>
                </template>
            </a-table>
        </div>

        <!-- Модальное окно деталей платежа -->
        <a-modal
            v-model:open="detailsModalVisible"
            :title="`Платеж #${selectedPayment?.id || ''}`"
            width="600px"
            :footer="null"
        >
            <div v-if="selectedPayment" class="payment-details">
                <a-descriptions :column="1" bordered>
                    <a-descriptions-item label="Дата">
                        {{ formatDateTime(selectedPayment.paymentDate) }}
                    </a-descriptions-item>
                    
                    <a-descriptions-item label="Сумма">
                        {{ formatCurrency(selectedPayment.amount, selectedPayment.currency) }}
                    </a-descriptions-item>
                    
                    <a-descriptions-item label="Статус">
                        <a-tag :color="getStatusColor(selectedPayment.status)">
                            {{ selectedPayment.status }}
                        </a-tag>
                    </a-descriptions-item>
                    
                    <a-descriptions-item label="Подписка">
                        {{ selectedPayment.subscription }}
                    </a-descriptions-item>
                    
                    <a-descriptions-item v-if="selectedPayment.user && userStore.isAdmin" label="Пользователь">
                        {{ selectedPayment.user.email }}
                    </a-descriptions-item>
                    
                    <a-descriptions-item v-if="selectedPayment.notes" label="Примечания">
                        {{ selectedPayment.notes }}
                    </a-descriptions-item>
                    
                    <a-descriptions-item label="ID транзакции">
                        {{ selectedPayment.transactionId || 'Не указан' }}
                    </a-descriptions-item>
                </a-descriptions>
            </div>
        </a-modal>

        <!-- Модальное окно создания платежа (для админа) -->
        <a-modal
            v-model:open="createModalVisible"
            title="Создать платеж"
            width="500px"
            @ok="handleCreatePayment"
            @cancel="closeCreateModal"
        >
            <a-form
                ref="formRef"
                :model="newPayment"
                :rules="formRules"
                layout="vertical"
            >
                <a-form-item label="Пользователь" name="userId" required>
                    <a-select
                        v-model:value="newPayment.userId"
                        placeholder="Выберите пользователя"
                        :options="userOptions"
                        :loading="usersLoading"
                        @focus="loadUsers"
                    />
                </a-form-item>
                
                <a-form-item label="Подписка" name="subscriptionId" required>
                    <a-select
                        v-model:value="newPayment.subscriptionId"
                        placeholder="Выберите подписку"
                        :options="subscriptionOptions"
                        :loading="subscriptionsLoading"
                        :disabled="!newPayment.userId"
                    />
                </a-form-item>
                
                <a-row :gutter="16">
                    <a-col :span="12">
                        <a-form-item label="Сумма" name="amount" required>
                            <a-input-number
                                v-model:value="newPayment.amount"
                                placeholder="0.00"
                                style="width: 100%"
                                :min="0.01"
                                :step="0.01"
                            />
                        </a-form-item>
                    </a-col>
                    
                    <a-col :span="12">
                        <a-form-item label="Валюта" name="currency" required>
                            <a-select v-model:value="newPayment.currency">
                                <a-select-option value="RUB">RUB</a-select-option>
                                <a-select-option value="USD">USD</a-select-option>
                                <a-select-option value="EUR">EUR</a-select-option>
                            </a-select>
                        </a-form-item>
                    </a-col>
                </a-row>
                
                <a-form-item label="Статус" name="statusId" required>
                    <a-select
                        v-model:value="newPayment.statusId"
                        placeholder="Выберите статус"
                        :options="statusOptions"
                    />
                </a-form-item>
                
                <a-form-item label="Дата платежа" name="paymentDate">
                    <a-date-picker
                        v-model:value="newPayment.paymentDate"
                        placeholder="Выберите дату"
                        style="width: 100%"
                        :format="'DD.MM.YYYY'"
                    />
                </a-form-item>
                
                <a-form-item label="Примечания" name="notes">
                    <a-textarea
                        v-model:value="newPayment.notes"
                        placeholder="Дополнительная информация"
                        :rows="3"
                    />
                </a-form-item>
            </a-form>
        </a-modal>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { message, Modal } from 'ant-design-vue'
import type { Dayjs } from 'dayjs'
import dayjs from 'dayjs'
import { ExportOutlined, InfoCircleOutlined } from '@ant-design/icons-vue'
import PaymentApi from '../../api/payment'
import AdminApi from '../../api/admin'
import { useUserStore } from '../../stores/user'
import type { Payment } from '../../common/types/payment/Payment'
import type { User } from '../../common/types/user/User'
import type { Subscription } from '../../common/types/subscription/Subscription'

const userStore = useUserStore()
const loading = ref(false)
const payments = ref<Payment[]>([])

// Фильтры
const dateRange = ref<[Dayjs, Dayjs] | null>(null)
const statusFilter = ref<string | null>(null)

// Пагинация
const pagination = ref({
    current: 1,
    pageSize: 20,
    total: 0,
    showSizeChanger: true,
    showQuickJumper: true
})

// Статистика
const paymentStats = ref({
    total: 0,
    successful: 0,
    totalAmount: 0,
    averageAmount: 0
})

// Модальные окна
const detailsModalVisible = ref(false)
const createModalVisible = ref(false)
const selectedPayment = ref<Payment | null>(null)

// Форма создания
const newPayment = ref({
    userId: null as number | null,
    subscriptionId: null as number | null,
    amount: 0,
    currency: 'RUB',
    statusId: null as number | null,
    paymentDate: dayjs().format('YYYY-MM-DD'),
    notes: ''
})

// Опции для селектов
const userOptions = ref<{ label: string; value: number }[]>([])
const subscriptionOptions = ref<{ label: string; value: number }[]>([])
const statusOptions = ref<{ label: string; value: number }[]>([])
const usersLoading = ref(false)
const subscriptionsLoading = ref(false)

// Правила валидации
const formRules = {
    userId: [{ required: true, message: 'Выберите пользователя' }],
    subscriptionId: [{ required: true, message: 'Выберите подписку' }],
    amount: [{ required: true, message: 'Введите сумму' }],
    statusId: [{ required: true, message: 'Выберите статус' }]
}

// Колонки таблицы
const paymentColumns = computed(() => {
    const columns: any[] = [
        {
            title: 'Дата',
            dataIndex: 'paymentDate',
            key: 'paymentDate',
            sorter: true
        },
        {
            title: 'Сумма',
            key: 'amount',
            sorter: true
        },
        {
            title: 'Статус',
            key: 'status',
            filters: [
                { text: 'Успешно', value: 'Успешно' },
                { text: 'Ошибка', value: 'Ошибка' },
                { text: 'Ожидание', value: 'Ожидание' }
            ],
            onFilter: (value: string, record: Payment) => record.status === value
        },
        {
            title: 'Подписка',
            key: 'subscription'
        }
    ]

    // Для админа добавляем столбец с пользователем
    if (userStore.isAdmin) {
        columns.splice(3, 0, {
            title: 'Пользователь',
            key: 'user'
        })
    }

    columns.push({
        title: 'Действия',
        key: 'actions',
        width: 200
    })

    return columns
})

// Загрузка платежей
const loadPayments = async () => {
    loading.value = true
    try {
        const params: any = {
            page: pagination.value.current,
            limit: pagination.value.pageSize
        }

        if (dateRange.value) {
            params.startDate = dateRange.value[0].format('YYYY-MM-DD')
            params.endDate = dateRange.value[1].format('YYYY-MM-DD')
        }

        if (statusFilter.value) {
            params.status = statusFilter.value
        }

        const response = await PaymentApi.getPayments(params)
        payments.value = response.data.payments
        pagination.value.total = response.data.total
        calculateStats()
    } catch (error) {
        message.error('Ошибка загрузки платежей')
    } finally {
        loading.value = false
    }
}

// Расчет статистики
const calculateStats = () => {
    const successful = payments.value.filter(p => p.status === 'Успешно')
    const totalAmount = successful.reduce((sum, p) => sum + p.amount, 0)
    
    paymentStats.value = {
        total: payments.value.length,
        successful: successful.length,
        totalAmount,
        averageAmount: successful.length > 0 ? totalAmount / successful.length : 0
    }
}

// Фильтрованные платежи
const filteredPayments = computed(() => {
    let filtered = [...payments.value]

    // Фильтр по дате
    if (dateRange.value) {
        const start = dateRange.value[0]
        const end = dateRange.value[1]
        
        filtered = filtered.filter(p => {
            const paymentDate = dayjs(p.paymentDate)
            return paymentDate.isAfter(start.subtract(1, 'day')) && 
                   paymentDate.isBefore(end.add(1, 'day'))
        })
    }

    // Фильтр по статусу
    if (statusFilter.value) {
        filtered = filtered.filter(p => p.status === statusFilter.value)
    }

    return filtered
})

// Форматирование даты
const formatDate = (dateString: string) => {
    return dayjs(dateString).format('DD.MM.YYYY')
}

const formatDateTime = (dateString: string) => {
    return dayjs(dateString).format('DD.MM.YYYY HH:mm')
}

// Форматирование валюты
const formatCurrency = (amount: number, currency: string = 'RUB') => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: currency
    }).format(amount)
}

// Цвет статуса
const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        'Успешно': 'green',
        'Ошибка': 'red',
        'Ожидание': 'yellow',
        'Отменен': 'gray'
    }
    return colors[status] || 'blue'
}

// Обработчики событий
const handleDateChange = () => {
    pagination.value.current = 1
    loadPayments()
}

const handleTableChange = (paginationConfig: any, filters: any, sorter: any) => {
    pagination.value.current = paginationConfig.current
    pagination.value.pageSize = paginationConfig.pageSize
    loadPayments()
}

// Показать детали платежа
const showPaymentDetails = (payment: Payment) => {
    selectedPayment.value = payment
    detailsModalVisible.value = true
}

// Удалить платеж
const deletePayment = (id: number) => {
    Modal.confirm({
        title: 'Удалить платеж?',
        content: 'Это действие нельзя отменить.',
        okText: 'Удалить',
        okType: 'danger',
        cancelText: 'Отмена',
        onOk: async () => {
            try {
                await PaymentApi.deletePayment(id)
                message.success('Платеж удален')
                loadPayments()
            } catch (error) {
                message.error('Ошибка удаления платежа')
            }
        }
    })
}

// Повторить платеж
const retryPayment = async (payment: Payment) => {
    try {
        await PaymentApi.retryPayment(payment.id)
        message.success('Платеж отправлен на повторную обработку')
        loadPayments()
    } catch (error) {
        message.error('Ошибка повторной обработки платежа')
    }
}

// Экспорт платежей
const exportPayments = async () => {
    try {
        const response = await PaymentApi.exportPayments({
            startDate: dateRange.value?.[0]?.format('YYYY-MM-DD'),
            endDate: dateRange.value?.[1]?.format('YYYY-MM-DD'),
            status: statusFilter.value
        })

        // Создаем и скачиваем файл
        const blob = new Blob([response.data], { type: 'text/csv' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = `payments_${dayjs().format('YYYY-MM-DD')}.csv`
        link.click()
        
        message.success('Экспорт завершен')
    } catch (error) {
        message.error('Ошибка экспорта')
    }
}

// Загрузка пользователей для админа
const loadUsers = async () => {
    if (userOptions.value.length > 0 || !userStore.isAdmin) return
    
    usersLoading.value = true
    try {
        const response = await AdminApi.getUsers()
        userOptions.value = response.data.map((user: User) => ({
            label: user.email,
            value: user.id
        }))
    } catch (error) {
        console.error('Ошибка загрузки пользователей', error)
    } finally {
        usersLoading.value = false
    }
}

// Загрузка подписок пользователя
const loadSubscriptions = async () => {
    if (!newPayment.value.userId) return
    
    subscriptionsLoading.value = true
    try {
        const response = await AdminApi.getUserSubscriptions(newPayment.value.userId)
        subscriptionOptions.value = response.data.map((sub: Subscription) => ({
            label: `${sub.name} (${formatCurrency(sub.price, sub.currency)})`,
            value: sub.id
        }))
    } catch (error) {
        console.error('Ошибка загрузки подписок', error)
    } finally {
        subscriptionsLoading.value = false
    }
}

// Загрузка статусов
const loadStatuses = async () => {
    try {
        const response = await PaymentApi.getStatuses()
        statusOptions.value = response.data.map((status: any) => ({
            label: status.name,
            value: status.id
        }))
    } catch (error) {
        console.error('Ошибка загрузки статусов', error)
    }
}

// Показать модальное окно создания
const showCreatePaymentModal = async () => {
    await loadStatuses()
    createModalVisible.value = true
}

// Закрыть модальное окно создания
const closeCreateModal = () => {
    createModalVisible.value = false
    newPayment.value = {
        userId: null,
        subscriptionId: null,
        amount: 0,
        currency: 'RUB',
        statusId: null,
        paymentDate: dayjs().format('YYYY-MM-DD'),
        notes: ''
    }
}

// Создать платеж
const handleCreatePayment = async () => {
    try {
        await PaymentApi.createPayment(newPayment.value)
        message.success('Платеж создан')
        closeCreateModal()
        loadPayments()
    } catch (error) {
        message.error('Ошибка создания платежа')
    }
}

// Перейти к подписке
const goToSubscription = (subscriptionId: number) => {
    // Реализация навигации
    console.log('Go to subscription:', subscriptionId)
}

onMounted(() => {
    loadPayments()
})
</script>

<style scoped>
.payments-tab {
    background: white;
    border-radius: 8px;
    padding: 24px;
}

.payments-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.payments-header h2 {
    margin: 0;
}

.payment-stats {
    margin-bottom: 24px;
    padding: 20px;
    background: #fafafa;
    border-radius: 6px;
}

.payments-table {
    margin-top: 20px;
}

.amount-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}

.amount-value {
    font-weight: 600;
}

.currency-badge {
    padding: 2px 6px;
    background: #f0f0f0;
    border-radius: 3px;
    font-size: 12px;
    color: rgba(0, 0, 0, 0.65);
}

.payment-notes {
    margin-top: 4px;
    font-size: 12px;
    color: rgba(0, 0, 0, 0.45);
    display: flex;
    align-items: center;
    gap: 4px;
}

.payment-details {
    padding: 10px;
}
</style>