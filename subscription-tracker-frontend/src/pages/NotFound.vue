<template>
    <div class="not-found-page">
        <a-result
            status="404"
            title="404"
            sub-title="Страница не найдена"
        >
            <template #extra>
                <router-link to="/">
                    <a-button type="primary">На главную</a-button>
                </router-link>
            </template>
        </a-result>
    </div>
</template>

<style scoped>
.not-found-page {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}
</style>