# Subscription Tracker

Subscription Tracker — это fullstack-приложение для управления подписками с системой ролей, авторизацией через JWT и административным доступом.
Проект реализован с разделением бизнес-логики, DTO, сервисов и строгой системой доступа.

## Функциональность
### Пользователь

Регистрация и вход в систему

Просмотр своих подписок

Календарь платежей

История платежей

Просмотр и редактирование профиля

### Администратор (ROLE_ADMIN)

Управление подписками пользователей

Просмотр пользователей

Управление категориями, статусами и связанными сущностями

## Архитектура проекта

Проект построен по слоистой архитектуре:

```text
Controller → Service → Repository → Entity
                    ↓
                   DTO
```

Основные принципы:

- Контроллеры не содержат бизнес-логики

- Вся логика вынесена в сервисы

- DTO используются для входных данных

- Валидация выполняется на уровне DTO

- Doctrine используется только через репозитории

## Технологии
### Backend

PHP 8.2+

Symfony 6

Doctrine ORM

LexikJWTAuthenticationBundle

Symfony Security

PostgreSQL

### Frontend

Vue 3

TypeScript

Ant Design Vue

Pinia

Vue Router

Axios

## Аутентификация и безопасность
JWT (основной доступ)

/api/login — вход (JSON)

/api/register — регистрация

/api/* — защищённые маршруты

Авторизация через Authorization: Bearer <token>

## Роли и доступы
```text
ROLE_ADMIN
└── ROLE_USER
```

Ограничения:
Роль	Доступ
USER	свои данные
ADMIN	управление пользователями
## Структура backend
```text
src/
 ├── Controller/
 ├── Service/
 ├── Repository/
 ├── DTO/
 ├── Entity/
 ├── Security/
 └── Validator/
```

## Структура frontend
```text
src/
 ├── api/
 ├── components/
 ├── layouts/
 ├── pages/
 ├── router/
 ├── stores/
 └── common/types/
```

## Frontend-логика

Защищённые страницы используют ProtectedLayout

Роутинг зависит от авторизации

UI отображается в зависимости от роли

Состояние пользователя хранится в Pinia

Проверка сессии через /api/me

## Запуск проекта
### Backend
composer install
php bin/console doctrine:migrations:migrate
symfony serve

### Frontend
npm install
npm run dev

##
Проект разработан как учебный / pet-project с упором на:

правильную архитектуру

безопасность

масштабируемость

реальный production-подход
