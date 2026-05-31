<script setup lang="ts">
import { usePublicLocale } from '@/composables/usePublicLocale';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Form, Link } from '@inertiajs/vue3';

const { t } = usePublicLocale();
</script>

<template>
    <AuthLayout
        :page-title="t('auth.verify.page_title')"
        :title="t('auth.verify.title')"
        :subtitle="t('auth.verify.subtitle')"
    >
        <div>
            <p class="app-kicker">{{ t('auth.verify.kicker') }}</p>
            <h2 class="mt-4 text-4xl font-extrabold leading-[0.98] text-[var(--shell-text)] md:text-5xl">{{ t('auth.verify.heading') }}</h2>
            <p class="shell-muted mt-4 text-base leading-7">
                {{ t('auth.verify.body') }}
            </p>
        </div>

        <div class="app-soft-card mt-10 rounded-[1.25rem] p-6">
            <div class="app-label">{{ t('auth.verify.status_label') }}</div>
            <div class="mt-3 text-2xl font-extrabold text-[var(--shell-text)]">
                {{ t('auth.verify.status_value') }}
            </div>
            <p class="mt-3 text-sm leading-7 text-[var(--shell-muted)]">
                {{ t('auth.verify.status_hint') }}
            </p>
        </div>

        <Form action="/email/verification-notification" method="post" v-slot="{ processing }" class="mt-8">
            <button
                type="submit"
                :disabled="processing"
                class="app-btn-primary w-full rounded-full px-6 py-4 text-lg disabled:cursor-not-allowed disabled:opacity-60"
            >
                {{ processing ? t('auth.verify.resend_sending') : t('auth.verify.resend') }}
            </button>
        </Form>

        <div class="mt-8 flex justify-between gap-4 text-sm">
            <Link href="/dashboard" class="app-link-primary">{{ t('auth.common.to_dashboard') }}</Link>
            <Link href="/" class="app-link-muted">{{ t('auth.common.back_home') }}</Link>
        </div>
    </AuthLayout>
</template>
