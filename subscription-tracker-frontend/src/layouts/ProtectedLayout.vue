<template>
  <a-layout class="protected-layout">
    <!-- Основной контент -->
    <a-layout>
      <!-- Сайдбар -->
      <a-layout-sider
        v-model:collapsed="collapsed"
        :trigger="null"
        collapsible
        :width="250"
        breakpoint="lg"
        class="sider"
      >
        <div class="sider-logo">
          <h2 v-if="!collapsed">{{ appName }}</h2>
          <h2 v-else>≡</h2>
        </div>
        
        <a-menu
          v-model:selectedKeys="selectedKeys"
          mode="inline"
          theme="dark"
          :inline-collapsed="collapsed"
        >
          <a-menu-item key="home">
            <router-link to="/">
              <HomeOutlined />
              <span>Главная</span>
            </router-link>
          </a-menu-item>
          
          <a-menu-item key="subscriptions">
            <router-link to="/subscriptions">
              <AppstoreOutlined />
              <span>Мои подписки</span>
            </router-link>
          </a-menu-item>
          
          <!-- <a-menu-item key="calendar">
            <router-link to="/calendar">
              <CalendarOutlined />
              <span>Календарь</span>
            </router-link>
          </a-menu-item> -->

          <template v-if="isAdmin">
            <a-sub-menu key="admin">
              <template #title>
                <span>
                  <SettingOutlined />
                  <span>Админ-панель</span>
                </span>
              </template>
              <a-menu-item key="admin-users">
                <router-link to="/admin/users">
                  <TeamOutlined />
                  <span>Пользователи</span>
                </router-link>
              </a-menu-item>
              <!-- <a-menu-item key="admin-subscriptions">
                <router-link to="/admin/subscriptions">
                  <AppstoreOutlined />
                  <span>Все подписки</span>
                </router-link>
              </a-menu-item> -->
            </a-sub-menu>
          </template>
          
          <!-- Root пункты меню -->
          <template v-if="isRoot">
            <a-sub-menu key="root">
              <template #title>
                <span>
                  <CrownOutlined />
                  <span>Root-панель</span>
                </span>
              </template>
              <a-menu-item key="root-admins">
                <router-link to="/root/admins">
                  <UserSwitchOutlined />
                  <span>Администраторы</span>
                </router-link>
              </a-menu-item>
            </a-sub-menu>
          </template>
        </a-menu>
        
        <!-- Кнопка свернуть/развернуть -->
        <div class="sider-collapse">
          <MenuUnfoldOutlined
            v-if="collapsed"
            class="trigger"
            @click="toggleCollapsed"
          />
          <MenuFoldOutlined
            v-else
            class="trigger"
            @click="toggleCollapsed"
          />
        </div>
      </a-layout-sider>

      <!-- Основная область контента -->
      <a-layout class="content-layout">
        <a-layout-header class="header">
          <div class="header-content">
            <!-- Логотип -->
            <div class="logo">
              <router-link to="/" class="logo-link">
                <span class="logo-text">{{ appName }}</span>
              </router-link>
            </div>
          
            <!-- Меню пользователя -->
            <div class="user-menu">
              <a-dropdown :trigger="['click']" placement="bottomRight">
                <div class="user-info">
                  <a-avatar :size="32" style="background-color: #1890ff">
                    {{ userInitials }}
                  </a-avatar>
                  <DownOutlined style="margin-left: 8px; font-size: 12px;" />
                </div>

                <template #overlay>
                  <a-menu>
                    <a-menu-item key="profile">
                      <router-link to="/profile">
                        <UserOutlined />
                        <span>Профиль</span>
                      </router-link>
                    </a-menu-item>
                    <a-menu-item key="logout" @click="logout">
                      <LogoutOutlined />
                      <span>Выйти</span>
                    </a-menu-item>
                  </a-menu>
                </template>
              </a-dropdown>
            </div>
          </div>
        </a-layout-header>
        <!-- Breadcrumb -->
        <a-breadcrumb class="breadcrumb" v-if="breadcrumbItems.length > 0">
          <a-breadcrumb-item>
            <HomeOutlined />
          </a-breadcrumb-item>
          <a-breadcrumb-item v-for="(item, index) in breadcrumbItems" :key="index">
            {{ item }}
          </a-breadcrumb-item>
        </a-breadcrumb>

        <!-- Основной контент страницы -->
        <a-layout-content class="main-content">
          <div class="content-container">
            <router-view v-slot="{ Component }">
              <transition name="fade" mode="out-in">
                <component :is="Component" />
              </transition>
            </router-view>
          </div>
        </a-layout-content>

        <!-- Футер -->
        <a-layout-footer class="footer">
          <div class="footer-content">
            <span>{{ appName }} © {{ currentYear }}</span>
          </div>
        </a-layout-footer>
      </a-layout>
    </a-layout>
  </a-layout>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useUserStore } from '../stores/user';
