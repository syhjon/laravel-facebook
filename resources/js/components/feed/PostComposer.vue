<script setup>
import { ref } from 'vue';
import { apiRequest } from '../../services/http';
import UserAvatar from '../ui/UserAvatar.vue';

const componentProperties = defineProps({
    user: { type: Object, required: true },
    endpoint: { type: String, required: true },
    csrfToken: { type: String, required: true },
    maxLength: { type: Number, required: true },
});
const emitComponentEvent = defineEmits(['created']);
const postBody = ref('');
const postSubmissionInProgress = ref(false);
const errorMessage = ref('');

async function submitPost() {
    if (!postBody.value.trim() || postSubmissionInProgress.value) return;

    postSubmissionInProgress.value = true;
    errorMessage.value = '';

    try {
        const postResponsePayload = await apiRequest(componentProperties.endpoint, {
            method: 'POST',
            requestBody: { body: postBody.value.trim() },
            csrfToken: componentProperties.csrfToken,
        });
        postBody.value = '';
        emitComponentEvent('created', postResponsePayload.data);
    } catch (error) {
        errorMessage.value = error.errors?.body?.[0] ?? error.message;
    } finally {
        postSubmissionInProgress.value = false;
    }
}
</script>

<template>
    <section class="surface-card post-composer">
        <div class="d-flex gap-3">
            <UserAvatar :initials="user.initials" />
            <div class="flex-grow-1">
                <textarea
                    v-model="postBody"
                    class="post-composer__input form-control"
                    :maxlength="maxLength"
                    :placeholder="`${user.name}，分享你正在想的事…`"
                    aria-label="貼文內容"
                ></textarea>
                <div v-if="errorMessage" class="small text-danger mt-2">{{ errorMessage }}</div>
                <div class="post-composer__footer d-flex align-items-center justify-content-between mt-3">
                    <span class="small text-secondary">{{ postBody.length }} / {{ maxLength }}</span>
                    <button
                        class="btn btn-primary px-4"
                        type="button"
                        :disabled="!postBody.trim() || postSubmissionInProgress"
                        @click="submitPost"
                    >
                        <span v-if="postSubmissionInProgress" class="spinner-border spinner-border-sm me-2"></span>
                        {{ postSubmissionInProgress ? '發布中…' : '發布貼文' }}
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
