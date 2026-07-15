<script setup>
import { computed, reactive, ref } from 'vue';
import { apiRequest } from '../../services/http';

const props = defineProps({
    app: { type: Object, required: true },
    csrfToken: { type: String, required: true },
});

const isLogin = computed(() => props.app.page === props.app.pages.login);
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

    const endpoint = isLogin.value ? props.app.routes.login : props.app.routes.register;
    const payload = isLogin.value
        ? { email: form.email, password: form.password, remember: form.remember }
        : {
                name: form.name,
                email: form.email,
                password: form.password,
                password_confirmation: form.password_confirmation,
            };

    try {
        const data = await apiRequest(endpoint, {
            method: 'POST',
            body: payload,
            csrfToken: props.csrfToken,
        });
        window.location.assign(data.redirect);
    } catch (error) {
        errors.value = error.errors ?? {};
        message.value = error.message ?? '目前無法連線到伺服器。';
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <main class="auth-page py-5">
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
                                <div v-if="!isLogin" class="mb-3">
                                    <label class="form-label fw-semibold" for="name">姓名</label>
                                    <input
                                        id="name"
                                        v-model.trim="form.name"
                                        class="form-control form-control-lg"
                                        :class="{ 'is-invalid': fieldError('name') }"
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
                                        type="password"
                                        :autocomplete="isLogin ? 'current-password' : 'new-password'"
                                        required
                                    >
                                    <div class="invalid-feedback">{{ fieldError('password') }}</div>
                                    <div v-if="!isLogin && !fieldError('password')" class="form-text">
                                        密碼至少需要 {{ app.constraints.password_min_length }} 個字元。
                                    </div>
                                </div>

                                <div v-if="!isLogin" class="mb-4">
                                    <label class="form-label fw-semibold" for="password_confirmation">確認密碼</label>
                                    <input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        class="form-control form-control-lg"
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
                                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                                    {{ submitting ? '處理中…' : isLogin ? '登入' : '建立帳號' }}
                                </button>
                            </form>

                            <hr class="my-4">
                            <p class="text-center text-secondary mb-0">
                                {{ isLogin ? '還沒有帳號？' : '已經是會員？' }}
                                <a
                                    class="fw-semibold text-decoration-none"
                                    :href="isLogin ? app.routes.register : app.routes.login"
                                >{{ isLogin ? '立即註冊' : '前往登入' }}</a>
                            </p>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>
</template>