import { message } from 'ant-design-vue';
import { RolesEnum } from '../common/consts/Roles';

// Иконки
import {
  HomeOutlined,
  AppstoreOutlined,
  CalendarOutlined,
  LineChartOutlined,
  SettingOutlined,
  UserOutlined,
  LogoutOutlined,
  SafetyOutlined,
  BellOutlined,
  MenuUnfoldOutlined,
  MenuFoldOutlined,
  DownOutlined,
} from '@ant-design/icons-vue';

const router = useRouter();
const route = useRoute();
const userStore = useUserStore();

// Состояние
const collapsed = ref(false);
const selectedKeys = ref<string[]>([]);

// Константы
const appName = 'Subscription Tracker';
const currentYear = new Date().getFullYear();

// Вычисляемые свойства
const userInitials = computed(() => userStore.userInitials);
const isAdmin = computed(() => {
  return userStore.user?.roles?.includes(RolesEnum.ROLE_ADMIN) || 
         userStore.user?.roles?.includes(RolesEnum.ROLE_ROOT);
});
const isRoot = computed(() => {
  return userStore.user?.roles?.includes(RolesEnum.ROLE_ROOT);
});

// Breadcrumb на основе метаданных маршрута
const breadcrumbItems = computed(() => {
  const items = [];
  const routeMeta = route.meta;
  
  if (routeMeta.title) {
    items.push(routeMeta.title as string);
  }
  
  return items;
});

// Обновляем выбранный ключ меню при изменении маршрута
watch(
  () => route.name,
  (routeName) => {
    // Маппинг имени маршрута к ключу меню
    const routeToKeyMap: Record<string, string> = {
      'Home': 'home',
      'Subscriptions': 'subscriptions',
      'Calendar': 'calendar',
      'Analytics': 'analytics',
      'Profile': 'profile-settings',
      'AccountSettings': 'account-settings',
      'NotificationSettings': 'notification-settings'
    };
    
    const key = routeToKeyMap[routeName as string] || 'home';
    selectedKeys.value = [key];
    
    // Открываем меню настроек если мы на странице настроек
    if (routeName === 'AccountSettings' || routeName === 'NotificationSettings') {
      selectedKeys.value = ['account-settings'];
    }
  },
  { immediate: true }
);

// Проверка авторизации при монтировании
onMounted(async () => {
  try {
    // Проверяем, есть ли токен
    const token = localStorage.getItem('token');
    if (!token) {
      router.push('/login');
      return;
    }
    
    // Если пользователь не загружен, загружаем
    if (!userStore.isAuthenticated) {
      await userStore.fetchMe();
    }
  } catch (error) {
    console.error('Ошибка проверки авторизации:', error);
    router.push('/login');
  }
});

// Методы
const toggleCollapsed = () => {
  collapsed.value = !collapsed.value;
};

const logout = async () => {
  try {
    await userStore.logout();
    message.success('Вы успешно вышли из системы');
  } catch (error) {
    message.error('Ошибка при выходе из системы');
  }
};
</script>

<style scoped>
.protected-layout {
  min-height: 100vh;
  width: 100vw;
  overflow-x: hidden; /* Скрываем горизонтальный скролл */
}

