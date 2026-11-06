<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { apiRequest } from '../../services/http';
import UserAvatar from '../ui/UserAvatar.vue';

const componentProperties = defineProps({
    applicationData: { type: Object, required: true },
    csrfToken: { type: String, required: true },
});

const emitComponentEvent = defineEmits(['profile-updated']);
const profileData = reactive({ ...componentProperties.applicationData.user });
const profileForm = reactive({
    name: profileData.name,
    email: profileData.email,
});
const validationErrors = ref({});
const successMessage = ref('');
const errorMessage = ref('');
const profileRequestInProgress = ref(false);
const profileUpdateInProgress = ref(false);
const profileImageUpdateInProgress = ref(false);
const selectedProfileImage = ref(null);
const profileImagePreviewUrl = ref('');
const profileImageInputElement = ref(null);
const profileImageErrorMessage = ref('');
const displayedProfileImageUrl = computed(() => profileImagePreviewUrl.value || profileData.profile_image_url || '');
const validationErrorForField = (fieldName) => validationErrors.value[fieldName]?.[0] ?? '';

function applyProfile(updatedProfile) {
    Object.assign(profileData, updatedProfile);
    profileForm.name = updatedProfile.name;
    profileForm.email = updatedProfile.email;
    emitComponentEvent('profile-updated', updatedProfile);
}

function clearProfileImagePreview() {
    if (profileImagePreviewUrl.value) {
        URL.revokeObjectURL(profileImagePreviewUrl.value);
    }

    profileImagePreviewUrl.value = '';
    selectedProfileImage.value = null;

    if (profileImageInputElement.value) {
        profileImageInputElement.value.value = '';
    }
}

async function selectProfileImage(fileInputEvent) {
    const selectedFile = fileInputEvent.target.files?.[0] ?? null;

    clearProfileImagePreview();
    profileImageErrorMessage.value = '';

    if (!selectedFile) return;

    selectedProfileImage.value = selectedFile;
    profileImagePreviewUrl.value = URL.createObjectURL(selectedFile);
    await uploadProfileImage();
}

async function uploadProfileImage() {
    if (!selectedProfileImage.value || profileImageUpdateInProgress.value) return;

    profileImageUpdateInProgress.value = true;
    profileImageErrorMessage.value = '';
    successMessage.value = '';

    const profileImageFormData = new FormData();
    profileImageFormData.append('profile_image', selectedProfileImage.value);

    try {
        const profileImageResponsePayload = await apiRequest(
            componentProperties.applicationData.routes.profile_image_update,
            {
                method: 'POST',
                requestBody: profileImageFormData,
                csrfToken: componentProperties.csrfToken,
            },
        );

        applyProfile(profileImageResponsePayload.data);
        clearProfileImagePreview();
        successMessage.value = profileImageResponsePayload.message;
    } catch (error) {
        profileImageErrorMessage.value = error.errors?.profile_image?.[0] ?? error.message;
    } finally {
        profileImageUpdateInProgress.value = false;
    }
}

async function loadProfile() {
    profileRequestInProgress.value = true;
    errorMessage.value = '';

    try {
        const profileResponsePayload = await apiRequest(componentProperties.applicationData.routes.profile_show);
        applyProfile(profileResponsePayload.data);
    } catch (error) {
        errorMessage.value = error.message;
    } finally {
        profileRequestInProgress.value = false;
    }
}

async function updateProfile() {
    if (profileUpdateInProgress.value) return;

    profileUpdateInProgress.value = true;
    validationErrors.value = {};
    successMessage.value = '';
    errorMessage.value = '';

    try {
        const profileResponsePayload = await apiRequest(componentProperties.applicationData.routes.profile_update, {
            method: 'PATCH',
            requestBody: {
                name: profileForm.name.trim(),
                email: profileForm.email.trim(),
            },
            csrfToken: componentProperties.csrfToken,
        });

        applyProfile(profileResponsePayload.data);
        successMessage.value = profileResponsePayload.message;
    } catch (error) {
        validationErrors.value = error.errors ?? {};
        errorMessage.value = error.message;
    } finally {
        profileUpdateInProgress.value = false;
    }
}

