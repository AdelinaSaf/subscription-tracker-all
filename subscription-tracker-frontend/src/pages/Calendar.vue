<template>
  <div class="p-6">
    <h2 class="text-2xl font-bold mb-4">Календарь списаний</h2>

    <a-table
      :data-source="payments"
      :columns="columns"
      row-key="id"
      bordered
      class="bg-white rounded shadow-sm"
    />
  </div>
</template>

<script setup lang="ts">
import { reactive } from 'vue';

const payments = reactive([
  { id: 1, subscription: 'Netflix', amount: 12.99, date: '2025-12-03' },
  { id: 2, subscription: 'Spotify', amount: 9.99, date: '2025-12-05' },
  { id: 3, subscription: 'Adobe CC', amount: 49.99, date: '2025-12-10' },
]);

const columns = [
  { title: 'Дата', dataIndex: 'date', key: 'date' },
  { title: 'Подписка', dataIndex: 'subscription', key: 'subscription' },
  { title: 'Сумма ($)', dataIndex: 'amount', key: 'amount' },
];
</script>
