<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    DrawerContent,
    DrawerDescription,
    DrawerHandle,
    DrawerOverlay,
    DrawerPortal,
    DrawerRoot,
    DrawerTitle,
} from 'vaul-vue';
import { Swiper, SwiperSlide } from 'swiper/vue';
import type { AppBottomAction } from '@/Components/App/appBottomActionSheet';

import 'swiper/css';

const props = withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        description: string;
        badges?: string[];
        actions: AppBottomAction[];
        linkHintLabel?: string;
        actionHintLabel?: string;
    }>(),
    {
        badges: () => [],
        linkHintLabel: 'Open',
        actionHintLabel: 'Hint',
    },
);

const emit = defineEmits<{
    'update:open': [boolean];
    action: [AppBottomAction];
}>();

const toneClasses: Record<AppBottomAction['tone'], string> = {
    accent: 'bg-[color:color-mix(in_srgb,var(--shell-accent)_92%,black_8%)] text-white shadow-[0_18px_34px_color-mix(in_srgb,var(--shell-accent)_22%,transparent)]',
    secondary:
        'bg-[color:color-mix(in_srgb,var(--shell-secondary)_90%,black_10%)] text-white shadow-[0_18px_34px_color-mix(in_srgb,var(--shell-secondary)_20%,transparent)]',
    neutral:
        'bg-[var(--shell-surface-alt)] text-[var(--shell-text)] shadow-[0_16px_30px_rgba(37,47,61,0.08)]',
};

const setOpen = (value: boolean): void => {
    emit('update:open', value);
};

const handleAction = (action: AppBottomAction): void => {
    emit('action', action);
    emit('update:open', false);
};
</script>

<template>
    <DrawerRoot :open="props.open" @update:open="setOpen">
        <DrawerPortal>
            <DrawerOverlay
                class="fixed inset-0 z-40 bg-[color:color-mix(in_srgb,var(--shell-text)_34%,transparent)] backdrop-blur-[2px]"
            />
            <DrawerContent
                class="fixed inset-x-0 bottom-0 z-50 max-h-[88vh] rounded-t-[2rem] border border-b-0 border-[color:color-mix(in_srgb,var(--shell-border)_96%,white_4%)] bg-[var(--shell-surface)] px-5 pt-3 pb-6 shadow-[0_-24px_60px_rgba(20,28,41,0.22)] outline-none"
            >
                <div class="mx-auto w-full max-w-2xl">
                    <div class="flex justify-center">
                        <DrawerHandle
                            class="h-1.5 w-16 rounded-full bg-[color:color-mix(in_srgb,var(--shell-border)_100%,var(--shell-text)_10%)]"
                        />
                    </div>

                    <div class="mt-4 flex items-start justify-between gap-4">
                        <div>
                            <DrawerTitle
                                class="text-[1.65rem] leading-tight font-extrabold text-[var(--shell-text)]"
                            >
                                {{ props.title }}
                            </DrawerTitle>
                            <DrawerDescription
                                class="mt-2 max-w-xl text-sm leading-6 text-[var(--shell-muted)]"
                            >
                                {{ props.description }}
                            </DrawerDescription>
                        </div>
                        <button
                            type="button"
                            class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-[var(--shell-surface-alt)] text-[1.2rem] font-black text-[var(--shell-muted)] transition-transform active:scale-95"
                            @click="setOpen(false)"
                        >
                            ×
                        </button>
                    </div>

                    <div
                        v-if="props.badges.length"
                        class="mt-5 flex flex-wrap gap-2"
                    >
                        <span
                            v-for="badge in props.badges"
                            :key="badge"
                            class="rounded-full bg-[color:color-mix(in_srgb,var(--shell-surface-alt)_82%,white)] px-3 py-1.5 text-[11px] font-black tracking-[0.16em] text-[var(--shell-muted)] uppercase"
                        >
                            {{ badge }}
                        </span>
                    </div>

                    <div class="mt-6">
                        <Swiper
                            :slides-per-view="1.08"
                            :space-between="14"
                            :breakpoints="{
                                640: { slidesPerView: 1.3, spaceBetween: 16 },
                            }"
                        >
                            <SwiperSlide
                                v-for="action in props.actions"
                                :key="action.key"
                                class="pb-2"
                            >
                                <Link
                                    v-if="action.href"
                                    :href="action.href"
                                    prefetch
                                    class="block min-h-[12.5rem] rounded-[1.75rem] p-5 transition-transform active:scale-[0.985]"
                                    :class="toneClasses[action.tone]"
                                    @click="setOpen(false)"
                                >
                                    <div
                                        class="flex items-start justify-between gap-4"
                                    >
                                        <span
                                            class="text-[1.85rem] leading-none"
                                            >{{ action.icon }}</span
                                        >
                                        <span
                                            class="rounded-full bg-white/14 px-3 py-1 text-[10px] font-black tracking-[0.18em] uppercase"
                                        >
                                            {{ props.linkHintLabel }}
                                        </span>
                                    </div>
                                    <h3
                                        class="mt-7 text-[1.45rem] leading-tight font-extrabold"
                                    >
                                        {{ action.title }}
                                    </h3>
                                    <p
                                        class="mt-3 text-sm leading-6 text-current/80"
                                    >
                                        {{ action.body }}
                                    </p>
                                </Link>

                                <button
                                    v-else
                                    type="button"
                                    class="block min-h-[12.5rem] w-full rounded-[1.75rem] p-5 text-left transition-transform active:scale-[0.985]"
                                    :class="toneClasses[action.tone]"
                                    @click="handleAction(action)"
                                >
                                    <div
                                        class="flex items-start justify-between gap-4"
                                    >
                                        <span
                                            class="text-[1.85rem] leading-none"
                                            >{{ action.icon }}</span
                                        >
                                        <span
                                            class="rounded-full bg-white/14 px-3 py-1 text-[10px] font-black tracking-[0.18em] uppercase"
                                        >
                                            {{ props.actionHintLabel }}
                                        </span>
                                    </div>
                                    <h3
                                        class="mt-7 text-[1.45rem] leading-tight font-extrabold"
                                    >
                                        {{ action.title }}
                                    </h3>
                                    <p
                                        class="mt-3 text-sm leading-6 text-current/80"
                                    >
                                        {{ action.body }}
                                    </p>
                                </button>
                            </SwiperSlide>
                        </Swiper>
                    </div>
                </div>
            </DrawerContent>
        </DrawerPortal>
    </DrawerRoot>
</template>
