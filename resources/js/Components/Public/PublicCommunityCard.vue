<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const props = withDefaults(defineProps<{
    title: string;
    body: string;
    ctaHref: string;
    ctaLabel: string;
    countLabel?: string;
    size?: 'compact' | 'default' | 'tablet' | 'large';
}>(), {
    countLabel: '+12',
    size: 'default',
});

const sizeClasses = {
    compact: {
        card: 'rounded-[1.9rem] p-6',
        title: 'text-[3.05rem]',
        body: 'mt-3 text-lg leading-8',
        button: 'mt-5 rounded-xl px-6 py-3 text-lg',
        avatar: 'h-10 w-10',
    },
    default: {
        card: 'rounded-[2rem] p-7',
        title: 'text-[2.65rem]',
        body: 'mt-4 text-base leading-7',
        button: 'mt-6 rounded-xl px-5 py-3',
        avatar: 'h-9 w-9',
    },
    tablet: {
        card: 'rounded-[2.1rem] p-7',
        title: 'max-w-[9rem] text-[2.4rem]',
        body: 'mt-5 text-[1.02rem] leading-7',
        button: 'mt-6 rounded-xl px-5 py-3.5 text-base',
        avatar: 'h-9 w-9',
    },
    large: {
        card: 'rounded-[2rem] p-8',
        title: 'max-w-[10rem] text-[3.05rem]',
        body: 'mt-8 text-lg leading-8',
        button: 'mt-8 rounded-xl px-6 py-4 text-lg',
        avatar: 'h-10 w-10',
    },
} as const;
</script>

<template>
    <section class="app-secondary-panel" :class="sizeClasses[props.size].card">
        <h2 class="font-extrabold leading-[0.98]" :class="sizeClasses[props.size].title">{{ title }}</h2>
        <p class="text-white/86" :class="sizeClasses[props.size].body">{{ body }}</p>
        <div class="mt-6 flex items-center -space-x-3">
            <div class="rounded-full border-2 border-[var(--shell-secondary)] bg-stone-200" :class="sizeClasses[props.size].avatar"></div>
            <div class="rounded-full border-2 border-[var(--shell-secondary)] bg-stone-300" :class="sizeClasses[props.size].avatar"></div>
            <div class="rounded-full border-2 border-[var(--shell-secondary)] bg-stone-400" :class="sizeClasses[props.size].avatar"></div>
            <div class="grid place-items-center rounded-full border-2 border-[var(--shell-secondary)] bg-[var(--shell-secondary-soft)] text-xs font-black text-[var(--shell-secondary-text)]" :class="sizeClasses[props.size].avatar">
                {{ countLabel }}
            </div>
        </div>
        <Link :href="ctaHref" class="app-secondary-panel__button block w-full text-center font-bold" :class="sizeClasses[props.size].button">
            {{ ctaLabel }}
        </Link>
    </section>
</template>
