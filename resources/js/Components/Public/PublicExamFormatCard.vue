<script setup lang="ts">
const props = withDefaults(defineProps<{
    category: string;
    title: string;
    body: string;
    icon: string;
    active?: boolean;
    size?: 'mobile' | 'tablet' | 'desktop';
    badge?: string;
}>(), {
    active: false,
    size: 'desktop',
    badge: '',
});

const sizeClasses = {
    mobile: {
        card: 'rounded-[1.8rem] gap-4 transition-[padding,min-height,transform,box-shadow,background-color,color,border-color] duration-200',
        iconWrap: 'rounded-[1.25rem] transition-[height,width,background-color,color] duration-200',
        title: 'transition-[font-size,line-height] duration-200',
    },
    tablet: {
        card: 'rounded-[1.8rem] p-5 gap-4',
        iconWrap: 'h-10 w-10 rounded-[0.95rem]',
        title: 'text-[1.55rem]',
    },
    desktop: {
        card: 'rounded-[1.6rem] p-5 gap-4',
        iconWrap: 'h-10 w-10 rounded-[0.95rem]',
        title: 'text-[1.85rem]',
    },
} as const;
</script>

<template>
    <button
        type="button"
        class="app-interactive flex w-full items-center text-left"
        :class="[
            sizeClasses[props.size].card,
            props.size === 'mobile'
                ? (props.active ? 'min-h-[10.2rem] p-5' : 'min-h-[7.6rem] p-4')
                : '',
            props.active
                ? 'border border-[color:color-mix(in_srgb,var(--shell-secondary)_28%,transparent)] bg-[var(--shell-secondary)] text-[var(--shell-secondary-text)] shadow-[0_18px_40px_color-mix(in_srgb,var(--shell-secondary)_20%,transparent)]'
                : 'border border-[color:color-mix(in_srgb,var(--shell-border)_88%,transparent)] bg-[var(--shell-surface)] text-[var(--shell-text)] shadow-[0_10px_24px_rgba(37,47,61,0.04)]',
        ]"
    >
        <div
            class="grid shrink-0 place-items-center"
            :class="[
                sizeClasses[props.size].iconWrap,
                props.size === 'mobile'
                    ? (props.active ? 'h-14 w-14' : 'h-10 w-10')
                    : '',
                props.active ? 'bg-white/15 text-[var(--shell-secondary-soft)]' : 'bg-[var(--shell-surface-alt)] text-[var(--shell-muted)]',
            ]"
        >
            <span :class="props.size === 'mobile' ? (props.active ? 'text-[1.45rem]' : 'text-[1.1rem]') : 'text-[1.35rem]'">{{ icon }}</span>
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex min-w-0 items-start justify-between gap-3">
                <div class="min-w-0">
                    <span class="block text-[10px] font-black uppercase tracking-[0.22em]" :class="props.active ? 'text-white/72' : 'text-[var(--shell-muted)]'">
                        {{ category }}
                    </span>
                    <h3
                        class="mt-1 break-words font-extrabold leading-tight"
                        :class="[
                            sizeClasses[props.size].title,
                            props.size === 'mobile'
                                ? (props.active ? 'text-[2.25rem]' : 'text-[1.8rem]')
                                : props.size === 'tablet'
                                    ? 'text-[1.55rem]'
                                    : 'text-[1.85rem]',
                        ]"
                    >
                        {{ title }}
                    </h3>
                </div>
            </div>
            <p
                class="mt-2 overflow-hidden text-sm leading-6 transition-[max-height,opacity] duration-200"
                :class="[
                    props.active ? 'text-white/88' : 'text-[var(--shell-muted)]',
                    props.size === 'mobile'
                        ? (props.active ? 'max-h-20 opacity-100' : 'max-h-10 opacity-88')
                        : 'max-h-24 opacity-100',
                ]"
            >
                {{ body }}
            </p>
        </div>
    </button>
</template>
