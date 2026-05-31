<script setup lang="ts">
import PublicCommunityCard from '@/Components/Public/PublicCommunityCard.vue';
import { Head } from '@inertiajs/vue3';

const sideItems = ['Dashboard', 'Reading', 'Listening', 'Grammar', 'AI Tutor'];
const vocabItems = [
    { title: 'Akademische Begriffe', count: '24 words', mastery: '90% mastery', icon: '文' },
    { title: 'Diskussion & Debatte', count: '15 words', mastery: '45% mastery', icon: '✦' },
];
</script>

<template>
    <Head title="DeutschCurator" />

    <div
        class="min-h-screen"
        style="
            --shell-bg: #f6f6ff;
            --shell-surface: #ffffff;
            --shell-surface-alt: #eef0ff;
            --shell-border: rgba(111, 118, 142, 0.14);
            --shell-text: #272e42;
            --shell-muted: #535b71;
            --shell-accent: #0049e6;
            --shell-accent-soft: #829bff;
            --shell-secondary: #702ae1;
            --shell-success: #26fedc;
        "
    >
        <div class="flex min-h-screen">
            <aside class="hidden w-80 flex-col bg-[var(--shell-surface-muted)] px-8 py-8 lg:flex">
                <div class="text-[2rem] font-extrabold text-[var(--shell-text)]">DeutschCurator</div>

                <div class="mt-12 flex items-start gap-4">
                    <div class="app-icon-tile grid h-14 w-14 place-items-center rounded-2xl text-xl font-bold text-[#001a63]">WM</div>
                    <div>
                        <div class="text-3xl font-semibold tracking-[-0.05em]">Wilhelm Müller</div>
                        <div class="mt-1 text-sm uppercase tracking-[0.16em] text-[var(--shell-muted)]">Level B2 Upper Intermediate</div>
                    </div>
                </div>

                <nav class="mt-16 space-y-3">
                    <a
                        v-for="(item, index) in sideItems"
                        :key="item"
                        href="#"
                        class="flex items-center gap-4 rounded-2xl px-5 py-4 text-[1.125rem] font-medium transition"
                        :class="index === 0
                            ? 'bg-[var(--shell-surface)] text-[#0049e6] shadow-[0_10px_30px_rgba(0,73,230,0.08)]'
                            : 'text-[var(--shell-muted)] hover:bg-[var(--shell-surface)]/75'"
                    >
                        <span class="text-xl">{{ index === 0 ? '◧' : index === 1 ? '▤' : index === 2 ? '◍' : index === 3 ? '⌘' : '✦' }}</span>
                        <span>{{ item }}</span>
                    </a>
                </nav>

                <div class="mt-auto">
                    <button class="w-full rounded-2xl bg-[var(--shell-accent)] px-6 py-4 text-lg font-bold text-white shadow-[0_16px_40px_rgba(0,73,230,0.28)]">
                        Try for Free
                    </button>
                    <div class="mt-8 space-y-4 text-xl text-[var(--shell-muted)]">
                        <div>⚙ Settings</div>
                        <div>？ Help</div>
                    </div>
                </div>
            </aside>

            <main class="flex-1 px-8 py-8 lg:px-14">
                <header class="mb-10 flex flex-wrap items-start justify-between gap-6">
                    <div>
                        <h1 class="text-6xl font-extrabold text-[var(--shell-text)] md:text-7xl">Willkommen, Wilhelm.</h1>
                        <p class="shell-muted mt-4 text-2xl leading-10">
                            Ready for your TestDaF preparation today?
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="inline-flex items-center gap-3 rounded-full bg-[var(--shell-surface-alt)] px-4 py-3 font-bold text-[var(--shell-accent)]">
                            <span class="grid h-9 w-9 place-items-center rounded-full bg-[#26fedc] text-[#005d4f]">⚡</span>
                            12 Day Streak
                        </div>
                        <button class="app-icon-tile grid h-14 w-14 place-items-center rounded-2xl text-[var(--shell-text)]">
                            ●
                        </button>
                    </div>
                </header>

                <div class="grid gap-6 lg:grid-cols-[1.55fr_0.75fr]">
                    <section class="app-soft-card rounded-[2.25rem] p-8">
                        <span class="inline-flex rounded-full bg-[#26fedc] px-4 py-2 text-xs font-black uppercase tracking-[0.24em] text-[#005d4f]">
                            Recommended session
                        </span>
                        <h2 class="mt-8 max-w-5xl text-6xl font-extrabold leading-[1.03] text-[var(--shell-text)]">
                            Mastering Complex Word Order in Nebensätze
                        </h2>
                        <p class="mt-6 max-w-3xl text-2xl leading-10 text-[var(--shell-muted)]">
                            Level B2 grammar module focusing on indirect questions and causal clauses.
                        </p>
                        <div class="mt-24 flex flex-wrap items-center justify-between gap-6">
                            <button class="app-btn-primary inline-flex items-center gap-4 rounded-2xl px-8 py-5 text-2xl font-bold shadow-[0_18px_48px_rgba(0,73,230,0.28)]">
                                Start Training
                                <span>→</span>
                            </button>
                            <div class="flex items-center gap-10 text-2xl font-medium text-[var(--shell-text)]">
                                <div class="flex items-center gap-3">◔ 15 Min</div>
                                <div class="flex items-center gap-3">★ 250 XP</div>
                            </div>
                        </div>
                    </section>

                    <section class="app-frost-panel rounded-[2.25rem] p-8">
                        <div class="flex items-center gap-4">
                            <div class="grid h-14 w-14 place-items-center rounded-full bg-gradient-to-br from-[#702ae1] to-[#26fedc] text-white">✺</div>
                            <h3 class="text-4xl font-bold">AI Insights</h3>
                        </div>
                        <div class="mt-8 space-y-6">
                            <div class="rounded-[2rem] border-l-4 border-[#702ae1] bg-[var(--shell-surface-alt)] p-6 text-xl leading-10">
                                "You've improved by <span class="font-bold text-[#702ae1]">14%</span> in Listening comprehension this week."
                            </div>
                            <div class="rounded-[2rem] border-l-4 border-[#26fedc] bg-[var(--shell-surface-alt)] p-6 text-xl leading-10">
                                "Typical error detected: Nominative vs Accusative after 'für'."
                            </div>
                        </div>
                        <button class="mt-10 w-full rounded-2xl border-2 border-[#702ae1] px-6 py-4 text-2xl font-bold text-[#702ae1]">
                            View Full Report
                        </button>
                    </section>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-[1.05fr_0.72fr_0.62fr]">
                    <section class="app-frost-panel rounded-[2.25rem] p-8">
                        <div class="flex items-center justify-between">
                            <h3 class="text-4xl font-bold">Vocabulary Deck</h3>
                            <span class="text-xs font-black uppercase tracking-[0.24em] text-[var(--shell-muted)]">48 words due</span>
                        </div>
                        <div class="mt-10 space-y-8">
                            <div
                                v-for="item in vocabItems"
                                :key="item.title"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center gap-6">
                                    <div class="app-icon-tile grid h-16 w-16 place-items-center rounded-2xl text-3xl text-[#0049e6]">
                                        {{ item.icon }}
                                    </div>
                                    <div>
                                        <div class="text-3xl font-bold">{{ item.title }}</div>
                                        <div class="mt-2 text-lg text-[var(--shell-muted)]">{{ item.count }} • {{ item.mastery }}</div>
                                    </div>
                                </div>
                                <span class="text-4xl text-[var(--shell-muted)]">›</span>
                            </div>
                        </div>
                    </section>

                    <section class="app-soft-card rounded-[2.25rem] p-8">
                        <div class="text-xs font-black uppercase tracking-[0.24em] text-[var(--shell-muted)]">Total XP</div>
                        <div class="mt-3 text-6xl font-extrabold">12,450</div>
                        <div class="mt-8 h-3 rounded-full bg-[#c9d4ff]">
                            <div class="h-full w-[72%] rounded-full bg-[#0049e6]"></div>
                        </div>
                        <div class="mt-3 text-sm font-bold text-[#0049e6]">72% to Next Level</div>
                    </section>

                    <section class="rounded-[2.25rem] bg-[#0049e6] p-8 text-white shadow-[0_20px_48px_rgba(0,73,230,0.3)]">
                        <div class="text-xs font-black uppercase tracking-[0.24em] text-white/60">Study Hours</div>
                        <div class="mt-3 text-6xl font-extrabold">42.5</div>
                        <div class="mt-10 text-lg text-white/80">Top 5% of learners this month</div>
                    </section>
                </div>

                <PublicCommunityCard
                    class="mt-6"
                    title="Join the Community Practice"
                    body="Live speaking group starts in 15 minutes."
                    cta-href="#"
                    cta-label="Reserve my spot"
                    count-label="+42"
                    size="large"
                />

                <button class="app-btn-primary fixed bottom-8 right-8 grid h-16 w-16 place-items-center rounded-2xl text-4xl font-light shadow-[0_18px_48px_rgba(0,73,230,0.35)]">
                    +
                </button>
            </main>
        </div>
    </div>
</template>
