<template>
    <div>
        <h2>Мои подписки</h2>
        <ul v-if="subscriptions.length">
          <li v-for="sub in subscriptions" :key="sub.id">
            {{ sub.name }} — {{ sub.price }} $
          </li>
        </ul>
<p v-else>У вас пока нет подписок</p>

        <h3>Добавить подписку</h3>
        <a-form layout="vertical" @submit.prevent="addSubscription">
            <a-form-item label="Название">
                <a-input v-model:value="form.name" placeholder="Netflix, Spotify..." />
            </a-form-item>
            <a-form-item label="Цена">
                <a-input-number v-model:value="form.price" :min="0" placeholder="Цена" />
            </a-form-item>
            <a-button type="primary" html-type="submit" :disabled="!form.name || !form.price">
                Добавить
            </a-button>
        </a-form>
    </div>
</template>

<script setup lang="ts">
import { reactive, onMounted } from 'vue';
import axios from 'axios';

const subscriptions = reactive<any[]>([]);

const form = reactive({
    name: '',
    price: null
});

async function fetchSubscriptions() {
    const { data } = await axios.get('/app/subscriptions', {
        baseURL: 'https://localhost:8000/api',
        withCredentials: true,
    });
    subscriptions.splice(0, subscriptions.length, ...data);
}

async function addSubscription() {
    await axios.post('https://localhost:8000/api/app/subscriptions', form);
    form.name = '';
    form.price = null;
    fetchSubscriptions();
}

onMounted(fetchSubscriptions);
</script>
