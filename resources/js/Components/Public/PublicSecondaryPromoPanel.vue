<script setup lang="ts">
const props = withDefaults(defineProps<{
    kicker: string;
    title: string;
    body: string;
    size?: 'default' | 'large';
}>(), {
    size: 'default',
});

const sizeClasses = {
    default: {
        card: 'rounded-[1.5rem] p-6 md:rounded-[2rem]',
        title: 'mt-3 text-2xl md:text-3xl',
        body: 'mt-2 text-base leading-7',
    },
    large: {
        card: 'rounded-[2rem] p-8',
        title: 'mt-4 text-[2.2rem] xl:text-4xl',
        body: 'mt-6 text-lg leading-8',
    },
} as const;
</script>

<template>
    <section class="app-secondary-panel" :class="sizeClasses[props.size].card">
        <p class="text-xs font-bold uppercase tracking-[0.22em] text-white/70">{{ kicker }}</p>
        <h2 class="font-extrabold leading-tight" :class="sizeClasses[props.size].title">{{ title }}</h2>
        <p class="text-white/85" :class="sizeClasses[props.size].body">{{ body }}</p>
        <slot />
    </section>
</template>
