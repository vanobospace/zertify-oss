<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

const previews = [
    {
        title: 'Scholarly AI',
        href: '/preview/scholarly-ai',
        tag: 'Editorial product shell',
        body: 'Более тёплый академический layout с community-блоком, словарными деками и AI-рекомендацией.',
    },
    {
        title: 'DeutschCurator',
        href: '/preview/deutsch-curator',
        tag: 'Citrus Cobalt shell',
        body: 'Более технологичный и энергичный продуктовый shell с сильным hero, insight rail и ярким CTA.',
    },
];
</script>

<template>
    <Head title="Preview hub" />

    <div class="min-h-screen px-6 py-10 md:px-10">
        <div class="mx-auto max-w-6xl">
            <div class="app-frost-panel rounded-[2rem] p-8 md:p-10">
                <p class="app-kicker">
                    Main page preview set
                </p>
                <h1 class="mt-4 text-5xl font-extrabold text-[var(--shell-text)] md:text-7xl">
                    Два новых направления главной страницы.
                </h1>
                <p class="shell-muted mt-5 max-w-3xl text-lg leading-8">
                    Оба варианта собраны как полноценные front-facing product shells на Inertia/Vue, но с разным визуальным характером и разным ощущением интерфейса.
                </p>

                <div class="mt-10 grid gap-6 lg:grid-cols-2">
                    <article
                        v-for="preview in previews"
                        :key="preview.href"
                        class="app-soft-card rounded-[1.75rem] p-6"
                    >
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[var(--shell-muted)]">
                            {{ preview.tag }}
                        </p>
                        <h2 class="mt-3 text-3xl font-bold">
                            {{ preview.title }}
                        </h2>
                        <p class="shell-muted mt-4 text-base leading-7">
                            {{ preview.body }}
                        </p>
                        <div class="mt-8 flex items-center gap-4">
                            <Link
                                :href="preview.href"
                                class="app-btn-primary inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm"
                            >
                                Открыть макет
                            </Link>
                            <span class="text-sm font-medium text-[var(--shell-muted)]">
                                {{ preview.href }}
                            </span>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </div>
</template>
