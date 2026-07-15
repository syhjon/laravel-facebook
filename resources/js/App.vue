<script setup>
import { computed } from 'vue';
import AuthPage from './components/auth/AuthPage.vue';
import FeedPage from './components/feed/FeedPage.vue';
import AppNavbar from './components/layout/AppNavbar.vue';

const app = window.appData;
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const isAuthPage = computed(() => [app.pages.login, app.pages.register].includes(app.page));
</script>

<template>
    <div class="app-shell">
        <AppNavbar :app="app" :csrf-token="csrfToken" />
        <AuthPage v-if="isAuthPage" :app="app" :csrf-token="csrfToken" />
        <FeedPage v-else :app="app" :csrf-token="csrfToken" />
    </div>
</template>
