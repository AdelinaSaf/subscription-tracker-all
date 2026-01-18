<template>
    <div class="subscriptions-page">
        <div class="page-header">
            <h2>Мои подписки</h2>
            <a-button type="primary" @click="showCreateModal">
                <template #icon><PlusOutlined /></template>
                Добавить подписку
            </a-button>
        </div>

        <!-- Таблица подписок -->
        <a-table 
            :data-source="subscriptions"
            :columns="columns"
            :loading="loading"
            rowKey="id"
            :pagination="{ pageSize: 7 }"
        >
            <template #bodyCell="{ column, record }">
                <!-- Статус -->
                <template v-if="column.key === 'active'">
                    <a-tag :color="record.active ? 'green' : 'red'">
                        {{ record.active ? 'Активна' : 'Неактивна' }}
                    </a-tag>
                </template>
                
                <!-- Дата следующего платежа -->
                <template v-else-if="column.key === 'nextPaymentDate'">
                    {{ formatDate(record.nextPaymentDate) }}
                </template>
                
                <!-- Действия -->
                <template v-else-if="column.key === 'actions'">
                    <a-space>
                        <a-button 
                            type="link" 
                            danger 
                            @click="toggleSubscriptionStatus(record)"
                        >
                            {{ record.active ? 'Деактивировать' : 'Активировать' }}
                        </a-button>
                        <a-button type="link" @click="editSubscription(record)">
                            <EditOutlined />
                        </a-button>
                        <a-popconfirm
                            title="Вы уверены, что хотите удалить подписку?"
                            @confirm="deleteSubscription(record.id)"
                            :overlayStyle="{ width: '300px', maxWidth: '100%' }"
                        >
                            <a-button type="link" danger>
                                <DeleteOutlined />
                            </a-button>
                        </a-popconfirm>
                    </a-space>
                </template>
            </template>
        </a-table>

        <!-- Модальное окно создания/редактирования -->
        <a-modal
            v-model:open="modalVisible"
            :title="modalTitle"
            @ok="handleModalOk"
            @cancel="handleModalCancel"
            width="600px"
        >
            <a-form
                ref="formRef"
                :model="formState"
                :rules="rules"
                layout="vertical"
            >
                <a-form-item label="Название подписки" name="name">
                    <a-input
                        v-model:value="formState.name"
                        placeholder="Например: Netflix"
                    />
                </a-form-item>

                <a-row :gutter="16">
                    <a-col :span="12">
                        <a-form-item label="Цена" name="price">
                            <a-input-number
                                v-model:value="formState.price"
                                :min="0"
                                style="width: 100%"
                                addon-after="₽"
                            />
                        </a-form-item>
                    </a-col>
                    <a-col :span="12">
                        <a-form-item label="Валюта" name="currency">
                            <a-select v-model:value="formState.currency">
                                <a-select-option value="RUB">RUB</a-select-option>
                                <a-select-option value="USD">USD</a-select-option>
                                <a-select-option value="EUR">EUR</a-select-option>
                            </a-select>
                        </a-form-item>
                    </a-col>
                </a-row>

                <a-row :gutter="16">
                    <a-col :span="12">
                        <a-form-item label="Периодичность" name="periodicity">
                            <a-select v-model:value="formState.periodicity">
                                <a-select-option value="month">Ежемесячно</a-select-option>
                                <a-select-option value="year">Ежегодно</a-select-option>
                                <a-select-option value="week">Еженедельно</a-select-option>
                            </a-select>
                        </a-form-item>
                    </a-col>
                    <a-col :span="12">
                        <a-form-item label="Дата следующего платежа" name="nextPaymentDate">
                            <a-date-picker
                                v-model:value="formState.nextPaymentDate"
                                format="YYYY-MM-DD"
                                value-format="YYYY-MM-DD"
                                style="width: 100%"
                                :disabled-date="disabledDate"
                                :show-today="false"
                                :defaultPickerValue="defaultPickerValue"
                            />
                        </a-form-item>
                    </a-col>
                </a-row>
            </a-form>
        </a-modal>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { message, type FormInstance, type Rule } from 'ant-design-vue'
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons-vue'
import SubscriptionApi from '../api/subscription'
import type { Subscription, CreateSubscriptionDto, UpdateSubscriptionDto } from '../common/types/subscription/Subscription'
import dayjs from 'dayjs'


const loading = ref(false)
const subscriptions = ref<Subscription[]>([])
const modalVisible = ref(false)
const modalTitle = ref('Добавить подписку')
const editingId = ref<number | null>(null)
const formRef = ref<FormInstance>()

const formState = reactive({
    name: '',
    price: 0,
    currency: 'RUB',
    periodicity: 'month' as 'month' | 'year' | 'week',
    nextPaymentDate: ''
})

const disabledDate = (current: dayjs.Dayjs) => {
    return current && current < dayjs().startOf('day')
}

const rules: Record<string, Rule[]> = {
    name: [
        { required: true, message: 'Введите название подписки', trigger: 'blur' },
        { min: 2, max: 100, message: 'Длина от 2 до 100 символов', trigger: 'blur' }
    ],
    price: [
        { required: true, message: 'Введите цену', trigger: 'blur' },
        { type: 'number', min: 0, message: 'Цена должна быть положительной', trigger: 'blur' }
    ],
    nextPaymentDate: [
        { required: true, message: 'Выберите дату следующего платежа', trigger: 'change' },
        {
            validator: async (_rule: Rule, value: string) => {
                if (value) {
                    const selectedDate = dayjs(value)
                    const today = dayjs().startOf('day')
                    if (selectedDate.isBefore(today)) {
                        throw new Error('Дата платежа не может быть в прошлом')
                    }
                }
            },
            trigger: 'change'
        }
    ]
}

