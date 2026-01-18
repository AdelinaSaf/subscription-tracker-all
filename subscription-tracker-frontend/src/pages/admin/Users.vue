<template>
    <div class="admin-users-page">
        <div class="page-header">
            <h2>Управление пользователями</h2>
        </div>

        <!-- Таблица пользователей -->
        <a-table 
            :data-source="users"
            :columns="columns"
            :loading="loading"
            rowKey="id"
            :pagination="{ pageSize: 10 }"
        >
            <template #bodyCell="{ column, record }">
                <!-- Роли -->
                <template v-if="column.key === 'roles'">
                    <a-space>
                        <a-tag 
                            v-for="role in record.roles" 
                            :key="role"
                            :color="getRoleColor(role)"
                        >
                            {{ formatRole(role) }}
                        </a-tag>
                    </a-space>
                </template>
                
                <!-- Действия -->
                <template v-else-if="column.key === 'actions'">
                    <a-space>
                        <a-button type="link" @click="editUser(record)">
                            <EditOutlined /> Редактировать
                        </a-button>
                        <a-popconfirm
                            title="Вы уверены, что хотите удалить пользователя?"
                            @confirm="deleteUser(record.id)"
                        >
                            <a-button type="link" danger>
                                <DeleteOutlined /> Удалить
                            </a-button>
                        </a-popconfirm>
                    </a-space>
                </template>
            </template>
        </a-table>

        <!-- Модальное окно редактирования -->
        <a-modal
            v-model:open="modalVisible"
            title="Редактирование пользователя"
            @ok="handleModalOk"
            @cancel="handleModalCancel"
        >
            <a-form
                ref="formRef"
                :model="formState"
                layout="vertical"
            >
                <a-form-item label="Роли">
                    <a-select
                        v-model:value="formState.roles"
                        mode="multiple"
                        style="width: 100%"
                    >
                        <a-select-option value="ROLE_USER">Пользователь</a-select-option>
                        <a-select-option value="ROLE_ADMIN">Администратор</a-select-option>
                    </a-select>
                </a-form-item>
            </a-form>
        </a-modal>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import { EditOutlined, DeleteOutlined } from '@ant-design/icons-vue'
import AdminApi from '../../api/admin'
import type { AdminUser } from '../../common/types/admin/Admin'
import { RolesEnum } from '../../common/consts/Roles'

const loading = ref(false)
const users = ref<AdminUser[]>([])
const modalVisible = ref(false)
const editingId = ref<number | null>(null)

const formState = reactive({
    roles: [] as string[]
})

const columns = [
    {
        title: 'ID',
        dataIndex: 'id',
        key: 'id',
        width: 80
    },
    {
        title: 'Email',
        dataIndex: 'email',
        key: 'email'
    },
    {
        title: 'Часовой пояс',
        dataIndex: 'timezone',
        key: 'timezone'
    },
    {
        title: 'Роли',
        key: 'roles'
    },
    {
        title: 'Действия',
        key: 'actions',
        width: 200
    }
]

const loadUsers = async () => {
    loading.value = true
    try {
        const response = await AdminApi.getUsers()
        users.value = response.data
    } catch (error) {
        message.error('Ошибка загрузки пользователей')
        console.error(error)
    } finally {
        loading.value = false
    }
}

const editUser = (user: AdminUser) => {
    editingId.value = user.id
    formState.roles = [...user.roles]
    modalVisible.value = true
}

const handleModalOk = async () => {
    if (!editingId.value) return
    
    try {
        await AdminApi.updateUser(editingId.value, {
            roles: formState.roles
        })
        message.success('Роли пользователя обновлены')
        modalVisible.value = false
        await loadUsers()
    } catch (error) {
        message.error('Ошибка обновления пользователя')
    }
}

const handleModalCancel = () => {
    modalVisible.value = false
}

const deleteUser = async (id: number) => {
    try {
        await AdminApi.deleteUser(id)
        message.success('Пользователь удален')
        await loadUsers()
    } catch (error) {
        message.error('Ошибка удаления пользователя')
    }
}

const getRoleColor = (role: string) => {
    switch (role) {
        case RolesEnum.ROLE_ROOT: return 'red'
        case RolesEnum.ROLE_ADMIN: return 'orange'
        case RolesEnum.ROLE_USER: return 'blue'
        default: return 'default'
    }
}

const formatRole = (role: string) => {
    const map: Record<string, string> = {
        [RolesEnum.ROLE_ROOT]: 'Root',
        [RolesEnum.ROLE_ADMIN]: 'Админ',
        [RolesEnum.ROLE_USER]: 'Пользователь'
    }
    return map[role] || role
}

onMounted(() => {
    loadUsers()
})
</script>

<style scoped>
.admin-users-page {
    padding: 20px;
}

.page-header {
    margin-bottom: 24px;
}

.page-header h2 {
    margin: 0;
}
</style>