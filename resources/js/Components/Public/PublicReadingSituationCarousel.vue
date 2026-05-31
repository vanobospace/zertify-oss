<script setup lang="ts">
import { usePublicLocale } from '@/composables/usePublicLocale';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Swiper, SwiperSlide } from 'swiper/vue';
import type { Swiper as SwiperInstance } from 'swiper';

import 'swiper/css';

type SituationCard = {
    id: string;
    number: number;
    label?: string;
    text: string;
};

const props = defineProps<{
    situations: SituationCard[];
    activeSituationId: string;
    activeAnswerLabel?: string;
    selectedAnswerLabels?: Record<string, string>;
    dock?: boolean;
    showHeader?: boolean;
    nudgeKey?: number;
}>();

const emit = defineEmits<{
    select: [situationId: string];
    interact: [];
}>();

const { t } = usePublicLocale();
const cardElements = ref<HTMLElement[]>([]);
const trackMinHeight = ref(0);
const trackElement = ref<HTMLElement | null>(null);
const dockSwiper = ref<SwiperInstance | null>(null);
const isPeeking = ref(false);
let resizeObserver: ResizeObserver | null = null;
let scrollRafId: number | null = null;
let scrollSnapTimeoutId: ReturnType<typeof window.setTimeout> | null = null;
let peekForwardTimeoutId: ReturnType<typeof window.setTimeout> | null = null;
let peekReturnTimeoutId: ReturnType<typeof window.setTimeout> | null = null;
const activeSituation = () => props.situations.find((item) => item.id === props.activeSituationId);
const activeSelectedAnswerLabel = () => props.selectedAnswerLabels?.[props.activeSituationId];
const activeSituationIndex = (): number => Math.max(props.situations.findIndex((item) => item.id === props.activeSituationId), 0);

const setCardElement = (element: Element | null, index: number): void => {
    if (element instanceof HTMLElement) {
        cardElements.value[index] = element;
    }
};

const measureTrackHeight = async (): Promise<void> => {
    await nextTick();

    const tallestCard = cardElements.value.reduce((maxHeight, element) => {
        if (! element) {
            return maxHeight;
        }

        return Math.max(maxHeight, element.offsetHeight);
    }, 0);

    trackMinHeight.value = tallestCard;
};

const observeCards = async (): Promise<void> => {
    await measureTrackHeight();

    if (typeof window === 'undefined' || typeof ResizeObserver === 'undefined') {
        return;
    }

    resizeObserver?.disconnect();
    resizeObserver = new ResizeObserver(() => {
        void measureTrackHeight();
    });

    for (const element of cardElements.value) {
        if (element) {
            resizeObserver.observe(element);
        }
    }
};

const syncActiveSituationToViewport = (): void => {
    if (! trackElement.value || cardElements.value.length === 0) {
        return;
    }

    const trackRect = trackElement.value.getBoundingClientRect();
    const trackCenter = trackRect.left + (trackRect.width / 2);

    let closestSituationId = props.activeSituationId;
    let closestDistance = Number.POSITIVE_INFINITY;

    cardElements.value.forEach((element, index) => {
        if (! element) {
            return;
        }

        const rect = element.getBoundingClientRect();
        const cardCenter = rect.left + (rect.width / 2);
        const distance = Math.abs(trackCenter - cardCenter);

        if (distance < closestDistance) {
            closestDistance = distance;
            closestSituationId = props.situations[index]?.id ?? closestSituationId;
        }
    });

    if (closestSituationId && closestSituationId !== props.activeSituationId) {
        emit('select', closestSituationId);
    }
};

const snapNearestSituationToCenter = (): void => {
    if (isPeeking.value || ! trackElement.value || cardElements.value.length === 0) {
        return;
    }

    const track = trackElement.value;
    const trackRect = track.getBoundingClientRect();
    const viewportCenter = trackRect.left + (trackRect.width / 2);

    let closestElement: HTMLElement | null = null;
    let closestDistance = Number.POSITIVE_INFINITY;

    cardElements.value.forEach((element) => {
        if (! element) {
            return;
        }

        const rect = element.getBoundingClientRect();
        const cardCenter = rect.left + (rect.width / 2);
        const distance = Math.abs(viewportCenter - cardCenter);

        if (distance < closestDistance) {
            closestDistance = distance;
            closestElement = element;
        }
    });

    if (! closestElement) {
        return;
    }

    const targetLeft = closestElement.offsetLeft - ((track.clientWidth - closestElement.offsetWidth) / 2);
    const maxScrollLeft = Math.max(track.scrollWidth - track.clientWidth, 0);
    const nextScrollLeft = Math.min(Math.max(targetLeft, 0), maxScrollLeft);

    if (Math.abs(track.scrollLeft - nextScrollLeft) < 2) {
        return;
    }

    track.scrollTo({
        left: nextScrollLeft,
        behavior: 'smooth',
    });
};

