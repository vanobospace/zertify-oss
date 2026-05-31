<script setup lang="ts">
import PublicModuleHeader from '@/Components/Public/PublicModuleHeader.vue';
import PublicReadingSituationCarousel from '@/Components/Public/PublicReadingSituationCarousel.vue';
import PublicReadingSituationRow from '@/Components/Public/PublicReadingSituationRow.vue';
import PublicReadingTextCard from '@/Components/Public/PublicReadingTextCard.vue';
import PublicAppLayout from '@/Layouts/PublicAppLayout.vue';
import { usePublicLocale } from '@/composables/usePublicLocale';
import { Head } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

defineOptions({
    layout: PublicAppLayout,
});

type ModulePart = {
    key: 'teil1' | 'teil2' | 'teil3';
    label: string;
    available: boolean;
};

type ReadingText = {
    id: string;
    label: string;
    title?: string;
    body: string;
};

type ReadingSituation = {
    id: string;
    number: number;
    label?: string;
    text: string;
};

type ReadingTask = {
    instructions: string;
    prompt: string;
    situations: ReadingSituation[];
    texts: ReadingText[];
    extra_answer: {
        id: string;
        label: string;
        text: string;
    };
    correct: Record<string, string>;
    explanation: Record<string, Record<string, string>>;
};

const props = defineProps<{
    module: {
        title: string;
        subtitle: string;
        contextLabel?: string;
        timer: string;
        startedLockLabel: string;
    };
    parts: ModulePart[];
    task: ReadingTask;
}>();

const { t, tr } = usePublicLocale();

const activePart = ref<ModulePart['key']>('teil1');
const activeSituationIndex = ref(0);
const activeTextId = ref(props.task.texts[0]?.id ?? '');
const desktopTextItemRefs = ref<Record<string, HTMLElement | null>>({});
const mobileTextItemRefs = ref<Record<string, HTMLElement | null>>({});
const selectedAnswersBySituation = ref<Record<string, string>>({});
const showMobileSituationDock = ref(true);
const mobileSituationDockCollapsed = ref(true);
const mobileSituationDockNudgeKey = ref(0);
const mobileSituationDockPanelRef = ref<HTMLElement | null>(null);
const mobileSituationDockHeight = ref(0);
const transientSituationStates = ref<Record<string, 'saved' | 'canceled'>>({});
const transientSituationTimeouts = new Map<string, ReturnType<typeof window.setTimeout>>();
let mobileSituationDockResizeObserver: ResizeObserver | null = null;

const answerOptions = computed(() => props.task.texts);
const visibleTextOptions = computed(() => props.task.texts);

