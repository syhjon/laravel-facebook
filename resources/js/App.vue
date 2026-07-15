<script setup>
import { computed, reactive, ref } from 'vue';

const app = window.appData;
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const isLogin = computed(() => app.page === 'login');
const isRegister = computed(() => app.page === 'register');
const isDashboard = computed(() => app.page === 'dashboard');

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    remember: false,
});

const errors = ref({});
const message = ref('');
const submitting = ref(false);

const fieldError = (field) => errors.value[field]?.[0] ?? '';

async function submit() {
    errors.value = {};
    message.value = '';
    submitting.value = true;

    const endpoint = isLogin.value ? app.routes.login : app.routes.register;
    const payload = isLogin.value
        ? {
                email: form.email,
                password: form.password,
                remember: form.remember,
            }
        : {
                name: form.name,
                email: form.email,
                password: form.password,
                password_confirmation: form.password_confirmation,
            };

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (!response.ok) {
            errors.value = data.errors ?? {};
            message.value = data.message ?? '送出失敗，請稍後再試。';
            return;
        }

        window.location.assign(data.redirect);
    } catch {
        message.value = '目前無法連線到伺服器，請確認網路後重試。';
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="app-shell">
        <nav class="navbar navbar-expand border-bottom bg-white py-3">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" :href="app.routes.dashboard">
                    <span class="brand-mark">W</span>
                    {{ app.project.name }}
                </a>

                <div class="d-flex align-items-center gap-2">
                    <template v-if="!app.user">
                        <a
                            class="btn btn-sm"
                            :class="isLogin ? 'btn-primary' : 'btn-outline-primary'"
                            :href="app.routes.login"
                        >
                            登入
                        </a>
                        <a
                            class="btn btn-sm"
                            :class="isRegister ? 'btn-primary' : 'btn-outline-primary'"
                            :href="app.routes.register"
                        >
                            註冊
                        </a>
                    </template>

                    <form v-else :action="app.routes.logout" method="POST">
                        <input type="hidden" name="_token" :value="csrfToken">
                        <button class="btn btn-sm btn-outline-secondary" type="submit">登出</button>
                    </form>
                </div>
            </div>
        </nav>

        <main v-if="isLogin || isRegister" class="auth-page py-5">
            <div class="container py-lg-5">
                <div class="row align-items-center justify-content-center g-5">
                    <div class="col-lg-5 d-none d-lg-block">
                        <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis mb-3">
                            {{ app.project.technology_label }}
                        </span>
                        <h1 class="display-5 fw-bold lh-sm mb-3">連結彼此，<br>分享每個精彩時刻。</h1>
                        <p class="lead text-secondary mb-0">
                            建立帳號、登入會員中心，開始你的 {{ app.project.name }} 體驗。
                        </p>
                    </div>

                    <div class="col-md-8 col-lg-5 col-xl-4">
                        <section class="card auth-card border-0 shadow-lg">
                            <div class="card-body p-4 p-sm-5">
                                <div class="mb-4">
                                    <h2 class="h3 fw-bold mb-2">{{ isLogin ? '歡迎回來' : '建立會員帳號' }}</h2>
                                    <p class="text-secondary mb-0">
                                        {{ isLogin ? '輸入你的會員資料以繼續。' : '只需一分鐘即可完成註冊。' }}
                                    </p>
                                </div>

                                <div v-if="message" class="alert alert-danger" role="alert">{{ message }}</div>

                                <form novalidate @submit.prevent="submit">
                                    <div v-if="isRegister" class="mb-3">
                                        <label class="form-label fw-semibold" for="name">姓名</label>
                                        <input
                                            id="name"
                                            v-model.trim="form.name"
                                            class="form-control form-control-lg"
                                            :class="{ 'is-invalid': fieldError('name') }"
                                            name="name"
                                            :maxlength="app.constraints.name_max_length"
                                            autocomplete="name"
                                            required
                                        >
                                        <div class="invalid-feedback">{{ fieldError('name') }}</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="email">Email</label>
                                        <input
                                            id="email"
                                            v-model.trim="form.email"
                                            class="form-control form-control-lg"
                                            :class="{ 'is-invalid': fieldError('email') }"
                                            name="email"
                                            type="email"
                                            :maxlength="app.constraints.email_max_length"
                                            autocomplete="email"
                                            required
                                        >
                                        <div class="invalid-feedback">{{ fieldError('email') }}</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="password">密碼</label>
                                        <input
                                            id="password"
                                            v-model="form.password"
                                            class="form-control form-control-lg"
                                            :class="{ 'is-invalid': fieldError('password') }"
                                            name="password"
                                            type="password"
                                            :autocomplete="isLogin ? 'current-password' : 'new-password'"
                                            required
                                        >
                                        <div class="invalid-feedback">{{ fieldError('password') }}</div>
                                        <div v-if="isRegister && !fieldError('password')" class="form-text">
                                            密碼至少需要 {{ app.constraints.password_min_length }} 個字元。
                                        </div>
                                    </div>

                                    <div v-if="isRegister" class="mb-4">
                                        <label class="form-label fw-semibold" for="password_confirmation">確認密碼</label>
                                        <input
                                            id="password_confirmation"
                                            v-model="form.password_confirmation"
                                            class="form-control form-control-lg"
                                            name="password_confirmation"
                                            type="password"
                                            autocomplete="new-password"
                                            required
                                        >
                                    </div>

                                    <div v-if="isLogin" class="form-check mb-4">
                                        <input id="remember" v-model="form.remember" class="form-check-input" type="checkbox">
                                        <label class="form-check-label" for="remember">記住我</label>
                                    </div>

                                    <button class="btn btn-primary btn-lg w-100" type="submit" :disabled="submitting">
                                        <span
                                            v-if="submitting"
                                            class="spinner-border spinner-border-sm me-2"
                                            aria-hidden="true"
                                        ></span>
                                        {{ submitting ? '處理中…' : isLogin ? '登入' : '建立帳號' }}
                                    </button>
                                </form>

                                <hr class="my-4">

                                <p class="text-center text-secondary mb-0">
                                    {{ isLogin ? '還沒有帳號？' : '已經是會員？' }}
                                    <a
                                        class="fw-semibold text-decoration-none"
                                        :href="isLogin ? app.routes.register : app.routes.login"
                                    >
                                        {{ isLogin ? '立即註冊' : '前往登入' }}
                                    </a>
                                </p>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </main>

        <main v-else-if="isDashboard" class="dashboard-page py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        <section class="welcome-panel overflow-hidden shadow-sm">
                            <div class="row g-0 align-items-stretch">
                                <div class="col-md-8 p-4 p-sm-5">
                                    <span class="badge text-bg-success-subtle text-success-emphasis mb-3">已登入</span>
                                    <p class="text-secondary mb-1">歡迎回來</p>
                                    <h1 class="display-6 fw-bold mb-3">{{ app.user.name }}</h1>
                                    <p class="text-secondary mb-1">{{ app.user.email }}</p>
                                    <p class="small text-secondary mb-4">加入日期：{{ app.user.member_since }}</p>
                                    <div class="alert alert-primary border-0 mb-0">
                                        會員系統已正常運作。你可以從這裡繼續建立個人資料、貼文與好友功能。
                                    </div>
                                </div>
                                <div class="col-md-4 dashboard-accent d-flex align-items-center justify-content-center p-5">
                                    <div class="avatar-circle" aria-hidden="true">
                                        {{ app.user.initials }}
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
