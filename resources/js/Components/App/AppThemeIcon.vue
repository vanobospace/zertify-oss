<script setup lang="ts">
defineProps<{
    mode: 'light' | 'dark' | 'system';
}>();
</script>

<template>
    <svg
        v-if="mode === 'light'"
        class="h-[1.4rem] w-[1.4rem]"
        viewBox="0 0 24 24"
        fill="none"
        aria-hidden="true"
    >
        <circle cx="12" cy="12" r="4.25" fill="currentColor" />
        <path
            d="M12 2.75v2.5M12 18.75v2.5M21.25 12h-2.5M5.25 12h-2.5M18.54 5.46l-1.77 1.77M7.23 16.77l-1.77 1.77M18.54 18.54l-1.77-1.77M7.23 7.23 5.46 5.46"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
        />
    </svg>

    <svg
        v-else-if="mode === 'dark'"
        class="h-[1.3rem] w-[1.3rem]"
        viewBox="0 0 24 24"
        fill="none"
        aria-hidden="true"
    >
        <path
            d="M15.2 3.1a8.95 8.95 0 1 0 5.7 15.7A9.7 9.7 0 0 1 15.2 3.1Z"
            fill="currentColor"
        />
    </svg>

    <svg
        v-else
        class="h-[1.34rem] w-[1.34rem]"
        viewBox="0 0 24 24"
        fill="none"
        aria-hidden="true"
    >
        <circle
            cx="12"
            cy="12"
            r="7.25"
            stroke="currentColor"
            stroke-width="1.8"
        />
        <path d="M12 4.75a7.25 7.25 0 0 1 0 14.5Z" fill="currentColor" />
    </svg>
</template>
