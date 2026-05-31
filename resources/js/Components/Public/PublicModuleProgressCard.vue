<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const props = withDefaults(defineProps<{
    title: string;
    body: string;
    progressLabel: string;
    progressValue: number;
    ctaLabel: string;
    icon: string;
    order: string;
    tone?: 'primary' | 'secondary' | 'tertiary';
    size?: 'mobile' | 'tablet' | 'desktop';
    href?: string | null;
    active?: boolean;
}>(), {
    tone: 'primary',
    size: 'desktop',
    href: null,
    active: false,
});

const toneMap = {
    primary: {
        iconWrap: 'bg-[color:color-mix(in_srgb,var(--shell-accent-soft)_32%,white)] text-[var(--shell-accent)]',
        progress: 'bg-[var(--shell-accent)]',
        accentText: 'text-[var(--shell-accent)]',
        button: 'app-btn-primary',
        border: 'border-[color:color-mix(in_srgb,var(--shell-accent)_28%,transparent)]',
        activeBorder: 'border-[color:color-mix(in_srgb,var(--shell-accent)_28%,transparent)]',
    },
    secondary: {
        iconWrap: 'bg-[color:color-mix(in_srgb,var(--shell-secondary-soft)_24%,white)] text-[var(--shell-secondary)]',
        progress: 'bg-[var(--shell-secondary)]',
        accentText: 'text-[var(--shell-secondary)]',
        button: 'app-secondary-panel app-secondary-panel__button !border-[var(--shell-secondary)] !bg-[var(--shell-secondary)] !text-white shadow-[0_14px_28px_color-mix(in_srgb,var(--shell-secondary)_16%,transparent)]',
        border: 'border-[color:color-mix(in_srgb,var(--shell-secondary)_18%,transparent)]',
        activeBorder: 'border-[color:color-mix(in_srgb,var(--shell-secondary)_30%,transparent)]',
    },
    tertiary: {
        iconWrap: 'bg-[color:color-mix(in_srgb,var(--shell-highlight)_48%,white)] text-[var(--shell-success-text)]',
        progress: 'bg-[var(--shell-accent)]',
        accentText: 'text-[var(--shell-accent)]',
        button: 'app-btn-primary',
        border: 'border-[color:color-mix(in_srgb,var(--shell-accent)_28%,transparent)]',
        activeBorder: 'border-[color:color-mix(in_srgb,var(--shell-accent)_28%,transparent)]',
    },
} as const;

const sizeClasses = {
    mobile: {
        card: 'h-[23rem] rounded-[2rem] p-5',
        iconWrap: 'h-10 w-10 rounded-[0.95rem]',
        title: 'text-[1.8rem]',
        titleWrap: 'h-[4.75rem]',
        action: 'mt-4 w-full rounded-full px-5 py-2.5 text-sm',
        body: 'mt-5 h-[5.75rem]',
    },
    tablet: {
        card: 'min-h-[20.5rem] rounded-[2rem] p-5',
        iconWrap: 'h-9 w-9 rounded-[0.9rem]',
        title: 'text-[1.6rem]',
        titleWrap: 'min-h-[3.9rem]',
        action: 'mt-4 w-full rounded-full px-5 py-2.5 text-sm',
        body: 'mt-3 line-clamp-3 min-h-[5.25rem] flex-1',
    },
    desktop: {
        card: 'min-h-[21.5rem] rounded-[2rem] p-6',
        iconWrap: 'h-10 w-10 rounded-[0.95rem]',
        title: 'text-[1.72rem]',
        titleWrap: 'min-h-[4rem]',
        action: 'mt-4 w-full rounded-full px-5 py-2.5 text-sm',
        body: 'mt-3 line-clamp-3 min-h-[5.5rem] flex-1',
    },
} as const;
</script>

<template>
    <div class="block h-full">
        <article
            class="relative flex h-full min-w-0 origin-center flex-col border bg-[var(--shell-surface)] shadow-[0_12px_28px_rgba(37,47,61,0.05)] transition-[transform,border-color,box-shadow,background-color] duration-200"
            :class="[
                sizeClasses[props.size].card,
                toneMap[props.tone].border,
                'app-interactive cursor-pointer hover:-translate-y-0.5',
                props.active
                    ? props.size === 'mobile'
                        ? `${toneMap[props.tone].activeBorder} shadow-[0_22px_42px_rgba(37,47,61,0.12)]`
                        : `scale-[1.02] ${toneMap[props.tone].activeBorder} shadow-[0_22px_42px_rgba(37,47,61,0.12)]`
                    : '',
            ]"
        >
            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="grid shrink-0 place-items-center" :class="[sizeClasses[props.size].iconWrap, toneMap[props.tone].iconWrap]">
                        <span class="text-[1.15rem]">{{ icon }}</span>
                    </div>
                    <div class="min-w-0" :class="sizeClasses[props.size].titleWrap">
                        <h3
                            class="break-words font-extrabold leading-[1.05] text-[var(--shell-text)] transition-[font-size,transform] duration-200"
                            :class="[
                                sizeClasses[props.size].title,
                                props.active
                                    ? (props.size === 'desktop' ? 'translate-y-[-1px] text-[2.15rem]' : props.size === 'tablet' ? 'translate-y-[-1px] text-[1.95rem]' : '')
                                    : '',
                            ]"
                        >
                            {{ title }}
                        </h3>
                        <p class="mt-1 text-sm font-medium text-[var(--shell-muted)]">{{ progressLabel }}</p>
                    </div>
                </div>
                <span v-if="props.size === 'desktop'" class="text-[2.4rem] font-black leading-none text-[color:color-mix(in_srgb,var(--shell-surface-alt)_94%,#afc3ee)]">
                    {{ order }}
                </span>
            </div>

            <p
                class="text-base leading-7 text-[var(--shell-muted)]"
                :class="[
                    sizeClasses[props.size].body,
                    props.size === 'mobile' ? 'line-clamp-3' : '',
                ]"
            >
                {{ body }}
            </p>

            <div class="mt-3 space-y-2.5">
                <div v-if="props.size !== 'mobile'" class="flex items-center justify-between text-sm font-bold text-[var(--shell-text)]">
                    <span>{{ progressLabel.split(' ')[0] }}</span>
                    <span :class="toneMap[props.tone].accentText">{{ progressValue }}%</span>
                </div>
                <div class="app-neutral-bar h-2 w-full rounded-full">
                    <div class="h-full rounded-full" :class="toneMap[props.tone].progress" :style="{ width: `${props.progressValue}%` }"></div>
                </div>
            </div>

            <div class="mt-3 min-h-[3rem]">
                <Link
                    v-if="props.active && props.href"
                    :href="props.href"
                    class="inline-flex items-center justify-center font-black text-white"
                    :class="[toneMap[props.tone].button, sizeClasses[props.size].action]"
                    @click.stop
                >
                    {{ props.ctaLabel }}
                </Link>
                <button
                    v-else-if="props.active"
                    type="button"
                    disabled
                    class="inline-flex items-center justify-center font-black text-white opacity-45 disabled:cursor-not-allowed"
                    :class="[toneMap[props.tone].button, sizeClasses[props.size].action]"
                    @click.stop
                >
                    {{ props.ctaLabel }}
                </button>
            </div>
        </article>
    </div>
</template>
