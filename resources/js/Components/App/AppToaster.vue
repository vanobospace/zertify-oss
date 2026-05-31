<script setup lang="ts">
import 'vue-sonner/style.css';

import { Toaster } from 'vue-sonner';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const theme = ref<'light' | 'dark'>('light');

let themeObserver: MutationObserver | null = null;

const syncTheme = (): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const root = document.documentElement;
    const isDark =
        root.classList.contains('dark') || root.dataset.theme === 'dark';
    theme.value = isDark ? 'dark' : 'light';
};

onMounted(() => {
    syncTheme();

    if (typeof document === 'undefined') {
        return;
    }

    themeObserver = new MutationObserver(syncTheme);
    themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class', 'data-theme'],
    });
});

onBeforeUnmount(() => {
    themeObserver?.disconnect();
});
</script>

<template>
    <Toaster
        close-button
        :theme="theme"
        position="top-center"
        rich-colors
        offset="20px"
    />
</template>
