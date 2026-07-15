<script setup>
import { ref } from 'vue';
import { apiRequest } from '../../services/http';
import UserAvatar from '../ui/UserAvatar.vue';

const props = defineProps({
    user: { type: Object, required: true },
    endpoint: { type: String, required: true },
    csrfToken: { type: String, required: true },
    maxLength: { type: Number, required: true },
});
const emit = defineEmits(['created']);
const body = ref('');
const submitting = ref(false);
const errorMessage = ref('');

async function submit() {
    if (!body.value.trim() || submitting.value) return;

    submitting.value = true;
    errorMessage.value = '';

    try {
        const response = await apiRequest(props.endpoint, {
            method: 'POST',
            body: { body: body.value.trim() },
            csrfToken: props.csrfToken,
        });
        body.value = '';
        emit('created', response.data);
    } catch (error) {
        errorMessage.value = error.errors?.body?.[0] ?? error.message;
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <section class="surface-card post-composer">
        <div class="d-flex gap-3">
            <UserAvatar :initials="user.initials" />
            <div class="flex-grow-1">
                <textarea
                    v-model="body"
                    class="post-composer__input form-control"
                    :maxlength="maxLength"
                    :placeholder="`${user.name}，分享你正在想的事…`"
                    aria-label="貼文內容"
                ></textarea>
                <div v-if="errorMessage" class="small text-danger mt-2">{{ errorMessage }}</div>
                <div class="post-composer__footer d-flex align-items-center justify-content-between mt-3">
                    <span class="small text-secondary">{{ body.length }} / {{ maxLength }}</span>
                    <button
                        class="btn btn-primary px-4"
                        type="button"
                        :disabled="!body.trim() || submitting"
                        @click="submit"
                    >
                        <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                        {{ submitting ? '發布中…' : '發布貼文' }}
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
