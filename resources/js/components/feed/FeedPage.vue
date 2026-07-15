<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { apiRequest } from '../../services/http';
import PostCard from './PostCard.vue';
import PostComposer from './PostComposer.vue';

const props = defineProps({
    app: { type: Object, required: true },
    csrfToken: { type: String, required: true },
});

const posts = ref([]);
const nextCursor = ref(null);
const hasMore = ref(true);
const loading = ref(false);
const errorMessage = ref('');
const sentinel = ref(null);
let observer = null;

async function loadMore() {
    if (loading.value || !hasMore.value) return;

    loading.value = true;
    errorMessage.value = '';

    try {
        const url = new URL(props.app.routes.feed, window.location.origin);
        if (nextCursor.value) url.searchParams.set('cursor', nextCursor.value);

        const response = await apiRequest(url.toString());
        posts.value.push(...response.data);
        nextCursor.value = response.meta.next_cursor;
        hasMore.value = response.meta.has_more;
    } catch (error) {
        errorMessage.value = error.message;
    } finally {
        loading.value = false;
    }
}

function prependPost(post) {
    posts.value.unshift(post);
}

function replacePost(updatedPost) {
    const index = posts.value.findIndex((post) => post.id === updatedPost.id);
    if (index !== -1) posts.value[index] = updatedPost;
}

onMounted(() => {
    observer = new IntersectionObserver(
        ([entry]) => {
            if (entry.isIntersecting) loadMore();
        },
        { rootMargin: '120px 0px' },
    );

    if (sentinel.value) observer.observe(sentinel.value);
    loadMore();
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <main class="feed-page">
        <div class="feed-layout container">
            <header class="feed-heading">
                <p class="feed-heading__eyebrow mb-1">你的動態牆</p>
                <h1 class="h3 fw-bold mb-1">嗨，{{ app.user.name }}</h1>
                <p class="text-secondary mb-0">看看大家的新消息，或分享你此刻的想法。</p>
            </header>

            <PostComposer
                :user="app.user"
                :endpoint="app.routes.post_create"
                :csrf-token="csrfToken"
                :max-length="app.constraints.post_max_length"
                @created="prependPost"
            />

            <section class="feed-list" aria-label="貼文列表">
                <PostCard
                    v-for="post in posts"
                    :key="post.id"
                    :post="post"
                    :current-user="app.user"
                    :routes="app.routes"
                    :csrf-token="csrfToken"
                    :comment-max-length="app.constraints.comment_max_length"
                    @updated="replacePost"
                />
            </section>

            <div v-if="!loading && !posts.length && !errorMessage" class="empty-feed surface-card text-center">
                <div class="empty-feed__icon">W</div>
                <h2 class="h5 fw-bold">動態牆還很安靜</h2>
                <p class="text-secondary mb-0">成為第一個分享近況的人吧。</p>
            </div>

            <div v-if="errorMessage" class="alert alert-danger d-flex justify-content-between align-items-center">
                <span>{{ errorMessage }}</span>
                <button class="btn btn-sm btn-outline-danger" type="button" @click="loadMore">重試</button>
            </div>

            <div ref="sentinel" class="feed-sentinel" aria-hidden="true"></div>
            <div v-if="loading" class="feed-loading text-center text-secondary">
                <span class="spinner-border spinner-border-sm me-2"></span>載入更多貼文…
            </div>
            <p v-else-if="posts.length && !hasMore" class="feed-end text-center text-secondary mb-0">
                你已經看完所有貼文了
            </p>
        </div>
    </main>
</template>
