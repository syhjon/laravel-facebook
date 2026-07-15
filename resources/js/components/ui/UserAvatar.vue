<script setup>
defineProps({
    initials: { type: String, required: true },
    size: { type: String, default: 'md' },
});
</script>

<template>
    <span class="user-avatar" :class="`user-avatar--${size}`" aria-hidden="true">
        {{ initials }}
    </span>
</template>
