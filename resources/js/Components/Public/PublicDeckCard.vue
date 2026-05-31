<script setup lang="ts">
const props = withDefaults(defineProps<{
    title: string;
    stat: string;
    icon: string;
    progressClass: string;
    badgeClass: string;
    size?: 'compact' | 'default' | 'tablet' | 'large';
}>(), {
    size: 'default',
});

const sizeClasses = {
    compact: {
        card: 'rounded-[1.5rem] p-4 gap-4',
        iconWrap: 'h-16 w-16 rounded-2xl text-2xl',
        title: 'text-[clamp(2rem,8vw,2.4rem)]',
        stat: 'px-3 py-1 text-[10px] tracking-[0.1em]',
    },
    default: {
        card: 'rounded-[1.6rem] px-5 py-5 gap-4',
        iconWrap: 'h-14 w-14 rounded-2xl text-xl',
        title: 'text-[2rem]',
        stat: 'px-3 py-1 text-[10px] tracking-[0.1em]',
    },
    tablet: {
        card: 'rounded-[1.75rem] p-5 gap-4',
        iconWrap: 'h-14 w-14 rounded-2xl text-xl',
        title: 'text-[1.65rem]',
        stat: 'px-3 py-1 text-[10px] tracking-[0.1em]',
    },
    large: {
        card: 'rounded-[1.8rem] p-6',
        iconWrap: 'h-14 w-14 rounded-2xl text-2xl',
        title: 'mt-6 text-[2rem]',
        stat: 'px-3 py-1 text-[10px] tracking-[0.1em]',
    },
} as const;
</script>

<template>
    <article class="app-soft-card min-w-0 overflow-hidden" :class="sizeClasses[props.size].card">
        <template v-if="props.size === 'large'">
            <div class="app-icon-tile grid place-items-center" :class="sizeClasses[props.size].iconWrap">{{ icon }}</div>
            <h3 class="font-bold text-[var(--shell-text)]" :class="sizeClasses[props.size].title">{{ title }}</h3>
            <div class="mt-3 flex items-center justify-end text-sm text-[var(--shell-muted)]">
                <span class="shrink-0 rounded-full font-bold uppercase" :class="[badgeClass, sizeClasses[props.size].stat]">{{ stat }}</span>
            </div>
            <div class="app-neutral-bar mt-6 h-1.5 w-full rounded-full">
                <div class="h-full rounded-full" :class="progressClass"></div>
            </div>
        </template>
        <template v-else>
            <div class="flex items-center" :class="sizeClasses[props.size].card.includes('gap-4') ? 'gap-4' : 'gap-3'">
                <div class="app-icon-tile grid shrink-0 place-items-center" :class="sizeClasses[props.size].iconWrap">{{ icon }}</div>
                <div class="min-w-0 flex-1">
                    <div class="flex min-w-0 items-start justify-between gap-2">
                        <h3 class="min-w-0 flex-1 truncate font-extrabold leading-none text-[var(--shell-text)]" :class="sizeClasses[props.size].title">{{ title }}</h3>
                        <span class="shrink-0 rounded-full font-bold uppercase" :class="[badgeClass, sizeClasses[props.size].stat]">{{ stat }}</span>
                    </div>
                    <div class="app-neutral-bar mt-3 h-1.5 w-full rounded-full">
                        <div class="h-full rounded-full" :class="progressClass"></div>
                    </div>
                </div>
            </div>
        </template>
    </article>
</template>
