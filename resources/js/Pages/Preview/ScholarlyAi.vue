<script setup lang="ts">
import { usePublicLocale } from '@/composables/usePublicLocale';
import PublicAiPanel from '@/Components/Public/PublicAiPanel.vue';
import PublicCommunityCard from '@/Components/Public/PublicCommunityCard.vue';
import PublicDeckCard from '@/Components/Public/PublicDeckCard.vue';
import PublicStudyChartCard from '@/Components/Public/PublicStudyChartCard.vue';
import PublicAppLayout from '@/Layouts/PublicAppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({
    layout: PublicAppLayout,
});

const page = usePage<{
    auth: {
        user: {
            name: string;
        } | null;
    };
}>();

const { t, tr } = usePublicLocale();
const userName = computed(() => page.props.auth.user?.name ?? t('dashboard.user_fallback'));
const heroTitle = computed(() => (page.props.auth.user ? tr('dashboard.welcome', { name: userName.value }) : t('home.hero.title')));

const decks = computed(() => [
    {
        title: 'Lesen',
        icon: '🍴',
        stat: t('dashboard.deck.mastered'),
        progressClass: 'w-full bg-emerald-700',
        badgeClass: 'bg-[#a9f7b7] text-[#004c22]',
    },
    {
        title: 'Hören',
        icon: '💼',
        stat: '142 / 200',
        progressClass: 'w-[71%] bg-amber-700',
        badgeClass: 'bg-[#ffc69a] text-[#532a00]',
    },
    {
        title: 'Sprachbausteine',
        icon: '🧪',
        stat: '45 / 300',
        progressClass: 'w-[15%] bg-[#8c4a00]',
        badgeClass: 'bg-[#ece7df] text-[#6d7788]',
    },
]);

const primaryActionHref = computed(() => (page.props.auth.user ? '/dashboard' : '/register'));
const primaryActionLabel = computed(() => (page.props.auth.user ? t('layout.action.to_dashboard') : t('layout.action.register_now')));
const secondaryActionHref = computed(() => (page.props.auth.user ? '/dashboard' : '/login'));
const secondaryActionLabel = computed(() => (page.props.auth.user ? t('layout.action.open_dashboard') : t('layout.action.sign_in')));

const aiBodyParts = computed(() => {
    const marker = '__HIGHLIGHT__';
    const localized = tr('home.ai.body', { highlight: marker });
    const [before, after] = localized.split(marker);

    return {
        before: before ?? '',
        highlight: t('home.ai.highlight'),
        after: after ?? '',
    };
});

const studyBars = [35, 58, 42, 82, 30, 95, 48];
const studyDayLabels = computed(() => [
    t('common.weekday.mon'),
    t('common.weekday.tue'),
    t('common.weekday.wed'),
    t('common.weekday.thu'),
    t('common.weekday.fri'),
    t('common.weekday.sat'),
    t('common.weekday.sun'),
]);
</script>

