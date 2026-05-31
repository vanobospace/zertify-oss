<script setup lang="ts">
const props = withDefaults(defineProps<{
    label: string;
    value: string;
    size?: 'compact' | 'default';
}>(), {
    size: 'default',
});

const sizeClasses = {
    compact: {
        card: 'rounded-[1rem] px-4 py-4',
        label: 'text-[10px] tracking-[0.14em]',
        value: 'mt-1 text-4xl',
    },
    default: {
        card: 'rounded-[1.25rem] px-5 py-4',
        label: 'text-xs tracking-[0.18em]',
        value: 'mt-2 text-2xl',
    },
} as const;
</script>

<template>
    <div class="shell-card" :class="sizeClasses[props.size].card">
        <div class="font-bold uppercase text-[var(--shell-muted)]" :class="sizeClasses[props.size].label">{{ label }}</div>
        <div class="font-extrabold text-[var(--shell-text)]" :class="sizeClasses[props.size].value">{{ value }}</div>
    </div>
</template>
