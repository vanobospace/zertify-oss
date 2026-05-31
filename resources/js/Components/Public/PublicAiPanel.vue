<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const props = withDefaults(defineProps<{
    kicker: string;
    title: string;
    bodyBefore: string;
    bodyHighlight: string;
    bodyAfter: string;
    primaryHref?: string;
    primaryLabel?: string;
    secondaryHref?: string;
    secondaryLabel?: string;
    size?: 'compact' | 'default' | 'tablet' | 'large';
}>(), {
    primaryHref: '',
    primaryLabel: '',
    secondaryHref: '',
    secondaryLabel: '',
    size: 'default',
});

const sizeClasses = {
    compact: {
        card: 'rounded-[2rem] p-6',
        title: 'text-[2.3rem]',
        body: 'mt-4 text-[1.05rem] leading-8',
        actions: 'mt-5 flex flex-col gap-3 sm:flex-row',
        button: 'w-full rounded-xl px-6 py-4 text-center text-lg',
    },
    default: {
        card: 'rounded-[2.2rem] p-7',
        title: 'text-[2.15rem]',
        body: 'mt-4 text-base leading-7',
        actions: 'mt-5 flex gap-3',
        button: 'w-full rounded-xl px-6 py-3 text-center text-lg',
    },
    tablet: {
        card: 'rounded-[2.25rem] p-8',
        title: 'text-[2.65rem]',
        body: 'mt-5 text-[1.05rem] leading-8',
        actions: 'mt-6 flex flex-wrap gap-3',
        button: 'rounded-xl px-7 py-3.5 text-center text-lg',
    },
    large: {
        card: 'rounded-[2.5rem] p-10',
        title: 'mt-6 text-[3.65rem]',
        body: 'mt-6 max-w-5xl text-[1.85rem] leading-10',
        actions: 'mt-10 flex flex-wrap gap-4',
        button: 'rounded-xl px-8 py-4 text-lg',
    },
} as const;
</script>

<template>
    <section class="app-frost-panel" :class="sizeClasses[props.size].card">
        <div v-if="props.size !== 'large'" class="flex items-center gap-3">
            <div class="grid h-11 w-11 place-items-center rounded-2xl bg-[var(--shell-highlight)] text-lg text-[var(--shell-success-text)]">✹</div>
            <div>
                <p class="app-kicker">{{ kicker }}</p>
                <h2 class="font-extrabold text-[var(--shell-text)]" :class="sizeClasses[props.size].title">{{ title }}</h2>
            </div>
        </div>
        <template v-else>
            <p class="app-kicker">{{ kicker }}</p>
            <h2 class="font-extrabold text-[var(--shell-text)]" :class="sizeClasses[props.size].title">{{ title }}</h2>
        </template>

        <p class="text-[var(--shell-muted)]" :class="sizeClasses[props.size].body">
            {{ bodyBefore }}<span class="app-highlight-term">{{ bodyHighlight }}</span>{{ bodyAfter }}
        </p>

        <div :class="sizeClasses[props.size].actions">
            <Link
                v-if="primaryHref && primaryLabel"
                :href="primaryHref"
                class="app-btn-primary block font-bold text-white"
                :class="sizeClasses[props.size].button"
            >
                {{ primaryLabel }}
            </Link>
            <Link
                v-if="secondaryHref && secondaryLabel"
                :href="secondaryHref"
                class="app-btn-secondary block font-bold"
                :class="sizeClasses[props.size].button"
            >
                {{ secondaryLabel }}
            </Link>
        </div>
    </section>
</template>