const activeSituation = computed(() => props.task.situations[activeSituationIndex.value] ?? props.task.situations[0]);
const activeSituationId = computed(() => activeSituation.value?.id ?? '');
const answeredCount = computed(() => Object.keys(selectedAnswersBySituation.value).length);
const isStarted = computed(() => answeredCount.value > 0);
const requiredAnswerCount = computed(() => Math.min(props.task.texts.length, props.task.situations.length));
const isTeilOneComplete = computed(() => answeredCount.value === requiredAnswerCount.value);
const activeAnswerId = computed(() => selectedAnswersBySituation.value[activeSituationId.value] ?? '');
const activeAnswerLabel = computed(() => answerOptions.value.find((option) => option.id === activeAnswerId.value)?.label ?? '');
const activeTextLabel = computed(() => answerOptions.value.find((option) => option.id === activeTextId.value)?.label ?? '');
const activeSituationLabel = computed(() => activeSituation.value?.label ?? '');
const activeSituationHasSavedAnswer = computed(() => Boolean(activeAnswerId.value));
const activeSelectionMatchesCurrentText = computed(() => activeSituationHasSavedAnswer.value && activeAnswerId.value === activeTextId.value);
const activeSituationTransientState = computed(() => transientSituationStates.value[activeSituationId.value] ?? null);
const activeSituationShowingSuccess = computed(() => activeSituationTransientState.value === 'saved');
const activeSituationShowingCanceled = computed(() => activeSituationTransientState.value === 'canceled');
const textLabel = (label: string): string => tr('lesen.answer.text_label', { label });
const adLabel = (label: string): string => tr('lesen.answer.ad_label', { label });
const localizedModule = computed(() => ({
    contextLabel: props.module.contextLabel ?? 'B2 · Allgemein · Lesen',
    title: `${t('lesen.module.preparation_prefix')}: Lesen`,
    subtitle: t('lesen.module.subtitle'),
    timer: tr('lesen.module.timer_remaining', { time: '12:45' }),
    startedLockLabel: t('lesen.module.locked_label'),
}));
const localizedParts = computed(() => props.parts);
const desktopAssignButtonLabel = computed(() => {
    if (! activeTextLabel.value) {
        return t('lesen.actions.choose');
    }

    if (! activeSituationHasSavedAnswer.value) {
        return tr('lesen.actions.assign_text', { label: activeTextLabel.value });
    }

    if (activeSelectionMatchesCurrentText.value) {
        return textLabel(activeAnswerLabel.value);
    }

    return tr('lesen.actions.replace_text', { label: activeTextLabel.value });
});
const mobileAssignButtonLabel = computed(() => {
    if (! activeTextLabel.value) {
        return t('lesen.actions.selection_missing');
    }

    if (! activeSituationHasSavedAnswer.value) {
        return activeSituationLabel.value
            ? tr('lesen.actions.assign_situation_to_text', { situation: activeSituationLabel.value, text: activeTextLabel.value })
            : tr('lesen.actions.assign_text', { label: activeTextLabel.value });
    }

    if (activeSelectionMatchesCurrentText.value) {
        return textLabel(activeAnswerLabel.value);
    }

    return activeSituationLabel.value
        ? tr('lesen.actions.replace_situation_to_text', { situation: activeSituationLabel.value, text: activeTextLabel.value })
        : tr('lesen.actions.replace_text', { label: activeTextLabel.value });
});
const mobileActionVerbLabel = computed(() => {
    if (! activeTextLabel.value) {
        return t('lesen.actions.selection_missing');
    }

    if (activeSituationShowingSuccess.value) {
        return t('lesen.actions.saved');
    }

    if (activeSituationShowingCanceled.value) {
        return t('lesen.actions.canceled');
    }

    if (activeSelectionMatchesCurrentText.value) {
        return t('lesen.actions.cancel');
    }

    return mobileAssignButtonLabel.value;
});
const selectedAnswerLabelsBySituation = computed(() => Object.fromEntries(
    Object.entries(selectedAnswersBySituation.value).map(([situationId, selectedTextId]) => {
        const label = answerOptions.value.find((option) => option.id === selectedTextId)?.label ?? '';

        return [situationId, label];
    }),
));
const mobileDockHeaderLabel = computed(() => mobileSituationDockCollapsed.value
    ? t('lesen.situations.select_title')
    : `${t('lesen.situations.short_label')} ▾`);
const assignedSituationNumbersByText = computed(() => {
    const entries = Object.entries(selectedAnswersBySituation.value);

    return answerOptions.value.reduce<Record<string, string[]>>((carry, option) => {
        carry[option.id] = entries
            .filter(([, selectedTextId]) => selectedTextId === option.id)
            .map(([situationId]) => props.task.situations.find((situation) => situation.id === situationId)?.label)
            .filter((value): value is string => typeof value === 'string')
            .sort((left, right) => left.localeCompare(right));

        return carry;
    }, {});
});
const mobileProgressSegments = computed(() => {
    const totalSegments = 5;
    const filledSegments = Math.min(answeredCount.value, totalSegments);

    return Array.from({ length: totalSegments }, (_, index) => index < filledSegments);
});
const desktopProgressSegments = computed(() => {
    const totalSegments = Math.max(visibleTextOptions.value.length, 1);
    const filledSegments = Math.min(answeredCount.value, totalSegments);

    return Array.from({ length: totalSegments }, (_, index) => index < filledSegments);
});
const decoratedParts = computed(() => props.parts.map((part) => ({
    ...part,
    disabled: isStarted.value && part.key !== activePart.value,
})));

const setActivePart = (partKey: ModulePart['key']): void => {
    if (isStarted.value && partKey !== activePart.value) {
        return;
    }

    activePart.value = partKey;
};

const setActiveSituation = (situationId: string): void => {
    const nextIndex = props.task.situations.findIndex((situation) => situation.id === situationId);

    if (nextIndex >= 0) {
        activeSituationIndex.value = nextIndex;
        mobileSituationDockCollapsed.value = false;
    }
};

const setActiveText = (answerId: string): void => {
    activeTextId.value = answerId;
};

