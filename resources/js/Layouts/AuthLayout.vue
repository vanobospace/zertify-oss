<script setup lang="ts">
import PublicSecondaryPromoPanel from '@/Components/Public/PublicSecondaryPromoPanel.vue';
import PublicStatCard from '@/Components/Public/PublicStatCard.vue';
import { usePublicLocale } from '@/composables/usePublicLocale';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    title: string;
    subtitle: string;
    pageTitle: string;
}>();

const { t } = usePublicLocale();
</script>

<template>
    <div class="min-h-screen bg-[var(--shell-bg)] px-4 py-6 sm:px-6 md:px-10">
        <Head :title="pageTitle" />

        <div class="mx-auto flex max-w-6xl items-center justify-between">
            <Link href="/" class="block">
                <div class="text-3xl font-extrabold tracking-[-0.04em] text-[var(--shell-accent)]">Zertify</div>
                <div class="mt-1 text-[0.65rem] font-bold uppercase tracking-[0.22em] text-[var(--shell-muted)]">
                    zertify.app
                </div>
            </Link>

            <div class="hidden text-sm font-medium text-[var(--shell-muted)] lg:block">
                {{ t('auth.layout.support') }}
            </div>
        </div>

        <div class="mx-auto mt-6 grid max-w-6xl gap-5 lg:mt-10 lg:gap-8 lg:grid-cols-[1.05fr_0.95fr]">
            <section class="app-frost-panel order-2 rounded-[1.5rem] p-6 md:p-8 lg:order-1 lg:rounded-[2rem] lg:p-10">
                <p class="app-kicker">{{ t('auth.layout.kicker') }}</p>
                <h1 class="mt-4 text-5xl font-extrabold leading-[0.95] text-[var(--shell-text)] md:text-6xl">
                    {{ title }}
                </h1>
                <p class="shell-muted mt-4 max-w-xl text-base leading-7 md:text-lg md:leading-8">
                    {{ subtitle }}
                </p>

                <div class="mt-6 grid gap-3 md:mt-8 md:grid-cols-2">
                    <PublicStatCard :label="t('auth.layout.module_label')" :value="t('auth.layout.module_value')" />
                    <PublicStatCard :label="t('auth.layout.feedback_label')" :value="t('auth.layout.feedback_value')" />
                </div>

                <PublicSecondaryPromoPanel
                    class="mt-6 md:mt-8"
                    :kicker="t('auth.layout.learners_kicker')"
                    :title="t('auth.layout.learners_title')"
                    :body="t('auth.layout.learners_body')"
                />
            </section>

            <section class="app-frost-panel order-1 rounded-[1.5rem] p-6 md:p-8 lg:order-2 lg:rounded-[2rem] lg:p-10">
                <slot />
            </section>
        </div>
    </div>
</template>
