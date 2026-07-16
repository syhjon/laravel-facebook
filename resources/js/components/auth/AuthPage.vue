<script setup>
import { computed, reactive, ref } from 'vue';
import { apiRequest } from '../../services/http';

const componentProperties = defineProps({
    applicationData: { type: Object, required: true },
    csrfToken: { type: String, required: true },
});

const isLoginPage = computed(
    () => componentProperties.applicationData.page === componentProperties.applicationData.pages.login,
);
const authenticationForm = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    remember: false,
});
const validationErrors = ref({});
const authenticationErrorMessage = ref('');
const authenticationSubmissionInProgress = ref(false);
const validationErrorForField = (fieldName) => validationErrors.value[fieldName]?.[0] ?? '';

async function submitAuthenticationForm() {
    validationErrors.value = {};
    authenticationErrorMessage.value = '';
    authenticationSubmissionInProgress.value = true;

    const authenticationEndpoint = isLoginPage.value
        ? componentProperties.applicationData.routes.login
        : componentProperties.applicationData.routes.register;
    const authenticationPayload = isLoginPage.value
        ? {
                email: authenticationForm.email,
                password: authenticationForm.password,
                remember: authenticationForm.remember,
            }
        : {
                name: authenticationForm.name,
                email: authenticationForm.email,
                password: authenticationForm.password,
                password_confirmation: authenticationForm.password_confirmation,
            };

    try {
        const authenticationResponsePayload = await apiRequest(authenticationEndpoint, {
            method: 'POST',
            requestBody: authenticationPayload,
            csrfToken: componentProperties.csrfToken,
        });
        window.location.assign(authenticationResponsePayload.redirect);
    } catch (error) {
        validationErrors.value = error.errors ?? {};
        authenticationErrorMessage.value = error.message ?? '目前無法連線到伺服器。';
    } finally {
        authenticationSubmissionInProgress.value = false;
    }
}
</script>

<template>
    <main class="auth-page py-5">
        <div class="container py-lg-5">
            <div class="row align-items-center justify-content-center g-5">
                <div class="col-lg-5 d-none d-lg-block">
                    <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis mb-3">
                        {{ applicationData.project.technology_label }}
                    </span>
                    <h1 class="display-5 fw-bold lh-sm mb-3">連結彼此，<br>分享每個精彩時刻。</h1>
                    <p class="lead text-secondary mb-0">
                        建立帳號、登入會員中心，開始你的 {{ applicationData.project.name }} 體驗。
                    </p>
                </div>

                <div class="col-md-8 col-lg-5 col-xl-4">
                    <section class="card auth-card border-0 shadow-lg">
                        <div class="card-body p-4 p-sm-5">
                            <div class="mb-4">
                                <h2 class="h3 fw-bold mb-2">{{ isLoginPage ? '歡迎回來' : '建立會員帳號' }}</h2>
                                <p class="text-secondary mb-0">
                                    {{ isLoginPage ? '輸入你的會員資料以繼續。' : '只需一分鐘即可完成註冊。' }}
                                </p>
                            </div>

                            <div v-if="authenticationErrorMessage" class="alert alert-danger" role="alert">
                                {{ authenticationErrorMessage }}
                            </div>

                            <form novalidate @submit.prevent="submitAuthenticationForm">
                                <div v-if="!isLoginPage" class="mb-3">
                                    <label class="form-label fw-semibold" for="name">姓名</label>
                                    <input
                                        id="name"
                                        v-model.trim="authenticationForm.name"
                                        class="form-control form-control-lg"
                                        :class="{ 'is-invalid': validationErrorForField('name') }"
                                        :maxlength="applicationData.constraints.name_max_length"
                                        autocomplete="name"
                                        required
                                    >
                                    <div class="invalid-feedback">{{ validationErrorForField('name') }}</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="email">Email</label>
                                    <input
                                        id="email"
                                        v-model.trim="authenticationForm.email"
                                        class="form-control form-control-lg"
                                        :class="{ 'is-invalid': validationErrorForField('email') }"
                                        type="email"
                                        :maxlength="applicationData.constraints.email_max_length"
                                        autocomplete="email"
                                        required
                                    >
                                    <div class="invalid-feedback">{{ validationErrorForField('email') }}</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="password">密碼</label>
                                    <input
                                        id="password"
                                        v-model="authenticationForm.password"
                                        class="form-control form-control-lg"
                                        :class="{ 'is-invalid': validationErrorForField('password') }"
                                        type="password"
                                        :autocomplete="isLoginPage ? 'current-password' : 'new-password'"
                                        required
                                    >
                                    <div class="invalid-feedback">{{ validationErrorForField('password') }}</div>
                                    <div v-if="!isLoginPage && !validationErrorForField('password')" class="form-text">
                                        密碼至少需要 {{ applicationData.constraints.password_min_length }} 個字元。
                                    </div>
                                </div>

                                <div v-if="!isLoginPage" class="mb-4">
                                    <label class="form-label fw-semibold" for="password_confirmation">確認密碼</label>
                                    <input
                                        id="password_confirmation"
                                        v-model="authenticationForm.password_confirmation"
                                        class="form-control form-control-lg"
                                        type="password"
                                        autocomplete="new-password"
                                        required
                                    >
                                </div>

                                <div v-if="isLoginPage" class="form-check mb-4">
                                    <input id="remember" v-model="authenticationForm.remember" class="form-check-input" type="checkbox">
                                    <label class="form-check-label" for="remember">記住我</label>
                                </div>

                                <button class="btn btn-primary btn-lg w-100" type="submit" :disabled="authenticationSubmissionInProgress">
                                    <span v-if="authenticationSubmissionInProgress" class="spinner-border spinner-border-sm me-2"></span>
                                    {{ authenticationSubmissionInProgress ? '處理中…' : isLoginPage ? '登入' : '建立帳號' }}
                                </button>
                            </form>

                            <hr class="my-4">
                            <p class="text-center text-secondary mb-0">
                                {{ isLoginPage ? '還沒有帳號？' : '已經是會員？' }}
                                <a
                                    class="fw-semibold text-decoration-none"
                                    :href="isLoginPage ? applicationData.routes.register : applicationData.routes.login"
                                >{{ isLoginPage ? '立即註冊' : '前往登入' }}</a>
                            </p>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>
</template>