const toggleMobileSituationDockCollapsed = (): void => {
    mobileSituationDockCollapsed.value = ! mobileSituationDockCollapsed.value;
};

const triggerMobileSituationDockPeek = (): void => {
    mobileSituationDockNudgeKey.value += 1;
};

const setDesktopTextItemRef = (textId: string, element: Element | null): void => {
    desktopTextItemRefs.value[textId] = element instanceof HTMLElement ? element : null;
};

const setMobileTextItemRef = (textId: string, element: Element | null): void => {
    mobileTextItemRefs.value[textId] = element instanceof HTMLElement ? element : null;
};

const waitForLayoutSettled = async (): Promise<void> => {
    await nextTick();

    if (typeof window === 'undefined') {
        return;
    }

    await new Promise<void>((resolve) => {
        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => {
                resolve();
            });
        });
    });
};

const updateMobileSituationDockHeight = (): void => {
    if (typeof window === 'undefined') {
        return;
    }

    const panelHeight = mobileSituationDockPanelRef.value?.offsetHeight ?? 0;
    const visualGap = mobileSituationDockCollapsed.value ? 2 : 4;

    mobileSituationDockHeight.value = panelHeight + visualGap;
};

const observeMobileSituationDock = async (): Promise<void> => {
    if (typeof window === 'undefined' || typeof ResizeObserver === 'undefined') {
        return;
    }

    await nextTick();

    mobileSituationDockResizeObserver?.disconnect();
    mobileSituationDockResizeObserver = null;

    const dockElement = mobileSituationDockPanelRef.value;

    if (! dockElement) {
        mobileSituationDockHeight.value = 0;

        return;
    }

    updateMobileSituationDockHeight();

    mobileSituationDockResizeObserver = new ResizeObserver(() => {
        updateMobileSituationDockHeight();
    });

    mobileSituationDockResizeObserver.observe(dockElement);
};

const scrollAccordionIntoView = async (textId: string): Promise<void> => {
    if (typeof window === 'undefined') {
        return;
    }

    await waitForLayoutSettled();

    const isDesktop = window.innerWidth >= 1024;
    const target = isDesktop ? desktopTextItemRefs.value[textId] : mobileTextItemRefs.value[textId];

    if (! target) {
        return;
    }

    const stickyOffset = isDesktop ? 96 : 72;
    const rect = target.getBoundingClientRect();
    const desiredTop = stickyOffset;

    if (rect.top >= desiredTop) {
        return;
    }

    const nextScrollTop = window.scrollY + rect.top - desiredTop;

    window.scrollTo({
        top: Math.max(nextScrollTop, 0),
        behavior: 'smooth',
    });
};

const showTransientStateForSituation = (situationId: string, state: 'saved' | 'canceled'): void => {
    if (! situationId || typeof window === 'undefined') {
        return;
    }

    transientSituationStates.value = {
        ...transientSituationStates.value,
        [situationId]: state,
    };

    const existingTimeout = transientSituationTimeouts.get(situationId);

    if (existingTimeout) {
        window.clearTimeout(existingTimeout);
    }

    const timeout = window.setTimeout(() => {
        const nextStates = { ...transientSituationStates.value };

        delete nextStates[situationId];
        transientSituationStates.value = nextStates;
        transientSituationTimeouts.delete(situationId);
    }, 2200);

    transientSituationTimeouts.set(situationId, timeout);
};

const applyActiveTextToSituation = (situationId: string): void => {
    if (! activeSituationId.value) {
        return;
    }

    const nextSituationId = situationId || activeSituationId.value;
    const currentAnswerId = selectedAnswersBySituation.value[nextSituationId];

    if (currentAnswerId === activeTextId.value) {
        const nextSelections = { ...selectedAnswersBySituation.value };

        delete nextSelections[nextSituationId];
        selectedAnswersBySituation.value = nextSelections;
        showTransientStateForSituation(nextSituationId, 'canceled');
        setActiveSituation(nextSituationId);

        return;
    }

    const nextSelections = Object.entries(selectedAnswersBySituation.value).reduce<Record<string, string>>((carry, [selectedSituationId, selectedTextId]) => {
        if (selectedTextId !== activeTextId.value || selectedSituationId === nextSituationId) {
            carry[selectedSituationId] = selectedTextId;
        }

        return carry;
    }, {});

    selectedAnswersBySituation.value = {
        ...nextSelections,
        [nextSituationId]: activeTextId.value,
    };

    showTransientStateForSituation(nextSituationId, 'saved');
    setActiveSituation(nextSituationId);
};

