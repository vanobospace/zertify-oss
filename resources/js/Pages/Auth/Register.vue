<script setup lang="ts">
import { usePublicLocale } from '@/composables/usePublicLocale';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Form, Link } from '@inertiajs/vue3';

const { t } = usePublicLocale();
</script>

<template>
    <AuthLayout
        :page-title="t('auth.register.page_title')"
        :title="t('auth.register.title')"
        :subtitle="t('auth.register.subtitle')"
    >
        <div>
            <p class="app-kicker">{{ t('auth.register.kicker') }}</p>
            <h2 class="mt-4 text-4xl font-extrabold leading-[0.98] text-[var(--shell-text)] md:text-5xl">{{ t('auth.register.heading') }}</h2>
            <p class="shell-muted mt-4 text-base leading-7">
                {{ t('auth.register.body') }}
            </p>
        </div>

        <Form action="/register" method="post" v-slot="{ errors, processing }" class="mt-10 space-y-6">
            <div class="space-y-2">
                <label for="name" class="app-label">{{ t('auth.field.name') }}</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    autocomplete="name"
                    required
                    class="app-input"
                >
                <p v-if="errors.name" class="text-sm font-medium text-red-700">{{ errors.name }}</p>
            </div>

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

            <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-2">
                    <label for="password" class="app-label">{{ t('auth.field.password') }}</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="app-input"
                    >
                    <p v-if="errors.password" class="text-sm font-medium text-red-700">{{ errors.password }}</p>
                </div>

                <div class="space-y-2">
                    <label for="password_confirmation" class="app-label">
                        {{ t('auth.field.password_confirmation') }}
                    </label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="app-input"
                    >
                </div>
            </div>

            <button
                type="submit"
                :disabled="processing"
                class="app-btn-primary w-full rounded-full px-6 py-4 text-lg disabled:cursor-not-allowed disabled:opacity-60"
            >
                {{ processing ? t('auth.register.submitting') : t('auth.register.submit') }}
            </button>
        </Form>

        <div class="mt-8 flex flex-wrap items-center justify-between gap-4 text-sm">
            <Link href="/login" class="app-link-primary">{{ t('auth.register.have_account') }}</Link>
            <Link href="/" class="app-link-muted">{{ t('auth.common.back_home') }}</Link>
        </div>
    </AuthLayout>
</template>
