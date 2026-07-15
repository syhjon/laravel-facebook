<script setup>
import { ref } from 'vue';

const count = ref(0);
</script>

<template>
    <main class="flex min-h-screen items-center justify-center bg-slate-950 px-6 text-white">
        <section class="text-center">
            <p class="mb-3 text-sm font-medium tracking-widest text-emerald-400 uppercase">
                Laravel + Vite + Vue
            </p>
            <h1 class="text-4xl font-bold sm:text-5xl">專案已準備完成</h1>
            <p class="mt-4 text-slate-400">你可以從 resources/js/App.vue 開始開發。</p>
            <button
                class="mt-8 rounded-lg bg-emerald-500 px-5 py-2.5 font-semibold text-slate-950 transition hover:bg-emerald-400"
                type="button"
                @click="count++"
            >
                測試 Vue：{{ count }}
            </button>
        </section>
    </main>
</template>