<template>
    <Head title="Zertify" />

    <!-- Phone -->
    <div class="space-y-5 md:hidden">
        <section class="space-y-3">
            <h1 class="text-[2.85rem] font-extrabold leading-[0.95] text-[var(--shell-text)] sm:text-[3.2rem]">
                {{ heroTitle }}
            </h1>
            <p class="shell-muted max-w-3xl text-[1.05rem] leading-8">
                {{ t('home.hero.subtitle') }}
            </p>
        </section>

        <section class="app-streak-panel rounded-[1.7rem] p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[var(--shell-success-text)] opacity-75">{{ t('dashboard.stat.streak') }}</p>
                    <h2 class="mt-1 text-5xl font-black leading-none">{{ t('dashboard.stat.streak_value') }}</h2>
                </div>
                <div class="grid h-12 w-12 place-items-center rounded-full bg-white/30 text-2xl">🔥</div>
            </div>
            <p class="mt-3 text-sm font-medium text-[var(--shell-success-text)] opacity-75">{{ t('dashboard.milestone_hint') }}</p>
        </section>

        <PublicStudyChartCard
            :title="t('home.study.title')"
            :subtitle="t('home.study.subtitle')"
            :period-label="t('home.study.period_weekly')"
            :bars="studyBars"
            :day-labels="studyDayLabels"
            :highlighted-index="2"
            size="compact"
        />

        <PublicAiPanel
            :kicker="t('home.ai.kicker')"
            :title="t('home.ai.title')"
            :body-before="aiBodyParts.before"
            :body-highlight="aiBodyParts.highlight"
            :body-after="aiBodyParts.after"
            :primary-href="primaryActionHref"
            :primary-label="primaryActionLabel"
            :secondary-href="secondaryActionHref"
            :secondary-label="secondaryActionLabel"
            size="compact"
        />

        <section>
            <div class="mb-4 flex items-center justify-between gap-4">
                <h2 class="text-[3.1rem] font-extrabold text-[var(--shell-text)]">{{ t('home.vocab.title') }}</h2>
                <a href="#" class="text-sm font-bold text-[#8c4a00]">{{ t('home.vocab.view_all') }}</a>
            </div>
            <div class="space-y-3">
                <PublicDeckCard
                    v-for="deck in decks"
                    :key="deck.title"
                    :title="deck.title"
                    :stat="deck.stat"
                    :icon="deck.icon"
                    :progress-class="deck.progressClass"
                    :badge-class="deck.badgeClass"
                    size="compact"
                />
            </div>
        </section>

        <PublicCommunityCard
            :title="t('home.community.title')"
            :body="t('home.community.body')"
            :cta-href="secondaryActionHref"
            :cta-label="secondaryActionLabel"
            count-label="+12"
            size="compact"
        />
    </div>

    <!-- iPad -->
    <div class="hidden space-y-8 md:block lg:hidden">
        <section class="grid gap-6 md:grid-cols-[1.45fr_0.7fr] md:items-end">
            <div>
                <h1 class="max-w-4xl text-[4.35rem] font-extrabold leading-[0.92] text-[var(--shell-text)]">{{ heroTitle }}</h1>
                <p class="shell-muted mt-4 max-w-2xl text-[1.08rem] leading-8">{{ t('home.hero.subtitle') }}</p>
            </div>
            <div class="app-streak-panel justify-self-end rounded-[1.6rem] px-7 py-5">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-[var(--shell-success-text)] opacity-80">{{ t('dashboard.stat.streak') }}</p>
                <div class="mt-1 text-[2.35rem] font-black leading-none">{{ t('dashboard.stat.streak_value') }} 🔥</div>
            </div>
        </section>

        <div class="grid gap-5 md:grid-cols-[1.2fr_0.8fr]">
            <PublicStudyChartCard
                :title="t('home.study.title')"
                :subtitle="t('home.study.subtitle')"
                :period-label="t('home.study.period_weekly')"
                :bars="studyBars"
                :day-labels="studyDayLabels"
                :highlighted-index="2"
                size="tablet"
            />

            <PublicCommunityCard
                :title="t('home.community.title')"
                :body="t('home.community.body')"
                :cta-href="secondaryActionHref"
                :cta-label="secondaryActionLabel"
                count-label="+12"
                size="tablet"
            />
        </div>

        <PublicAiPanel
            :kicker="t('home.ai.kicker')"
            :title="t('home.ai.title')"
            :body-before="aiBodyParts.before"
            :body-highlight="aiBodyParts.highlight"
            :body-after="aiBodyParts.after"
            :primary-href="primaryActionHref"
            :primary-label="primaryActionLabel"
            :secondary-href="secondaryActionHref"
            :secondary-label="secondaryActionLabel"
            size="tablet"
        />

        <section>
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-[3.25rem] font-extrabold text-[var(--shell-text)]">{{ t('home.vocab.title') }}</h2>
                <a href="#" class="text-sm font-bold text-[#8c4a00]">{{ t('home.vocab.view_all') }}</a>
            </div>
            <div class="grid grid-cols-2 gap-5">
                <PublicDeckCard
                    v-for="(deck, index) in decks"
                    :key="deck.title"
                    :title="deck.title"
                    :stat="deck.stat"
                    :icon="deck.icon"
                    :progress-class="deck.progressClass"
                    :badge-class="deck.badgeClass"
                    size="tablet"
                    :class="index === 2 ? 'sm:col-span-2' : ''"
                />
            </div>
        </section>
    </div>

    <!-- Desktop -->
    <div class="hidden lg:block">
        <section class="flex items-end justify-between gap-8">
            <div>
                <h1 class="text-[4.75rem] font-extrabold leading-[0.92] text-[var(--shell-text)]">{{ heroTitle }}</h1>
                <p class="shell-muted mt-4 max-w-3xl text-[1.1rem] leading-9">{{ t('home.hero.subtitle') }}</p>
            </div>
            <div class="app-streak-panel shrink-0 rounded-[1.35rem] px-7 py-4">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">🔥</span>
                    <div>
                        <div class="text-[2.1rem] font-black leading-none">{{ t('dashboard.stat.streak_value') }}</div>
                        <div class="mt-1 text-xs font-bold uppercase tracking-[0.18em] text-[var(--shell-success-text)] opacity-80">{{ t('dashboard.stat.streak') }}</div>
                    </div>
                </div>
            </div>
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.8fr_0.9fr]">
            <PublicStudyChartCard
                :title="t('home.study.title')"
                :subtitle="t('home.study.subtitle')"
                :period-label="t('home.study.period_weekly')"
                :bars="studyBars"
                :day-labels="studyDayLabels"
                :highlighted-index="5"
                size="large"
            />

            <PublicCommunityCard
                :title="t('home.community.title')"
                :body="t('home.community.body')"
                :cta-href="secondaryActionHref"
                :cta-label="secondaryActionLabel"
                count-label="+42"
                size="large"
            />
        </div>

        <section class="mt-10">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-[3.1rem] font-extrabold text-[var(--shell-text)]">{{ t('home.vocab.title') }}</h2>
                <a href="#" class="text-lg font-bold text-[#8c4a00]">{{ t('home.vocab.view_all') }}</a>
            </div>
            <div class="grid gap-6 lg:grid-cols-3">
                <PublicDeckCard
                    v-for="deck in decks"
                    :key="deck.title"
                    :title="deck.title"
                    :stat="deck.stat"
                    :icon="deck.icon"
                    :progress-class="deck.progressClass"
                    :badge-class="deck.badgeClass"
                    size="large"
                />
            </div>
        </section>

        <PublicAiPanel
            class="mt-10"
            :kicker="t('home.ai.kicker')"
            :title="t('home.ai.title')"
            :body-before="aiBodyParts.before"
            :body-highlight="aiBodyParts.highlight"
            :body-after="aiBodyParts.after"
            :primary-href="primaryActionHref"
            :primary-label="primaryActionLabel"
            :secondary-href="secondaryActionHref"
            :secondary-label="secondaryActionLabel"
            size="large"
        />
    </div>
</template>
