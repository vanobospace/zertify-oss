<script setup lang="ts">
const props = defineProps<{
    primaryLabel: string;
    primaryDisabled?: boolean;
}>();

const emit = defineEmits<{
    primary: [];
    secondary: [];
}>();
</script>

<template>
    <div class="flex items-center gap-3">
        <button
            type="button"
            class="grid h-14 w-14 shrink-0 place-items-center rounded-[1.4rem] border border-[color:color-mix(in_srgb,var(--shell-border)_88%,transparent)] bg-[var(--shell-surface)] text-[var(--shell-muted)] transition-all active:scale-95"
            @click="emit('secondary')"
        >
            <span class="text-2xl">×</span>
        </button>
        <button
            type="button"
            class="flex h-14 flex-1 items-center justify-center gap-2 rounded-[1.4rem] bg-[var(--shell-accent)] px-6 text-lg font-black text-white shadow-[0_18px_36px_color-mix(in_srgb,var(--shell-accent)_20%,transparent)] transition-all active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-55"
            :disabled="props.primaryDisabled"
            @click="emit('primary')"
        >
            {{ props.primaryLabel }}
            <span>→</span>
        </button>
    </div>
</template>
