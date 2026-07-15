<script setup>
import UserAvatar from '../ui/UserAvatar.vue';

defineProps({
    app: { type: Object, required: true },
    csrfToken: { type: String, required: true },
});
</script>

<template>
    <nav class="app-navbar navbar border-bottom bg-white">
        <div class="container app-navbar__inner">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold mb-0" :href="app.routes.dashboard">
                <span class="brand-mark">W</span>
                {{ app.project.name }}
            </a>

            <div v-if="app.user" class="d-flex align-items-center gap-3">
                <div class="d-none d-sm-flex align-items-center gap-2 text-secondary small">
                    <UserAvatar :initials="app.user.initials" size="sm" />
                    <span class="fw-semibold text-dark">{{ app.user.name }}</span>
                </div>
                <form :action="app.routes.logout" method="POST">
                    <input type="hidden" name="_token" :value="csrfToken">
                    <button class="btn btn-sm btn-outline-secondary px-3" type="submit">登出</button>
                </form>
            </div>

            <div v-else class="d-flex align-items-center gap-2">
                <a
                    class="btn btn-sm"
                    :class="app.page === app.pages.login ? 'btn-primary' : 'btn-outline-primary'"
                    :href="app.routes.login"
                >登入</a>
                <a
                    class="btn btn-sm"
                    :class="app.page === app.pages.register ? 'btn-primary' : 'btn-outline-primary'"
                    :href="app.routes.register"
                >註冊</a>
            </div>
        </div>
    </nav>
</template>
