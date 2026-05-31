<script setup lang="ts">
import { usePublicLocale } from '@/composables/usePublicLocale';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Form, Link } from '@inertiajs/vue3';

const { t } = usePublicLocale();
</script>

<template>
    <AuthLayout
        :page-title="t('auth.login.page_title')"
        :title="t('auth.login.title')"
        :subtitle="t('auth.login.subtitle')"
    >
        <div>
            <p class="app-kicker">{{ t('auth.login.kicker') }}</p>
            <h2 class="mt-3 text-4xl font-extrabold leading-[0.98] text-[var(--shell-text)] md:text-5xl">{{ t('auth.login.heading') }}</h2>
            <p class="shell-muted mt-3 text-base leading-7">
                {{ t('auth.login.body') }}
            </p>
        </div>

        <Form action="/login" method="post" v-slot="{ errors, processing }" class="mt-7 space-y-5 md:mt-10 md:space-y-6">
            <div class="space-y-2">
                <label for="email" class="app-label">{{ t('auth.field.email') }}</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    required
                    class="app-input"
                >
                <p v-if="errors.email" class="text-sm font-medium text-red-700">{{ errors.email }}</p>
            </div>

            <div class="space-y-2">
                <label for="password" class="app-label">{{ t('auth.field.password') }}</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                    class="app-input"
                >
                <p v-if="errors.password" class="text-sm font-medium text-red-700">{{ errors.password }}</p>
            </div>

            <label class="flex items-center gap-3 text-sm text-[var(--shell-muted)]">
                <input
                    name="remember"
                    type="checkbox"
                    value="1"
                    class="h-4 w-4 rounded border-[var(--shell-border)] text-[var(--shell-accent)] focus:ring-[var(--shell-accent)]"
                >
                <span>{{ t('auth.login.remember') }}</span>
            </label>

            <button
                type="submit"
                :disabled="processing"
                class="app-btn-primary w-full rounded-full px-6 py-4 text-lg disabled:cursor-not-allowed disabled:opacity-60"
            >
                {{ processing ? t('auth.login.submitting') : t('auth.login.submit') }}
            </button>
        </Form>

        <div class="mt-8 flex flex-wrap items-center justify-between gap-4 text-sm">
            <Link href="/register" class="app-link-primary">{{ t('auth.login.no_account') }}</Link>
            <Link href="/" class="app-link-muted">{{ t('auth.common.back_home') }}</Link>
        </div>
    </AuthLayout>
</template>
