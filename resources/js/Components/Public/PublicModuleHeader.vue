<script setup lang="ts">
type ModulePart = {
    key: string;
    label: string;
    disabled?: boolean;
};

const props = defineProps<{
    title: string;
    subtitle: string;
    contextLabel?: string;
    timerLabel?: string;
    parts: ModulePart[];
    activePart: string;
    locked?: boolean;
    lockLabel?: string;
    mobileCompact?: boolean;
}>();

const emit = defineEmits<{
    select: [partKey: string];
}>();
</script>

<template>
    <section class="space-y-5 md:space-y-6">
        <div
            class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
            :class="props.mobileCompact ? 'hidden md:flex' : ''"
        >
            <div>
                <p v-if="props.contextLabel" class="app-kicker">
                    {{ props.contextLabel }}
                </p>
                <h1 class="text-[2.2rem] font-extrabold leading-tight text-[var(--shell-text)] md:text-[2.8rem]">
                    {{ props.title }}
                </h1>
                <p class="mt-2 max-w-3xl text-base leading-7 text-[var(--shell-muted)] md:text-lg">
                    {{ props.subtitle }}
                </p>
            </div>
            <div class="app-accent-reverse flex items-center gap-2 self-start rounded-full px-4 py-2.5">
                <span class="text-sm">◔</span>
                <span class="text-sm font-black tracking-[0.04em] md:text-base">{{ props.timerLabel }}</span>
            </div>
        </div>

        <div class="space-y-3">
            <div class="grid grid-cols-3 gap-2 rounded-[1.5rem] bg-[var(--shell-surface-alt)] p-1.5 md:inline-flex md:min-w-0 md:gap-3 md:rounded-[1.3rem]">
                <button
                    v-for="part in props.parts"
                    :key="part.key"
                    type="button"
                    class="rounded-[1.1rem] px-3 py-3 text-center text-sm font-bold transition-all md:min-w-[7.5rem] md:px-8"
                    :class="[
                        props.activePart === part.key
                            ? 'app-secondary-reverse'
                            : 'text-[var(--shell-muted)]',
                        part.disabled
                            ? 'cursor-not-allowed opacity-55'
                            : 'app-interactive hover:text-[var(--shell-accent)]',
                    ]"
                    :disabled="part.disabled"
                    @click="emit('select', part.key)"
                >
                    {{ part.label }}
                </button>
            </div>

            <p v-if="props.locked && props.lockLabel" class="text-xs font-bold uppercase tracking-[0.18em] text-[var(--shell-secondary)]">
                {{ props.lockLabel }}
            </p>
        </div>
    </section>
</template>
