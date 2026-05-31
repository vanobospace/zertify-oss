<script setup lang="ts">
const props = withDefaults(defineProps<{
    title: string;
    subtitle: string;
    periodLabel?: string;
    bars: number[];
    dayLabels: string[];
    highlightedIndex: number;
    size?: 'compact' | 'default' | 'tablet' | 'large';
    accentIcon?: string;
}>(), {
    periodLabel: '',
    size: 'default',
    accentIcon: '',
});

const sizeClasses = {
    compact: {
        card: 'rounded-[1.9rem] p-6',
        title: 'text-4xl',
        chart: 'mt-8 h-44 gap-2',
        labels: 'mt-4 text-[11px] tracking-[0.12em]',
    },
    default: {
        card: 'rounded-[2rem] p-7',
        title: 'text-[2.05rem]',
        chart: 'mt-6 h-36 gap-1.5',
        labels: 'mt-3 text-[10px] tracking-[0.12em]',
    },
    tablet: {
        card: 'rounded-[2.15rem] p-8',
        title: 'text-[2.35rem]',
        chart: 'mt-8 h-44 gap-2',
        labels: 'mt-4 text-[11px] tracking-[0.16em]',
    },
    large: {
        card: 'rounded-[2rem] p-8',
        title: 'text-[2.2rem]',
        chart: 'mt-10 h-64 gap-2',
        labels: 'mt-4 text-xs tracking-[0.18em]',
    },
} as const;
</script>

<template>
    <section class="shell-panel min-w-0 overflow-hidden" :class="sizeClasses[props.size].card">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="font-extrabold text-[var(--shell-text)]" :class="sizeClasses[props.size].title">{{ title }}</h2>
                <p class="shell-muted mt-1 text-sm">{{ subtitle }}</p>
            </div>
            <span
                v-if="periodLabel"
                class="rounded-full bg-[var(--shell-accent-soft)] px-3 py-1 text-[11px] font-bold text-[var(--shell-accent)]"
            >
                {{ periodLabel }}
            </span>
            <span v-else-if="accentIcon" class="text-xl text-[var(--shell-accent)]">{{ accentIcon }}</span>
        </div>

        <div class="grid grid-cols-7 items-end" :class="sizeClasses[props.size].chart">
            <div
                v-for="(bar, index) in bars"
                :key="`${index}-${bar}`"
                class="rounded-t-lg"
                :class="index === highlightedIndex ? 'bg-[var(--shell-accent)]' : 'app-neutral-bar'"
                :style="{ height: `${bar}%` }"
            />
        </div>

        <div class="flex justify-between px-1 font-bold uppercase text-[var(--shell-muted)]" :class="sizeClasses[props.size].labels">
            <span
                v-for="(day, index) in dayLabels"
                :key="`${day}-${index}`"
                :class="index === highlightedIndex ? 'text-[var(--shell-accent)]' : ''"
            >
                {{ day }}
            </span>
        </div>
    </section>
</template>
