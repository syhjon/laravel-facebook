<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { apiRequest } from '../../services/http';
import PostCard from './PostCard.vue';
import PostComposer from './PostComposer.vue';

const componentProperties = defineProps({
    applicationData: { type: Object, required: true },
    csrfToken: { type: String, required: true },
});

const posts = ref([]);
const nextCursor = ref(null);
const hasMore = ref(true);
const feedRequestInProgress = ref(false);
const errorMessage = ref('');
const feedLoadSentinel = ref(null);
let feedIntersectionObserver = null;

async function loadMorePosts() {
    if (feedRequestInProgress.value || !hasMore.value) return;

    feedRequestInProgress.value = true;
    errorMessage.value = '';

    try {
        const feedEndpointUrl = new URL(componentProperties.applicationData.routes.feed, window.location.origin);
        if (nextCursor.value) feedEndpointUrl.searchParams.set('cursor', nextCursor.value);

        const feedResponsePayload = await apiRequest(feedEndpointUrl.toString());
        posts.value.push(...feedResponsePayload.data);
        nextCursor.value = feedResponsePayload.meta.next_cursor;
        hasMore.value = feedResponsePayload.meta.has_more;
    } catch (error) {
        errorMessage.value = error.message;
    } finally {
        feedRequestInProgress.value = false;
    }
}

function prependCreatedPost(createdPost) {
    posts.value.unshift(createdPost);
}

function replacePost(updatedPost) {
    const postIndex = posts.value.findIndex((post) => post.id === updatedPost.id);
    if (postIndex !== -1) posts.value[postIndex] = updatedPost;
}

onMounted(() => {
    feedIntersectionObserver = new IntersectionObserver(
        ([intersectionEntry]) => {
            if (intersectionEntry.isIntersecting) loadMorePosts();
        },
        { rootMargin: '120px 0px' },
    );

    if (feedLoadSentinel.value) feedIntersectionObserver.observe(feedLoadSentinel.value);
    loadMorePosts();
});

onBeforeUnmount(() => feedIntersectionObserver?.disconnect());
</script>

<template>
    <main class="feed-page">
        <div class="feed-layout container">
            <header class="feed-heading">
                <p class="feed-heading__eyebrow mb-1">你的動態牆</p>
                <h1 class="h3 fw-bold mb-1">嗨，{{ applicationData.user.name }}</h1>
                <p class="text-secondary mb-0">看看大家的新消息，或分享你此刻的想法。</p>
            </header>

            <PostComposer
                :user="applicationData.user"
                :endpoint="applicationData.routes.post_create"
                :csrf-token="csrfToken"
                :max-length="applicationData.constraints.post_max_length"
                @created="prependCreatedPost"
            />

            <section class="feed-list" aria-label="貼文列表">
                <PostCard
                    v-for="post in posts"
                    :key="post.id"
                    :post="post"
                    :current-user="applicationData.user"
                    :routes="applicationData.routes"
                    :csrf-token="csrfToken"
                    :comment-max-length="applicationData.constraints.comment_max_length"
                    @updated="replacePost"
                />
            </section>

            <div v-if="!feedRequestInProgress && !posts.length && !errorMessage" class="empty-feed surface-card text-center">
                <div class="empty-feed__icon">W</div>
                <h2 class="h5 fw-bold">動態牆還很安靜</h2>
                <p class="text-secondary mb-0">成為第一個分享近況的人吧。</p>
            </div>

            <div v-if="errorMessage" class="alert alert-danger d-flex justify-content-between align-items-center">
                <span>{{ errorMessage }}</span>
                <button class="btn btn-sm btn-outline-danger" type="button" @click="loadMorePosts">重試</button>
            </div>

            <div ref="feedLoadSentinel" class="feed-sentinel" aria-hidden="true"></div>
            <div v-if="feedRequestInProgress" class="feed-loading text-center text-secondary">
                <span class="spinner-border spinner-border-sm me-2"></span>載入更多貼文…
            </div>
            <p v-else-if="posts.length && !hasMore" class="feed-end text-center text-secondary mb-0">
                你已經看完所有貼文了
            </p>
        </div>
    </main>
</template>
