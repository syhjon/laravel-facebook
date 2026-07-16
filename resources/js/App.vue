<script setup>
import { computed } from 'vue';
import AuthPage from './components/auth/AuthPage.vue';
import FeedPage from './components/feed/FeedPage.vue';
import AppNavbar from './components/layout/AppNavbar.vue';

const applicationData = window.applicationData;
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const isAuthenticationPage = computed(() =>
    [applicationData.pages.login, applicationData.pages.register].includes(applicationData.page),
);
</script>

<template>
    <div class="app-shell">
        <AppNavbar :application-data="applicationData" :csrf-token="csrfToken" />
        <AuthPage v-if="isAuthenticationPage" :application-data="applicationData" :csrf-token="csrfToken" />
        <FeedPage v-else :application-data="applicationData" :csrf-token="csrfToken" />
    </div>
</template>
