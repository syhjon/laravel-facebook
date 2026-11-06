<script setup>
import { computed, reactive } from 'vue';
import AuthPage from './components/auth/AuthPage.vue';
import FeedPage from './components/feed/FeedPage.vue';
import AppNavbar from './components/layout/AppNavbar.vue';
import ProfilePage from './components/profile/ProfilePage.vue';

const applicationData = reactive(window.applicationData);
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const isAuthenticationPage = computed(() =>
    [applicationData.pages.login, applicationData.pages.register].includes(applicationData.page),
);
const isProfilePage = computed(() => applicationData.page === applicationData.pages.profile);

function updateApplicationProfile(updatedProfile) {
    Object.assign(applicationData.user, updatedProfile);
}
</script>

<template>
    <div class="app-shell">
        <AppNavbar :application-data="applicationData" :csrf-token="csrfToken" />
        <AuthPage v-if="isAuthenticationPage" :application-data="applicationData" :csrf-token="csrfToken" />
        <ProfilePage
            v-else-if="isProfilePage"
            :application-data="applicationData"
            :csrf-token="csrfToken"
            @profile-updated="updateApplicationProfile"
        />
        <FeedPage v-else :application-data="applicationData" :csrf-token="csrfToken" />
    </div>
</template>
