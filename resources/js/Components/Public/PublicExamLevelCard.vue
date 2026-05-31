<script setup lang="ts">
const props = withDefaults(defineProps<{
    eyebrow: string;
    title: string;
    progressLabel: string;
    progressValue: number;
    numeral: string;
    active?: boolean;
    completed?: boolean;
    size?: 'mobile' | 'tablet' | 'desktop';
    activeTone?: 'accent' | 'secondary';
    expandActive?: boolean;
}>(), {
    active: false,
    completed: false,
    size: 'desktop',
    activeTone: 'accent',
    expandActive: false,
});

const rootClasses = {
    mobile: 'rounded-[1.8rem] p-4 min-h-[9.5rem]',
    tablet: 'rounded-[2rem] p-5 min-h-[10.75rem]',
    desktop: 'rounded-[2rem] p-7 min-h-[12rem]',
} as const;

const activeClasses = {
    accent: 'border-[color:color-mix(in_srgb,var(--shell-accent)_50%,white)] bg-[var(--shell-accent)] text-white shadow-[0_22px_44px_color-mix(in_srgb,var(--shell-accent)_28%,transparent)] ring-4 ring-[color:color-mix(in_srgb,var(--shell-accent-soft)_60%,white)] ring-offset-4 ring-offset-[var(--shell-bg)]',
    secondary: 'border-[color:color-mix(in_srgb,var(--shell-secondary)_42%,white)] bg-[var(--shell-secondary)] text-white shadow-[0_22px_44px_color-mix(in_srgb,var(--shell-secondary)_24%,transparent)] ring-4 ring-[color:color-mix(in_srgb,var(--shell-secondary-soft)_72%,white)] ring-offset-4 ring-offset-[var(--shell-bg)]',
} as const;
</script>

<template>
    <button
        type="button"
        class="app-interactive relative flex min-h-0 flex-col items-start overflow-hidden border text-left"
        :class="[
            rootClasses[props.size],
            props.expandActive && props.size === 'mobile'
                ? (props.active ? 'col-span-2' : 'col-span-1')
                : '',
            props.active
                ? activeClasses[props.activeTone]
                : 'border-[var(--shell-border)] bg-[var(--shell-surface)] text-[var(--shell-text)] shadow-[0_12px_26px_rgba(37,47,61,0.05)]',
        ]"
    >
        <div class="absolute -bottom-5 -right-2 text-[7.5rem] font-black leading-none opacity-[0.08]">{{ numeral }}</div>
        <span class="relative z-10 text-[10px] font-black uppercase tracking-[0.22em]" :class="props.active ? 'text-white/75' : 'text-[var(--shell-muted)]'">
            {{ eyebrow }}
        </span>
        <h3
            class="relative z-10 mt-3 font-extrabold leading-none"
            :class="props.size === 'mobile' ? 'text-[2rem]' : props.size === 'tablet' ? 'text-[2.35rem]' : 'text-[2.9rem]'"
        >
            {{ title }}
        </h3>
        <div class="relative z-10 mt-auto w-full pt-5">
            <div class="h-2 w-full rounded-full" :class="props.active ? 'bg-white/20' : 'app-neutral-bar'">
                <div
                    class="h-full rounded-full"
                    :class="props.active ? 'bg-[var(--shell-accent-soft)]' : props.completed ? 'bg-[var(--shell-accent)]' : 'bg-[var(--shell-surface-alt)]'"
                    :style="{ width: `${props.progressValue}%` }"
                ></div>
            </div>
            <p class="mt-3 text-sm font-black uppercase tracking-[0.08em]" :class="props.active ? 'text-white' : props.completed ? 'text-[var(--shell-accent)]' : 'text-[var(--shell-muted)]'">
                {{ progressLabel }}
            </p>
            <div v-if="props.size !== 'desktop' && props.active" class="mt-2 h-1.5 w-1.5 rounded-full bg-white/90"></div>
        </div>
    </button>
</template>