.header {
  background: #fff;
  padding: 0 24px;
  box-shadow: 0 1px 4px rgba(0, 21, 41, 0.08);
  width: calc(100vw - 250px); /* Занимаем всю ширину минус сайдбар */
  position: fixed;
  top: 0;
  right: 0;
  z-index: 100;
  
  .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 100%;
    width: 100%;
  }
  
  .logo {
    display: flex;
    align-items: center;
    
    .logo-link {
      text-decoration: none;
      
      .logo-text {
        font-size: 20px;
        font-weight: 600;
        color: #1890ff;
      }
    }
  }
  
  .user-menu {
    cursor: pointer;
    
    .user-info {
      display: flex;
      align-items: center;
      padding: 8px 12px;
      border-radius: 6px;
      transition: background-color 0.3s;
      
      &:hover {
        background-color: #f5f5f5;
      }
    }
  }
}

.sider {
  overflow: auto;
  position: fixed;
  height: 100vh;
  left: 0;
  top: 0;
  bottom: 0;
  z-index: 101;
  
  .sider-logo {
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    margin-bottom: 16px;
    
    h2 {
      color: white;
      margin: 0;
      font-size: 16px;
      font-weight: 600;
    }
  }
  
  .sider-collapse {
    position: absolute;
    bottom: 0;
    width: 100%;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding: 16px;
    text-align: center;
    
    .trigger {
      font-size: 18px;
      color: white;
      cursor: pointer;
      transition: color 0.3s;
      
      &:hover {
        color: #1890ff;
      }
    }
  }
}

.content-layout {
  margin-left: 250px; 
  width: calc(100vw - 250px); /* Ширина минус сайдбар */
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  transition: margin-left 0.2s, width 0.2s;
}

/* Когда сайдбар свернут */
.sider.ant-layout-sider-collapsed + .content-layout {
  margin-left: 80px;
  width: calc(100vw - 80px);
  
  .header {
    width: calc(100vw - 80px);
  }
}

.breadcrumb {
  padding: 16px 24px;
  background: #fff;
  border-bottom: 1px solid #f0f0f0;
  width: 100%;
  margin-top: 64px; /* Отступ для фиксированного хедера */
}

.main-content {
  padding: 24px;
  overflow-y: auto;
  flex: 1; /* Занимает все доступное пространство */
  width: 100%;
  
  .content-container {
    background: #fff;
    padding: 24px;
    border-radius: 8px;
    min-height: 100%;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    width: 100%;
    max-width: none; /* Убираем ограничение в 1200px */
  }
}

.footer {
  padding: 16px 24px;
  text-align: center;
  background: #fff;
  border-top: 1px solid #f0f0f0;
  width: calc(100vw - 250px);
  position: relative;
  right: 0;
  
  .footer-content {
    display: flex;
    justify-content: center; /* Центрируем текст */
    align-items: center;
  }
}

/* Когда сайдбар свернут - адаптируем футер */
.sider.ant-layout-sider-collapsed ~ .content-layout .footer {
  width: calc(100vw - 80px);
}

/* Адаптивность */
@media (max-width: 992px) {
  .sider {
    position: fixed;
    z-index: 1000;
  }
  
  .content-layout {
    margin-left: 0 !important;
    width: 100vw !important;
  }
  
  .header {
    width: 100vw !important;
    padding: 0 16px;
  }
  
  .breadcrumb {
    margin-top: 64px;
    padding: 16px;
  }
  
  .main-content {
    padding: 16px;
    
    .content-container {
      padding: 16px;
    }
  }
  
  .footer {
    width: 100vw !important;
    padding: 12px 16px;
  }
}

/* Для очень широких экранов можно добавить ограничение */
@media (min-width: 1920px) {
  .content-container {
    max-width: 1600px; /* Ограничиваем на очень широких экранах */
    margin: 0 auto; /* Центрируем */
  }
}

/* Анимации */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

:deep(.sider) .sider-logo h2 {
  color: white;
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}
</style>