const clearTrackPeekTimers = (): void => {
    if (typeof window === 'undefined') {
        return;
    }

    if (peekForwardTimeoutId !== null) {
        window.clearTimeout(peekForwardTimeoutId);
        peekForwardTimeoutId = null;
    }

    if (peekReturnTimeoutId !== null) {
        window.clearTimeout(peekReturnTimeoutId);
        peekReturnTimeoutId = null;
    }
};

const cancelTrackPeek = (): void => {
    clearTrackPeekTimers();
    isPeeking.value = false;
};

const setDockSwiper = (instance: SwiperInstance): void => {
    dockSwiper.value = instance;
    syncDockSwiperToActiveSituation();
};

const syncDockSwiperToActiveSituation = (): void => {
    if (! props.dock || ! dockSwiper.value) {
        return;
    }

    const targetIndex = activeSituationIndex();

    if (dockSwiper.value.activeIndex !== targetIndex) {
        dockSwiper.value.slideTo(targetIndex, 240, false);
    }
};

const handleDockSlideChange = (instance: SwiperInstance): void => {
    if (isPeeking.value) {
        return;
    }

    emit('interact');

    const situation = props.situations[instance.activeIndex];

    if (situation && situation.id !== props.activeSituationId) {
        emit('select', situation.id);
    }
};

const runTrackNudge = async (): Promise<void> => {
    if (typeof window === 'undefined') {
        return;
    }

    if (! props.dock || ! dockSwiper.value) {
        return;
    }

    await nextTick();

    clearTrackPeekTimers();
    syncDockSwiperToActiveSituation();

    const swiper = dockSwiper.value;
    const currentTranslate = typeof swiper.getTranslate === 'function' ? swiper.getTranslate() : 0;
    const peekDistance = Math.min(swiper.width * 0.3, 72);

    if (peekDistance < 20) {
        return;
    }

    isPeeking.value = true;
    swiper.translateTo(currentTranslate - peekDistance, 260, false, false);

    peekForwardTimeoutId = window.setTimeout(() => {
        peekReturnTimeoutId = window.setTimeout(() => {
            swiper.translateTo(currentTranslate, 240, false, false);

            window.setTimeout(() => {
                syncDockSwiperToActiveSituation();
                isPeeking.value = false;
            }, 250);

            peekReturnTimeoutId = null;
        }, 220);

        peekForwardTimeoutId = null;
    }, 300);
};

const handleTrackScroll = (): void => {
    if (isPeeking.value) {
        return;
    }

    emit('interact');

    if (typeof window === 'undefined') {
        syncActiveSituationToViewport();

        return;
    }

    if (scrollRafId !== null) {
        window.cancelAnimationFrame(scrollRafId);
    }

    scrollRafId = window.requestAnimationFrame(() => {
        syncActiveSituationToViewport();
        scrollRafId = null;
    });

    if (scrollSnapTimeoutId !== null) {
        window.clearTimeout(scrollSnapTimeoutId);
    }

    scrollSnapTimeoutId = window.setTimeout(() => {
        snapNearestSituationToCenter();
        scrollSnapTimeoutId = null;
    }, 120);
};

onMounted(() => {
    void observeCards();
});

watch(() => props.situations, () => {
    cardElements.value = [];
    void observeCards();
}, { deep: true });

watch(() => props.selectedAnswerLabels, () => {
    void measureTrackHeight();
}, { deep: true });

watch(() => props.activeSituationId, () => {
    void measureTrackHeight();
    syncDockSwiperToActiveSituation();
});

watch(() => props.nudgeKey, (nextKey, previousKey) => {
    if (typeof nextKey === 'number' && nextKey !== previousKey) {
        void runTrackNudge();
    }
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();

    if (typeof window !== 'undefined' && scrollRafId !== null) {
        window.cancelAnimationFrame(scrollRafId);
    }

    if (typeof window !== 'undefined' && scrollSnapTimeoutId !== null) {
        window.clearTimeout(scrollSnapTimeoutId);
    }

    clearTrackPeekTimers();
});
</script>

