<script setup lang="ts">
const props = withDefaults(defineProps<{
    label: string;
    title?: string;
    body: string;
    active?: boolean;
    compact?: boolean;
    extra?: boolean;
    assignedSituations?: string[];
    showMarker?: boolean;
    hideCompactBody?: boolean;
    showInactiveChevron?: boolean;
}>(), {
    active: false,
    compact: false,
    extra: false,
    assignedSituations: () => [],
    showMarker: true,
    hideCompactBody: false,
    showInactiveChevron: false,
});
</script>

<template>
    <article
        class="app-reading-text-card"
        :class="[
            props.active
                ? 'app-reading-text-card--active'
                : '',
            props.compact ? 'min-h-[4.25rem] py-4' : 'min-h-[11.5rem]',
            props.extra ? 'bg-[color:color-mix(in_srgb,var(--shell-secondary-soft)_18%,white)]' : '',
        ]"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <div class="flex min-w-0 items-start gap-2">
                    <div class="flex shrink-0 flex-wrap items-center gap-2 pt-0.5">
                        <span
                            class="app-reading-chip"
                            :class="props.active
                                ? 'app-reading-chip--active'
                                : 'app-reading-chip--idle'"
                        >
                            {{ props.label }}
                        </span>
                        <span
                            v-for="situationNumber in props.assignedSituations"
                            :key="situationNumber"
                            class="app-reading-assigned-badge"
                        >
                            {{ situationNumber }}
                        </span>
                    </div>
                </div>
            </div>
            <span
                v-if="props.showInactiveChevron && ! props.active"
                class="grid h-6 w-6 place-items-center text-[1rem] font-black text-[color:color-mix(in_srgb,var(--shell-muted)_88%,white)]"
                aria-hidden="true"
            >
                ⌄
            </span>
            <span v-else-if="props.showMarker" class="app-reading-marker" :class="props.active ? 'app-reading-marker--active' : ''">▤</span>
        </div>
        <p
            v-if="! (props.compact && props.hideCompactBody)"
            class="app-reading-text-card__body mt-3"
            :class="[props.compact ? 'app-ui-copy line-clamp-1 mt-2' : 'app-reading-copy app-reading-text-card__body--expanded line-clamp-none']"
        >
            {{ props.body }}
        </p>
    </article>
</template>
