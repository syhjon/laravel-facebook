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

            <div v-if="applicationData.user" class="dropdown user-menu">
                <button
                    id="user-menu-trigger"
                    class="user-menu__trigger dropdown-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="開啟會員選單"
                >
                    <UserAvatar :initials="applicationData.user.initials" size="sm" />
                    <span class="user-menu__name">{{ applicationData.user.name }}</span>
                </button>

                <ul class="dropdown-menu dropdown-menu-end user-menu__dropdown" aria-labelledby="user-menu-trigger">
                    <li>
                        <button class="dropdown-item user-menu__item" type="button">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0" />
                            </svg>
                            <span>個人檔案</span>
                        </button>
                    </li>
                    <li>
                        <button class="dropdown-item user-menu__item" type="button">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" />
                                <path d="M19 13.5v-3l-2-.7a7 7 0 0 0-.7-1.7l.9-1.9-2.1-2.1-1.9.9a7 7 0 0 0-1.7-.7L10.5 2h-3l-.7 2.3a7 7 0 0 0-1.7.7l-1.9-.9-2.1 2.1.9 1.9a7 7 0 0 0-.7 1.7l-2.3.7v3l2.3.7a7 7 0 0 0 .7 1.7l-.9 1.9 2.1 2.1 1.9-.9a7 7 0 0 0 1.7.7l.7 2.3h3l.7-2.3a7 7 0 0 0 1.7-.7l1.9.9 2.1-2.1-.9-1.9a7 7 0 0 0 .7-1.7l2.3-.7Z" />
                            </svg>
                            <span>設定</span>
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form :action="applicationData.routes.logout" method="POST">
                            <input type="hidden" name="_token" :value="csrfToken">
                            <button class="dropdown-item user-menu__item user-menu__item--danger" type="submit">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M10 4H5v16h5M14 8l4 4-4 4M8 12h10" />
                                </svg>
                                <span>登出</span>
                            </button>
                        </form>
                    </li>
                </ul>
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
