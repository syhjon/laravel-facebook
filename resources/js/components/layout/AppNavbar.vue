<script setup>
import UserAvatar from '../ui/UserAvatar.vue';

defineProps({
    applicationData: { type: Object, required: true },
    csrfToken: { type: String, required: true },
});
</script>

<template>
    <nav class="app-navbar navbar border-bottom bg-white">
        <div class="container app-navbar__inner">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold mb-0" :href="applicationData.routes.dashboard">
                <span class="brand-mark">W</span>
                {{ applicationData.project.name }}
            </a>

            <div v-if="applicationData.user" class="d-flex align-items-center gap-3">
                <div class="d-none d-sm-flex align-items-center gap-2 text-secondary small">
                    <UserAvatar :initials="applicationData.user.initials" size="sm" />
                    <span class="fw-semibold text-dark">{{ applicationData.user.name }}</span>
                </div>
                <form :action="applicationData.routes.logout" method="POST">
                    <input type="hidden" name="_token" :value="csrfToken">
                    <button class="btn btn-sm btn-outline-secondary px-3" type="submit">登出</button>
                </form>
            </div>

            <div v-else class="d-flex align-items-center gap-2">
                <a
                    class="btn btn-sm"
                    :class="applicationData.page === applicationData.pages.login ? 'btn-primary' : 'btn-outline-primary'"
                    :href="applicationData.routes.login"
                >登入</a>
                <a
                    class="btn btn-sm"
                    :class="applicationData.page === applicationData.pages.register ? 'btn-primary' : 'btn-outline-primary'"
                    :href="applicationData.routes.register"
                >註冊</a>
            </div>
        </div>
    </nav>
</template>
