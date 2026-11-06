<script setup>
defineProps({
    initials: { type: String, required: true },
    imageUrl: { type: String, default: '' },
    size: { type: String, default: 'md' },
});
</script>

<template>
    <span class="user-avatar" :class="`user-avatar--${size}`" aria-hidden="true">
        <img v-if="imageUrl" :src="imageUrl" alt="">
        <template v-else>{{ initials }}</template>
    </span>
</template>
