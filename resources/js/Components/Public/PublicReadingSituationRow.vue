<script setup lang="ts">
import { usePublicLocale } from '@/composables/usePublicLocale';

const props = defineProps<{
    number: number;
    label?: string;
    text: string;
    active?: boolean;
    answered?: boolean;
    selectedLabel?: string;
}>();

const emit = defineEmits<{
    select: [];
}>();

const { t } = usePublicLocale();
</script>

<template>
    <div
        class="group app-reading-situation-row items-start xl:items-center"
        :class="props.active
            ? 'app-reading-situation-row--active'
            : 'app-reading-situation-row--idle'"
        @click="emit('select')"
    >
        <div
            class="app-reading-situation-badge"
            :class="props.answered
                ? 'app-reading-situation-badge--answered'
                : props.active
                    ? 'app-reading-situation-badge--active'
                    : 'app-reading-situation-badge--neutral'"
        >
            {{ props.label ?? props.number }}
        </div>
        <p
            class="app-reading-situation-text min-w-0 flex-1"
            :class="props.active ? 'app-reading-situation-text--active' : 'app-reading-situation-text--idle'"
        >
            {{ props.text }}
        </p>
        <slot name="control">
            <span
                class="app-reading-choice-button app-reading-choice-button--desktop"
                :class="props.selectedLabel
                    ? 'app-reading-choice-button--assigned'
                    : 'app-reading-choice-button--neutral'"
            >
                {{ props.selectedLabel ?? t('lesen.actions.choose_text') }}
            </span>
        </slot>
    </div>
</template>