<template>
    <section class="space-y-3" :class="props.dock ? 'space-y-2' : ''">
        <div v-if="props.showHeader !== false" class="flex items-center justify-between">
            <h3 class="flex items-center gap-2 text-base font-extrabold text-[var(--shell-text)]">
                <span class="h-5 w-1.5 rounded-full bg-[var(--shell-secondary)]"></span>
                {{ t('lesen.situations.select_title') }}
            </h3>
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-black uppercase tracking-[0.18em] text-[var(--shell-muted)]">
                    {{ t('lesen.situations.current_label') }}
                </span>
                <div
                    v-if="activeSituation()?.label"
                    class="grid h-7 w-7 place-items-center rounded-full bg-[var(--shell-secondary)] text-[11px] font-black text-white"
                >
                    {{ activeSituation()?.label }}
                </div>
                <div
                    v-if="activeSelectedAnswerLabel()"
                    class="grid h-7 w-7 place-items-center rounded-full bg-[var(--shell-accent)] text-[11px] font-black text-white"
                >
                    {{ activeSelectedAnswerLabel() }}
                </div>
            </div>
        </div>

        <div
            ref="trackElement"
            v-if="!props.dock"
            class="scrollbar-hide flex snap-x gap-3 overflow-x-auto pb-2"
            :class="props.dock ? 'pb-1 pt-1' : ''"
            :style="trackMinHeight ? { minHeight: `${trackMinHeight}px` } : undefined"
            @scroll.passive="handleTrackScroll"
            @touchstart.passive="cancelTrackPeek(); emit('interact')"
            @pointerdown="cancelTrackPeek(); emit('interact')"
        >
            <button
                v-for="situation in props.situations"
                :key="situation.id"
                :ref="(element) => setCardElement(element, props.situations.findIndex((item) => item.id === situation.id))"
                type="button"
                class="app-interactive relative shrink-0 snap-start border text-left transition-all"
                :class="[
                    props.dock ? 'w-[18.5rem] rounded-[1.9rem] px-4 py-4' : 'w-[18rem] rounded-[2rem] p-4',
                    situation.id === props.activeSituationId
                        ? 'border-[var(--shell-secondary)] bg-[var(--shell-surface)] shadow-[0_16px_32px_color-mix(in_srgb,var(--shell-secondary)_14%,transparent)]'
                        : 'border-[color:color-mix(in_srgb,var(--shell-border)_88%,transparent)] bg-[var(--shell-surface)] opacity-80',
                ]"
                @click="emit('select', situation.id)"
            >
                <p
                    class="mt-1.5 text-[0.98rem] leading-[1.65]"
                    :class="[
                        props.dock ? 'mx-auto max-w-[14.75rem] text-left' : '',
                        situation.id === props.activeSituationId ? 'font-bold italic text-[var(--shell-text)]' : 'font-medium text-[var(--shell-text)]/80',
                    ]"
                >
                    {{ situation.text }}
                </p>
            </button>
        </div>

        <Swiper
            v-else
            :slides-per-view="1.18"
            :space-between="4"
            :slides-offset-before="3"
            :slides-offset-after="8"
            class="app-reading-dock-swiper -ml-[2px] overflow-visible pb-0.5 pr-[3px] pt-0.5"
            :style="trackMinHeight ? { minHeight: `${trackMinHeight + 4}px` } : undefined"
            @swiper="setDockSwiper"
            @slide-change-transition-end="handleDockSlideChange"
            @touch-start="cancelTrackPeek(); emit('interact')"
            @slider-move="cancelTrackPeek(); emit('interact')"
        >
            <SwiperSlide
                v-for="(situation, index) in props.situations"
                :key="situation.id"
                class="flex !h-auto items-center justify-start overflow-visible py-0.5"
            >
                <button
                    type="button"
                    class="app-interactive relative mr-auto ml-0 block w-full max-w-[16.75rem] border rounded-[1.9rem] px-4 py-2.5 text-left transition-all"
                    :ref="(element) => setCardElement(element, index)"
                    :class="situation.id === props.activeSituationId
                        ? 'border-[var(--shell-secondary)] bg-[var(--shell-surface)] shadow-none'
                        : 'border-[color:color-mix(in_srgb,var(--shell-border)_88%,transparent)] bg-[var(--shell-surface)] opacity-80'"
                    @click="emit('select', situation.id)"
                >
                    <p
                        class="mx-auto mt-0.5 max-w-[14.75rem] text-left text-[0.98rem] leading-[1.6]"
                        :class="situation.id === props.activeSituationId ? 'font-bold italic text-[var(--shell-text)]' : 'font-medium text-[var(--shell-text)]/80'"
                    >
                        {{ situation.text }}
                    </p>
                </button>
            </SwiperSlide>
        </Swiper>
    </section>
</template>

<style scoped>
.app-reading-dock-swiper :deep(.swiper-wrapper) {
    align-items: center;
}
</style>