const partPlaceholderCopy = computed(() => {
    if (activePart.value === 'teil2') {
        return {
            title: t('lesen.placeholder.teil2_title'),
            body: t('lesen.placeholder.teil2_body'),
        };
    }

    return {
        title: t('lesen.placeholder.teil3_title'),
        body: t('lesen.placeholder.teil3_body'),
    };
});

onBeforeUnmount(() => {
    if (typeof window === 'undefined') {
        return;
    }

    mobileSituationDockResizeObserver?.disconnect();

    for (const timeout of transientSituationTimeouts.values()) {
        window.clearTimeout(timeout);
    }

    transientSituationTimeouts.clear();
});

watch(activeTextId, (textId, previousTextId) => {
    if (! textId || textId === previousTextId) {
        return;
    }

    void scrollAccordionIntoView(textId);
    mobileSituationDockCollapsed.value = false;
});

watch(
    () => [activePart.value, showMobileSituationDock.value, mobileSituationDockCollapsed.value],
    () => {
        void observeMobileSituationDock();
    },
    { immediate: true },
);

</script>

<template>
    <Head :title="t('lesen.meta_title')" />

    <div class="space-y-6 md:space-y-8">
        <PublicModuleHeader
            :title="localizedModule.title"
            :subtitle="localizedModule.subtitle"
            :context-label="localizedModule.contextLabel"
            :timer-label="localizedModule.timer"
            :parts="localizedParts.map((part) => ({ ...part, disabled: decoratedParts.find((decoratedPart) => decoratedPart.key === part.key)?.disabled }))"
            :active-part="activePart"
            :locked="isStarted"
            :lock-label="localizedModule.startedLockLabel"
            :mobile-compact="true"
            @select="setActivePart"
        />

        <template v-if="activePart === 'teil1'">
            <section class="hidden items-start gap-4 xl:grid xl:grid-cols-2 xl:gap-6">
                <aside class="sticky top-24 flex w-full min-h-0 self-start flex-col">
                    <div class="flex w-full items-center justify-between">
                        <h2 class="text-xs font-black uppercase tracking-[0.22em] text-[var(--shell-muted)]">{{ t('lesen.texts.available_title') }}</h2>
                        <p class="max-w-[22rem] text-right text-xs leading-6 text-[var(--shell-muted)]">{{ t('lesen.task.instructions') }}</p>
                    </div>

                    <div class="mt-4 w-full space-y-4">
                        <button
                            v-for="text in visibleTextOptions"
                            :key="text.id"
                            type="button"
                            class="block w-full text-left"
                            :ref="(element) => setDesktopTextItemRef(text.id, element)"
                            @click="setActiveText(text.id)"
                        >
                            <PublicReadingTextCard
                                :label="text.label"
                                :title="text.title"
                                :body="text.body"
                                :active="activeTextId === text.id"
                                :compact="activeTextId !== text.id"
                                :extra="text.id === props.task.extra_answer.id"
                                :assigned-situations="assignedSituationNumbersByText[text.id] ?? []"
                                :hide-compact-body="true"
                            />
                        </button>
                    </div>
                </aside>

                <article class="shell-card rounded-[2.25rem] p-8 xl:p-10">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-[2rem] font-extrabold text-[var(--shell-text)]">{{ t('lesen.situations.title') }}</h2>
                            <p class="mt-2 max-w-[36rem] text-sm leading-7 text-[var(--shell-muted)]">{{ t('lesen.task.assign_once') }}</p>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <span
                                v-for="(filled, index) in desktopProgressSegments"
                                :key="index"
                                class="h-2.5 w-7 rounded-full"
                                :class="filled ? 'bg-[var(--shell-accent)]' : 'bg-[var(--shell-surface-alt)]'"
                            ></span>
                        </div>
                    </div>

                    <div class="mt-7 space-y-2">
                        <PublicReadingSituationRow
                        v-for="situation in props.task.situations"
                        :key="situation.id"
                        :number="situation.number"
                        :label="situation.label"
                        :text="situation.text"
                            :active="activeSituationId === situation.id"
                            :answered="Boolean(selectedAnswersBySituation[situation.id])"
                            :selected-label="selectedAnswersBySituation[situation.id] ? textLabel(answerOptions.find((option) => option.id === selectedAnswersBySituation[situation.id])?.label ?? '') : undefined"
                            @select="setActiveSituation(situation.id)"
                        >
                            <template #control>
                                <button
                                    type="button"
                                    class="app-reading-choice-button app-reading-choice-button--desktop"
                                    :class="transientSituationStates[situation.id] === 'saved'
                                        ? 'app-reading-choice-button--success'
                                        : transientSituationStates[situation.id] === 'canceled'
                                            ? 'app-reading-choice-button--canceled'
                                        : activeSituationId === situation.id
                                            ? 'app-reading-choice-button--fixed'
                                        : selectedAnswersBySituation[situation.id]
                                            ? 'app-reading-choice-button--assigned'
                                            : 'app-reading-choice-button--neutral'"
                                    @click.stop="applyActiveTextToSituation(situation.id)"
                                >
                                    <template v-if="transientSituationStates[situation.id] === 'saved'">
                                        <span class="flex items-center gap-2">
                                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/20 text-[0.85rem] font-black animate-pulse">✓</span>
                                            <span>{{ t('lesen.actions.saved') }}</span>
                                        </span>
                                    </template>
                                    <template v-else-if="transientSituationStates[situation.id] === 'canceled'">
                                        <span class="flex items-center gap-2">
                                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/60 text-[0.9rem] font-black animate-pulse">↺</span>
                                            <span>{{ t('lesen.actions.canceled') }}</span>
                                        </span>
                                    </template>
                                    <template v-else-if="activeSituationId === situation.id && activeSelectionMatchesCurrentText">
                                        <span>{{ desktopAssignButtonLabel }}</span>
                                        <span aria-hidden="true" class="text-[1.15rem] leading-none opacity-75">↺</span>
                                    </template>
                                    <template v-else>
                                        {{
                                            activeSituationId === situation.id
                                                ? desktopAssignButtonLabel
                                                : selectedAnswersBySituation[situation.id]
                                                    ? textLabel(answerOptions.find((option) => option.id === selectedAnswersBySituation[situation.id])?.label ?? '')
                                                    : t('lesen.actions.choose')
                                        }}
                                    </template>
                                </button>
                            </template>
                        </PublicReadingSituationRow>
                    </div>

                    <div class="mt-10 flex items-center justify-end">
                        <button
                            type="button"
                            class="app-btn-primary app-btn-primary--reading-desktop disabled:cursor-not-allowed disabled:opacity-55"
                            :disabled="! isTeilOneComplete"
                        >
                            {{ t('lesen.actions.review_answers') }}
                        </button>
                    </div>
                </article>
            </section>

            <section class="mx-auto max-w-[44rem] space-y-4 xl:hidden">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[var(--shell-accent)]">{{ t('lesen.mobile.current_task') }}</p>
                        <h2 class="mt-2 text-[2.1rem] font-extrabold leading-[0.98] text-[var(--shell-text)]">{{ t('lesen.task.prompt') }}</h2>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black uppercase tracking-[0.18em] text-[var(--shell-muted)]">{{ t('lesen.mobile.progress') }}</span>
                        <div class="mt-2 flex gap-1.5">
                            <span
                                v-for="(filled, index) in mobileProgressSegments"
                                :key="index"
                                class="h-1.5 w-6 rounded-full"
                                :class="filled ? 'bg-[var(--shell-accent)]' : 'bg-[var(--shell-surface-alt)]'"
                            ></span>
                        </div>
                    </div>
                </div>

                <section class="space-y-3">
                    <h3 class="flex items-center gap-2 text-base font-extrabold text-[var(--shell-text)]">
                        <span class="h-5 w-1.5 rounded-full bg-[var(--shell-accent)]"></span>
                        {{ t('lesen.mobile.find_matching_ad') }}
                    </h3>

                    <div class="-mx-[8px] px-[8px] pb-1 sm:mx-0 sm:px-0">
                        <button
                            v-for="(text, index) in visibleTextOptions"
                            :key="text.id"
                            type="button"
                            class="relative block w-full text-left transition-all duration-200"
                            :ref="(element) => setMobileTextItemRef(text.id, element)"
                            :class="[
                                index === 0
                                    ? ''
                                    : visibleTextOptions[index - 1]?.id === activeTextId
                                        ? 'mt-2'
                                        : '-mt-[2.45rem]',
                                text.id === activeTextId ? 'opacity-100' : 'opacity-94',
                            ]"
                            :style="{ zIndex: `${index + 1}` }"
                            @click="setActiveText(text.id)"
                        >
                            <PublicReadingTextCard
                                :label="text.label"
                                :title="text.title"
                                :body="text.body"
                                :active="text.id === activeTextId"
                                :compact="text.id !== activeTextId"
                                :extra="text.id === props.task.extra_answer.id"
                                :assigned-situations="assignedSituationNumbersByText[text.id] ?? []"
                                :show-inactive-chevron="true"
                            />
                        </button>
                    </div>

                    <article
                        v-if="props.task.explanation[activeSituationId]?.strategy_hint"
                        class="hidden gap-3 rounded-[1.8rem] border border-[color:color-mix(in_srgb,var(--shell-secondary)_14%,transparent)] bg-[color:color-mix(in_srgb,var(--shell-secondary-soft)_20%,white)] p-4 md:flex xl:hidden"
                    >
                        <div class="grid h-10 w-10 shrink-0 place-items-center rounded-[1rem] bg-[var(--shell-secondary)] text-white">◉</div>
                        <p class="text-sm italic leading-7 text-[var(--shell-secondary-text)]">
                            {{ t('lesen.mobile.tip_prefix') }} {{ props.task.explanation[activeSituationId]?.strategy_hint }}
                        </p>
                    </article>
                </section>

                <div
                    class="transition-[height] duration-200"
                    :style="{ height: `${mobileSituationDockHeight}px` }"
                ></div>
            </section>
        </template>

        <template v-else>
            <section class="shell-card rounded-[2.2rem] p-8 md:p-10">
                <div class="max-w-3xl">
                    <p class="app-kicker">{{ t('lesen.placeholder.kicker') }}</p>
                    <h2 class="mt-3 text-[2.2rem] font-extrabold leading-tight text-[var(--shell-text)] md:text-[2.8rem]">
                        {{ partPlaceholderCopy.title }}
                    </h2>
                    <p class="mt-4 text-base leading-8 text-[var(--shell-muted)] md:text-lg">
                        {{ partPlaceholderCopy.body }}
                    </p>
                </div>
            </section>
        </template>

        <div
            v-if="activePart === 'teil1' && showMobileSituationDock"
            class="fixed inset-x-0 z-50 md:hidden"
            style="bottom: 0"
        >
            <div class="px-4 pb-[max(calc(env(safe-area-inset-bottom)-0.75rem),0px)]">
                <div
                    ref="mobileSituationDockPanelRef"
                    class="mx-auto max-w-[44rem] overflow-hidden rounded-t-[1.35rem] border border-b-0 border-[var(--shell-border)] bg-[var(--shell-surface)]/97 shadow-[0_-12px_28px_rgba(20,20,35,0.12)] backdrop-blur"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-3 px-4 py-3"
                        @click="toggleMobileSituationDockCollapsed"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="h-1.5 w-10 rounded-full bg-[var(--shell-border)]"></span>
                            <span class="truncate text-sm font-black text-[var(--shell-text)]">{{ mobileDockHeaderLabel }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="grid h-7 w-7 place-items-center rounded-full bg-[var(--shell-secondary)] text-[11px] font-black text-white">
                                {{ activeSituation?.label }}
                            </span>
                            <span
                                v-if="activeSituationHasSavedAnswer"
                                class="grid h-7 w-7 place-items-center rounded-full bg-[var(--shell-accent)] text-[11px] font-black text-white"
                            >
                                {{ activeAnswerLabel }}
                            </span>
                        </div>
                    </button>

                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="translate-y-4 opacity-0"
                        enter-to-class="translate-y-0 opacity-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="translate-y-0 opacity-100"
                        leave-to-class="translate-y-4 opacity-0"
                        @after-enter="triggerMobileSituationDockPeek"
                    >
                        <div
                            v-if="!mobileSituationDockCollapsed"
                            class="border-t border-[var(--shell-border)] px-4 pb-1 pt-3"
                        >
                            <PublicReadingSituationCarousel
                                :situations="props.task.situations"
                                :active-situation-id="activeSituationId"
                                :active-answer-label="activeAnswerLabel"
                                :selected-answer-labels="selectedAnswerLabelsBySituation"
                                :dock="true"
                                :show-header="false"
                                :nudge-key="mobileSituationDockNudgeKey"
                                @select="setActiveSituation"
                            />
                            <button
                                type="button"
                                class="mt-3 flex h-11 w-full items-center justify-center gap-2 rounded-[1.15rem] px-4 text-sm font-black transition-all"
                                :class="activeSituationShowingSuccess
                                    ? 'app-reading-choice-button--success'
                                    : activeSituationShowingCanceled
                                        ? 'app-reading-choice-button--canceled'
                                    : activeSelectionMatchesCurrentText
                                        ? 'app-reading-choice-button--cancel'
                                        : 'app-reading-choice-button--secondary-soft'"
                                @click="applyActiveTextToSituation(activeSituationId)"
                            >
                                <template v-if="activeSituationShowingSuccess">
                                    <span class="flex items-center gap-2">
                                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/20 text-[0.85rem] font-black animate-pulse">✓</span>
                                        <span>{{ mobileActionVerbLabel }}</span>
                                    </span>
                                </template>
                                <template v-else-if="activeSituationShowingCanceled">
                                    <span class="flex items-center gap-2">
                                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/60 text-[0.9rem] font-black animate-pulse">↺</span>
                                        <span>{{ mobileActionVerbLabel }}</span>
                                    </span>
                                </template>
                                <template v-else-if="activeSelectionMatchesCurrentText">
                                    <span>{{ mobileActionVerbLabel }}</span>
                                    <span aria-hidden="true" class="text-[1.15rem] leading-none opacity-80">↺</span>
                                </template>
                                <template v-else>
                                    <span>{{ mobileActionVerbLabel }}</span>
                                </template>
                            </button>
                            <button
                                v-if="isTeilOneComplete"
                                type="button"
                                class="app-accent-reverse mt-3 flex h-11 w-full items-center justify-center rounded-[1.15rem] px-5 text-sm font-black"
                            >
                                {{ t('lesen.actions.review_answers') }}
                            </button>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-6 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-6 opacity-0"
        >
            <div
                v-if="activePart === 'teil1' && showMobileSituationDock"
                class="fixed inset-x-0 z-50 hidden border-t border-[var(--shell-border)] bg-[var(--shell-surface)]/97 px-4 pb-[env(safe-area-inset-bottom)] pt-3 shadow-[0_-12px_28px_rgba(20,20,35,0.12)] backdrop-blur transition-[bottom,opacity] duration-200 md:block xl:hidden"
                style="bottom: 0"
            >
                <div class="mx-auto max-w-[44rem]">
                    <PublicReadingSituationCarousel
                        :situations="props.task.situations"
                        :active-situation-id="activeSituationId"
                        :active-answer-label="activeAnswerLabel"
                        :selected-answer-labels="selectedAnswerLabelsBySituation"
                        :dock="true"
                        @select="setActiveSituation"
                    />
                    <button
                        type="button"
                        class="mt-3 flex h-11 w-full items-center justify-center gap-2 rounded-[1.15rem] px-4 text-sm font-black transition-all"
                        :class="activeSituationShowingSuccess
                            ? 'app-reading-choice-button--success'
                            : activeSituationShowingCanceled
                                ? 'app-reading-choice-button--canceled'
                            : activeSelectionMatchesCurrentText
                                ? 'app-reading-choice-button--cancel'
                                : 'app-reading-choice-button--secondary-soft'"
                        @click="applyActiveTextToSituation(activeSituationId)"
                    >
                        <template v-if="activeSituationShowingSuccess">
                            <span class="flex items-center gap-2">
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/20 text-[0.85rem] font-black animate-pulse">✓</span>
                                <span>{{ mobileActionVerbLabel }}</span>
                            </span>
                        </template>
                        <template v-else-if="activeSituationShowingCanceled">
                            <span class="flex items-center gap-2">
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/60 text-[0.9rem] font-black animate-pulse">↺</span>
                                <span>{{ mobileActionVerbLabel }}</span>
                            </span>
                        </template>
                        <template v-else-if="activeSelectionMatchesCurrentText">
                            <span>{{ mobileActionVerbLabel }}</span>
                            <span aria-hidden="true" class="text-[1.15rem] leading-none opacity-80">↺</span>
                        </template>
                        <template v-else>
                            <span>{{ mobileActionVerbLabel }}</span>
                        </template>
                    </button>
                    <button
                        v-if="isTeilOneComplete"
                        type="button"
                        class="app-accent-reverse mt-3 flex h-11 w-full items-center justify-center rounded-[1.15rem] px-5 text-sm font-black"
                    >
                        {{ t('lesen.actions.review_answers') }}
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
