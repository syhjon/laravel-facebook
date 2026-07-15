<script setup>
import UserAvatar from '../ui/UserAvatar.vue';

defineProps({ comments: { type: Array, required: true } });
</script>

<template>
    <div v-if="comments.length" class="comment-list">
        <div v-for="comment in comments" :key="comment.id" class="comment-item d-flex gap-2">
            <UserAvatar :initials="comment.author.initials" size="sm" />
            <div class="comment-bubble">
                <div class="d-flex align-items-baseline justify-content-between gap-3">
                    <span class="small fw-bold">{{ comment.author.name }}</span>
                    <time class="comment-time">{{ comment.created_at }}</time>
                </div>
                <p class="mb-0">{{ comment.body }}</p>
            </div>
        </div>
    </div>
</template>