onMounted(loadProfile);
onBeforeUnmount(clearProfileImagePreview);
</script>

<template>
    <main class="profile-page">
        <div class="container profile-page__container">
            <header class="profile-heading">
                <p class="profile-heading__eyebrow mb-1">會員中心</p>
                <h1 class="h3 fw-bold mb-1">個人檔案</h1>
                <p class="text-secondary mb-0">管理你的公開姓名與帳號聯絡信箱。</p>
            </header>

            <div v-if="errorMessage && !Object.keys(validationErrors).length" class="alert alert-danger" role="alert">
                {{ errorMessage }}
            </div>

            <div class="profile-layout">
                <aside class="surface-card profile-summary">
                    <div class="profile-summary__avatar">
                        <UserAvatar :initials="profileData.initials" :image-url="displayedProfileImageUrl" />
                    </div>
                    <h2 class="h5 fw-bold mb-1">{{ profileData.name }}</h2>
                    <p class="text-secondary text-break mb-4">{{ profileData.email }}</p>

                    <div class="profile-image-controls">
                        <input
                            id="profile-image"
                            ref="profileImageInputElement"
                            class="visually-hidden"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            :disabled="profileImageUpdateInProgress"
                            @change="selectProfileImage"
                        >
                        <label
                            class="btn btn-sm btn-outline-primary"
                            :class="{ disabled: profileImageUpdateInProgress }"
                            for="profile-image"
                        >
                            <span v-if="profileImageUpdateInProgress" class="spinner-border spinner-border-sm me-1"></span>
                            {{ profileImageUpdateInProgress ? '上傳中…' : '選擇並上傳照片' }}
                        </label>
                        <p class="profile-image-controls__hint mb-0">JPG、PNG 或 WebP，最大 5 MB</p>
                        <p v-if="selectedProfileImage" class="profile-image-controls__filename mb-0">
                            {{ selectedProfileImage.name }}
                        </p>
                        <p v-if="profileImageErrorMessage" class="small text-danger mb-0">
                            {{ profileImageErrorMessage }}
                        </p>
                    </div>

                    <dl class="profile-summary__details mb-0">
                        <div>
                            <dt>會員編號</dt>
                            <dd>#{{ profileData.id }}</dd>
                        </div>
                        <div>
                            <dt>加入日期</dt>
                            <dd>{{ profileData.member_since }}</dd>
                        </div>
                    </dl>
                </aside>

                <section class="surface-card profile-form-card">
                    <div class="profile-form-card__header">
                        <div>
                            <h2 class="h5 fw-bold mb-1">基本資料</h2>
                            <p class="text-secondary mb-0">更新後會同步顯示於導覽列與貼文資訊。</p>
                        </div>
                        <span v-if="profileRequestInProgress" class="spinner-border spinner-border-sm text-primary" aria-label="載入中"></span>
                    </div>

                    <div v-if="successMessage" class="alert alert-success" role="status">
                        {{ successMessage }}
                    </div>

                    <form novalidate @submit.prevent="updateProfile">
                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="profile-name">姓名</label>
                            <input
                                id="profile-name"
                                v-model="profileForm.name"
                                class="form-control form-control-lg"
                                :class="{ 'is-invalid': validationErrorForField('name') }"
                                :maxlength="applicationData.constraints.name_max_length"
                                autocomplete="name"
                                required
                            >
                            <div class="invalid-feedback">{{ validationErrorForField('name') }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="profile-email">Email</label>
                            <input
                                id="profile-email"
                                v-model="profileForm.email"
                                class="form-control form-control-lg"
                                :class="{ 'is-invalid': validationErrorForField('email') }"
                                type="email"
                                :maxlength="applicationData.constraints.email_max_length"
                                autocomplete="email"
                                required
                            >
                            <div class="invalid-feedback">{{ validationErrorForField('email') }}</div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button class="btn btn-primary px-4" type="submit" :disabled="profileUpdateInProgress">
                                <span v-if="profileUpdateInProgress" class="spinner-border spinner-border-sm me-2"></span>
                                {{ profileUpdateInProgress ? '儲存中…' : '儲存變更' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </main>
</template>
