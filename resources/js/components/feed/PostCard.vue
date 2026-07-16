<script setup>
import { ref } from 'vue';
import { apiRequest, buildPostRoute } from '../../services/http';
import UserAvatar from '../ui/UserAvatar.vue';
import CommentList from './CommentList.vue';

const componentProperties = defineProps({
    post: { type: Object, required: true },
    currentUser: { type: Object, required: true },
    routes: { type: Object, required: true },
    csrfToken: { type: String, required: true },
    commentMaxLength: { type: Number, required: true },
});
const emitComponentEvent = defineEmits(['updated']);
const commentBody = ref('');
const likeRequestInProgress = ref(false);
const commentRequestInProgress = ref(false);
const showComments = ref(componentProperties.post.comments.length > 0);
const errorMessage = ref('');

async function togglePostLike() {
    if (likeRequestInProgress.value) return;
    likeRequestInProgress.value = true;
    errorMessage.value = '';

    try {
        const likeResponsePayload = await apiRequest(buildPostRoute(componentProperties.routes.post_like_pattern, componentProperties.post.id), {
            method: 'POST',
            csrfToken: componentProperties.csrfToken,
        });
        emitComponentEvent('updated', likeResponsePayload.data);
    } catch (error) {
        errorMessage.value = error.message;
    } finally {
        likeRequestInProgress.value = false;
    }
}

async function submitPostComment() {
    if (!commentBody.value.trim() || commentRequestInProgress.value) return;
    commentRequestInProgress.value = true;
    errorMessage.value = '';

    try {
        const commentResponsePayload = await apiRequest(buildPostRoute(componentProperties.routes.post_comment_pattern, componentProperties.post.id), {
            method: 'POST',
            requestBody: { body: commentBody.value.trim() },
            csrfToken: componentProperties.csrfToken,
        });
        commentBody.value = '';
        showComments.value = true;
        emitComponentEvent('updated', commentResponsePayload.data);
    } catch (error) {
        errorMessage.value = error.errors?.body?.[0] ?? error.message;
    } finally {
        commentRequestInProgress.value = false;
    }
}
</script>

<template>
    <article class="surface-card post-card">
        <header class="post-card__header d-flex gap-3">
            <UserAvatar :initials="post.author.initials" />
            <div class="min-w-0">
                <h2 class="h6 fw-bold mb-1 text-truncate">{{ post.author.name }}</h2>
                <time class="small text-secondary">{{ post.created_at }}</time>
            </div>
        </header>

        <p class="post-card__body">{{ post.body }}</p>

        <div class="post-card__summary d-flex justify-content-between text-secondary small">
            <span>{{ post.likes_count ? `${post.likes_count} 人按讚` : '成為第一個按讚的人' }}</span>
            <button v-if="post.comments_count" class="summary-link" type="button" @click="showComments = !showComments">
                {{ post.comments_count }} 則回覆
            </button>
        </div>

        <div class="post-card__actions d-grid">
            <button
                class="post-action"
                :class="{ 'post-action--active': post.liked }"
                type="button"
                :disabled="likeRequestInProgress"
                @click="togglePostLike"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-4.35-9.33-8.28C.6 9.22 2.3 5 6.36 5c2.1 0 3.37 1.2 4.14 2.2C11.27 6.2 12.54 5 14.64 5c4.06 0 5.76 4.22 3.69 7.72C16 16.65 12 21 12 21Z" /></svg>
                {{ post.liked ? '已按讚' : '讚' }}
            </button>
            <button class="post-action" type="button" @click="showComments = true">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v12H8l-4 4V4Zm3 4h10M7 12h7" /></svg>
                回覆
            </button>
        </div>

        <section v-if="showComments" class="post-comments">
            <CommentList :comments="post.comments" />
            <form class="comment-form d-flex gap-2" @submit.prevent="submitPostComment">
                <UserAvatar :initials="currentUser.initials" size="sm" />
                <div class="comment-form__field flex-grow-1 d-flex align-items-center">
                    <input
                        v-model="commentBody"
                        class="form-control"
                        :maxlength="commentMaxLength"
                        placeholder="寫下你的回覆…"
                        aria-label="回覆內容"
                    >
                    <button type="submit" :disabled="!commentBody.trim() || commentRequestInProgress" aria-label="送出回覆">
                        {{ commentRequestInProgress ? '…' : '送出' }}
                    </button>
                </div>
            </form>
            <div v-if="errorMessage" class="small text-danger mt-2">{{ errorMessage }}</div>
        </section>
    </article>
</template>