const columns = [
    {
        title: 'Название',
        dataIndex: 'name',
        key: 'name'
    },
    {
        title: 'Цена',
        dataIndex: 'price',
        key: 'price',
        render: (value: number, record: Subscription) => 
            `${value} ${record.currency}`
    },
    {
        title: 'Периодичность',
        dataIndex: 'periodicity',
        key: 'periodicity',
        render: (value: string) => {
            const map: Record<string, string> = {
                month: 'Ежемесячно',
                year: 'Ежегодно',
                week: 'Еженедельно'
            }
            return map[value] || value
        }
    },
    {
        title: 'Следующий платеж',
        key: 'nextPaymentDate'
    },
    {
        title: 'Статус',
        key: 'active'
    },
    {
        title: 'Действия',
        key: 'actions',
        width: 250
    }
]

const loadSubscriptions = async () => {
    loading.value = true
    try {
        const response = await SubscriptionApi.getSubscriptions()
        subscriptions.value = response.data
    } catch (error) {
        message.error('Ошибка загрузки подписок')
        console.error(error)
    } finally {
        loading.value = false
    }
}

const defaultPickerValue = computed(() => {
    // Если есть выбранная дата, используем ее
    if (formState.nextPaymentDate) {
        return dayjs(formState.nextPaymentDate)
    }
    // Иначе используем завтрашнюю дату
    return dayjs().add(1, 'day')
})

const showCreateModal = () => {
    editingId.value = null
    modalTitle.value = 'Добавить подписку'
    const tomorrow = dayjs().add(1, 'day').format('YYYY-MM-DD')
    
    Object.assign(formState, {
        name: '',
        price: 0,
        currency: 'RUB',
        periodicity: 'month',
        nextPaymentDate: tomorrow 
    })
    modalVisible.value = true
}

const editSubscription = (record: Subscription) => {
    editingId.value = record.id
    modalTitle.value = 'Редактировать подписку'
    Object.assign(formState, {
        name: record.name,
        price: record.price,
        currency: record.currency,
        periodicity: record.periodicity,
        nextPaymentDate: record.nextPaymentDate
    })
    modalVisible.value = true
}

const handleModalOk = async () => {
    try {
        await formRef.value?.validate()
        
        if (editingId.value) {
            const payload: UpdateSubscriptionDto = { ...formState }
            await SubscriptionApi.updateSubscription(editingId.value, payload)
            message.success('Подписка обновлена')
        } else {
            const payload: CreateSubscriptionDto = { ...formState }
            await SubscriptionApi.createSubscription(payload)
            message.success('Подписка создана')
        }
        
        modalVisible.value = false
        await loadSubscriptions()
    } catch (error) {
        console.error(error)
    }
}

const handleModalCancel = () => {
    modalVisible.value = false
    formRef.value?.resetFields()
}

const toggleSubscriptionStatus = async (record: Subscription) => {
    try {
        await SubscriptionApi.toggleSubscriptionStatus(record.id)
        message.success(`Подписка ${record.active ? 'деактивирована' : 'активирована'}`)
        await loadSubscriptions()
    } catch (error) {
        message.error('Ошибка при изменении статуса подписки')
        console.error(error)
    }
}

const deleteSubscription = async (id: number) => {
    try {
        await SubscriptionApi.deleteSubscription(id)
        message.success('Подписка удалена')
        await loadSubscriptions()
    } catch (error) {
        message.error('Ошибка при удалении подписки')
    }
}

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('ru-RU')
}

onMounted(() => {
    loadSubscriptions()
})
</script>

<style scoped>
.subscriptions-page {
  width: 100%;
  max-width: none;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.page-header h2 {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
}

/* Улучшаем таблицу */
:deep(.ant-table) {
  background: transparent;
}

:deep(.ant-table-thead > tr > th) {
  background-color: #fafafa;
  font-weight: 600;
}

:deep(.ant-table-tbody > tr > td) {
  border-bottom: 1px solid #f0f0f0;
}

/* Делаем кнопки действий более компактными */
:deep(.ant-space) {
  display: flex;
  gap: 4px;
}

:deep(.ant-btn-link) {
  padding: 4px 8px;
}

/* Адаптивность таблицы */
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
  
  .page-header h2 {
    font-size: 20px;
  }
  
  :deep(.ant-table) {
    font-size: 14px;
  }
  
  :deep(.ant-table-thead) {
    display: none;
  }
  
  :deep(.ant-table-tbody > tr) {
    display: block;
    margin-bottom: 16px;
    border: 1px solid #f0f0f0;
    border-radius: 8px;
    padding: 12px;
  }
  
  :deep(.ant-table-tbody > tr > td) {
    display: flex;
    justify-content: space-between;
    border-bottom: none;
    padding: 8px 0;
  }
  
  :deep(.ant-table-tbody > tr > td:before) {
    content: attr(data-label);
    font-weight: 600;
    margin-right: 16px;
  }
  
  :deep(.ant-table-tbody > tr > td[data-label]) {
    display: flex;
  }
}
</style>