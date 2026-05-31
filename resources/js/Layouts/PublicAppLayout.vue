<script setup lang="ts">
import AppThemeIcon from '@/Components/App/AppThemeIcon.vue';
import {
    type PublicLocale,
    usePublicLocale,
} from '@/composables/usePublicLocale';
import { useSidebarState } from '@/composables/useSidebarState';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type ThemeMode = 'light' | 'dark' | 'system';
type TypographyPreset =
    | 'classic'
    | 'source'
    | 'source-compact'
    | 'plex'
    | 'editorial'
    | 'unified';

const page = usePage<{
    auth: {
        user: {
            name: string;
            email: string;
            role: string;
        } | null;
    };
    sidebarOpen?: boolean;
}>();

const { currentLocale, localeOptions, setLocale, t, direction, isRtl } =
    usePublicLocale();

const isAuthenticated = computed(() => page.props.auth.user !== null);
const canUseTypeLab = computed(() => {
    const user = page.props.auth.user;

    if (! user) {
        return false;
    }

    return user.role === 'admin';
});
const primaryActionHref = computed(() =>
    isAuthenticated.value ? '/dashboard' : '/register',
);
const primaryActionLabel = computed(() =>
    isAuthenticated.value
        ? t('layout.action.to_dashboard')
        : t('layout.action.start_learning'),
);
const secondaryActionHref = computed(() =>
    isAuthenticated.value ? '/dashboard' : '/login',
);
const secondaryActionLabel = computed(() =>
    isAuthenticated.value
        ? t('layout.action.open_dashboard')
        : t('layout.action.sign_in'),
);

const { sidebarOpen, toggleSidebar } = useSidebarState(
    page.props.sidebarOpen ?? true,
);

const currentTheme = ref<ThemeMode>('system');
const currentTypographyPreset = ref<TypographyPreset>('source');
const languageDetailsRef = ref<HTMLDetailsElement | null>(null);
const quickMenuDetailsRef = ref<HTMLDetailsElement | null>(null);
const themeMediaQueryRef = ref<MediaQueryList | null>(null);
const footerRef = ref<HTMLElement | null>(null);
const desktopQuickDockOffset = ref(0);
const mobileSettingsOpen = ref(false);
const mobileMenuOpen = ref(false);
const mobileModuleHeaderHidden = ref(false);
const lastScrollY = ref(0);

const navItems = computed(() => [
    { key: 'lessons', label: t('layout.nav.lessons') },
    { key: 'library', label: t('layout.nav.library') },
    { key: 'progress', label: t('layout.nav.progress') },
    { key: 'ai', label: t('layout.nav.ai_tutor') },
]);

const sideItems = computed(() => [
    {
        key: 'dashboard',
        icon: 'dashboard',
        label: t('layout.sidebar.dashboard'),
        href: '/dashboard',
    },
    {
        key: 'courses',
        icon: 'courses',
        label: t('layout.sidebar.courses'),
        href: '#',
    },
]);

const currentPath = computed(() => {
    const [path] = page.url.split('?');

    if (!path) {
        return '/';
    }

    return path.startsWith('/') ? path : `/${path}`;
});
const isModulePage = computed(() => currentPath.value.startsWith('/modules/'));

const themeOptions = computed<Array<{ value: ThemeMode; label: string }>>(
    () => [
        { value: 'light', label: t('theme.light') },
        { value: 'dark', label: t('theme.dark') },
        { value: 'system', label: t('theme.system') },
    ],
);
const typographyPresets = computed<
    Array<{
        value: TypographyPreset;
        label: string;
        body: string;
        display: string;
        note: string;
    }>
>(() => [
    {
        value: 'classic',
        label: 'OG',
        body: 'Inter',
        display: 'Manrope',
        note: 'Original project feel, neutral UI sans.',
    },
    {
        value: 'source',
        label: 'SS3',
        body: 'Source Sans 3',
        display: 'Manrope',
        note: 'Calmer long-form reading with familiar headings.',
    },
    {
        value: 'source-compact',
        label: 'SS3 Tight',
        body: 'Source Sans 3',
        display: 'Manrope',
        note: 'Same pairing, denser body copy for tighter layouts.',
    },
    {
        value: 'plex',
        label: 'Plex',
        body: 'IBM Plex Sans',
        display: 'Manrope',
        note: 'More editorial and technical, sharper text texture.',
    },
    {
        value: 'editorial',
        label: 'Editorial',
        body: 'IBM Plex Sans',
        display: 'Source Sans 3',
        note: 'Less contrast between UI and reading, softer titles.',
    },
    {
        value: 'unified',
        label: 'Unified',
        body: 'Source Sans 3',
        display: 'Source Sans 3',
        note: 'One-family system, smooth and consistent.',
    },
]);

const languageMenuAlignmentClass = computed(() =>
    isRtl.value ? 'left-0' : 'right-0',
);
const quickMenuAlignmentClass = computed(() =>
    isRtl.value ? 'right-0' : 'left-0',
);
const sidebarToggleEdgeClass = computed(() =>
    isRtl.value ? '-left-3' : '-right-3',
);
const sidebarTogglePath = computed(() => {
    const leftChevron = 'm15 19-7-7 7-7';
    const rightChevron = 'm9 5 7 7-7 7';

    if (isRtl.value) {
        return sidebarOpen.value ? rightChevron : leftChevron;
    }

    return sidebarOpen.value ? leftChevron : rightChevron;
});
const quickMenuPrimaryLabel = computed(() => {
    if (isAuthenticated.value) {
        return page.props.auth.user?.name ?? t('layout.quick.title');
    }

    return t('layout.quick.title');
});
const quickMenuSecondaryLabel = computed(() => {
    if (isAuthenticated.value) {
        return page.props.auth.user?.email ?? '';
    }

    return '';
});
const handleSystemThemeChange = (): void => {
    if (currentTheme.value === 'system') {
        applyTheme();
    }
};
const handleGlobalClick = (event: MouseEvent): void => {
    const target = event.target;

    if (!(target instanceof Node)) {
        return;
    }

    if (
        languageDetailsRef.value &&
        !languageDetailsRef.value.contains(target)
    ) {
        languageDetailsRef.value.open = false;
    }

    if (
        quickMenuDetailsRef.value &&
        !quickMenuDetailsRef.value.contains(target)
    ) {
        quickMenuDetailsRef.value.open = false;
    }
};

const currentLanguageShort = computed(() => {
    const selected = localeOptions.find(
        (option) => option.code === currentLocale.value,
    );

    return selected?.short ?? 'DE';
});

const currentThemeLabel = computed(() => {
    const selected = themeOptions.value.find(
        (option) => option.value === currentTheme.value,
    );

    return selected?.label ?? 'System';
});
const currentTypographyLabel = computed(() => {
    const selected = typographyPresets.value.find(
        (option) => option.value === currentTypographyPreset.value,
    );

    return selected?.label ?? 'SS3';
});
const currentTypographyPresetMeta = computed(() => {
    return (
        typographyPresets.value.find(
            (option) => option.value === currentTypographyPreset.value,
        ) ?? typographyPresets.value[1]
    );
});

const desktopQuickDockStyle = computed<Record<string, string>>(() => {
    if (!sidebarOpen.value) {
        return {
            bottom: `${desktopQuickDockOffset.value}px`,
            width: 'var(--app-shell-sidebar-collapsed)',
        };
    }

    return {
        bottom: `${desktopQuickDockOffset.value}px`,
        width: 'var(--app-shell-sidebar-open)',
    };
});

const applyTheme = (): void => {
    if (typeof document === 'undefined' || typeof window === 'undefined') {
        return;
    }

    const prefersDark = window.matchMedia(
        '(prefers-color-scheme: dark)',
    ).matches;
    const resolvedTheme =
        currentTheme.value === 'system'
            ? prefersDark
                ? 'dark'
                : 'light'
            : currentTheme.value;

    for (const target of [document.documentElement, document.body]) {
        target.classList.remove('light', 'dark');
        target.classList.add(resolvedTheme);
        target.setAttribute('data-theme', resolvedTheme);
    }

    document.documentElement.style.colorScheme = resolvedTheme;
};

const applyTypographyPreset = (): void => {
    if (typeof document === 'undefined') {
        return;
    }

    for (const target of [document.documentElement, document.body]) {
        target.setAttribute('data-typography', currentTypographyPreset.value);
    }
};

const setTheme = (theme: ThemeMode): void => {
    currentTheme.value = theme;
    localStorage.setItem('zertify-theme', theme);
    applyTheme();
};

const cycleTheme = (): void => {
    const cycle: ThemeMode[] = ['light', 'dark', 'system'];
    const currentIndex = cycle.indexOf(currentTheme.value);
    const nextIndex = (currentIndex + 1) % cycle.length;
    setTheme(cycle[nextIndex]);
};

const setTypographyPreset = (preset: TypographyPreset): void => {
    currentTypographyPreset.value = preset;
    localStorage.setItem('zertify-typography', preset);
    applyTypographyPreset();
};

const setLanguage = (languageCode: PublicLocale): void => {
    setLocale(languageCode);

    if (languageDetailsRef.value) {
        languageDetailsRef.value.open = false;
    }
};

const cycleLocale = (): void => {
    const currentIndex = localeOptions.findIndex(
        (option) => option.code === currentLocale.value,
    );
    const nextIndex = (currentIndex + 1) % localeOptions.length;
    const nextLocale = localeOptions[nextIndex];

    if (nextLocale) {
        setLocale(nextLocale.code);
    }
};

const closeQuickMenu = (): void => {
    if (quickMenuDetailsRef.value) {
        quickMenuDetailsRef.value.open = false;
    }
};

const toggleMobileSettings = (): void => {
    mobileSettingsOpen.value = !mobileSettingsOpen.value;
};

const closeMobileSettings = (): void => {
    mobileSettingsOpen.value = false;
};

const toggleMobileMenu = (): void => {
    closeMobileSettings();
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const closeMobileMenu = (): void => {
    mobileMenuOpen.value = false;
};

const updateDesktopQuickDockOffset = (): void => {
    if (
        typeof window === 'undefined' ||
        window.innerWidth < 1280 ||
        !footerRef.value
    ) {
        desktopQuickDockOffset.value = 0;

        return;
    }

    const footerRect = footerRef.value.getBoundingClientRect();
    desktopQuickDockOffset.value = Math.max(
        0,
        window.innerHeight - footerRect.top,
    );
};

const handleWindowScroll = (): void => {
    updateDesktopQuickDockOffset();

    if (typeof window === 'undefined') {
        return;
    }

    if (
        !isModulePage.value ||
        window.innerWidth >= 768 ||
        mobileMenuOpen.value
    ) {
        mobileModuleHeaderHidden.value = false;
        lastScrollY.value = window.scrollY;

        return;
    }

    const currentScrollY = Math.max(window.scrollY, 0);
    const scrollDelta = currentScrollY - lastScrollY.value;
    const revealThreshold = 20;

    if (currentScrollY <= revealThreshold) {
        mobileModuleHeaderHidden.value = false;
        lastScrollY.value = currentScrollY;

        return;
    }

    if (Math.abs(scrollDelta) < 8) {
        lastScrollY.value = currentScrollY;

        return;
    }

    if (scrollDelta > 0) {
        mobileModuleHeaderHidden.value = true;
    }

    lastScrollY.value = currentScrollY;
};

onMounted(() => {
    const storedTheme = localStorage.getItem('zertify-theme');

    if (
        storedTheme === 'light' ||
        storedTheme === 'dark' ||
        storedTheme === 'system'
    ) {
        currentTheme.value = storedTheme;
    }

    const storedTypography = localStorage.getItem('zertify-typography');

    if (
        storedTypography === 'classic' ||
        storedTypography === 'source' ||
        storedTypography === 'source-compact' ||
        storedTypography === 'plex' ||
        storedTypography === 'editorial' ||
        storedTypography === 'unified'
    ) {
        currentTypographyPreset.value = storedTypography;
    }

    themeMediaQueryRef.value = window.matchMedia(
        '(prefers-color-scheme: dark)',
    );
    themeMediaQueryRef.value.addEventListener(
        'change',
        handleSystemThemeChange,
    );
    document.addEventListener('click', handleGlobalClick);
    lastScrollY.value = window.scrollY;
    window.addEventListener('scroll', handleWindowScroll, { passive: true });
    window.addEventListener('resize', updateDesktopQuickDockOffset, {
        passive: true,
    });
    applyTheme();
    applyTypographyPreset();
    updateDesktopQuickDockOffset();
});

onBeforeUnmount(() => {
    if (themeMediaQueryRef.value) {
        themeMediaQueryRef.value.removeEventListener(
            'change',
            handleSystemThemeChange,
        );
    }

    document.removeEventListener('click', handleGlobalClick);
    window.removeEventListener('scroll', handleWindowScroll);
    window.removeEventListener('resize', updateDesktopQuickDockOffset);
});
</script>

<template>
    <div
        :dir="direction"
        class="min-h-screen overflow-x-hidden bg-[var(--shell-bg)] text-[var(--shell-text)] xl:flex xl:flex-col xl:[--desktop-shell-header:5.75rem]"
    >
        <header
            class="fixed inset-x-0 top-0 z-50 border-b border-[var(--shell-border)] bg-[var(--shell-surface)]/96 backdrop-blur transition-transform duration-200 md:hidden"
            :class="
                mobileModuleHeaderHidden ? '-translate-y-full' : 'translate-y-0'
            "
        >
            <div
                class="mx-auto flex max-w-[1440px] items-center justify-between px-4 pt-[max(env(safe-area-inset-top),0.75rem)] pb-3 md:px-6"
            >
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="app-interactive flex h-10 w-10 items-center justify-center rounded-full border border-[var(--shell-border)] bg-[var(--shell-surface)] text-[var(--shell-text)] transition hover:bg-[var(--shell-surface-alt)]"
                        :aria-expanded="mobileMenuOpen"
                        :title="t('layout.sidebar.title')"
                        @click="toggleMobileMenu"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M4 7h16M4 12h16M4 17h16"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            />
                        </svg>
                    </button>

                    <Link href="/" class="app-interactive block">
                        <div
                            class="text-2xl font-extrabold tracking-[-0.04em] text-[var(--shell-accent)]"
                        >
                            Zertify
                        </div>
                    </Link>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="app-interactive flex h-9 w-9 items-center justify-center rounded-full border border-[var(--shell-border)] bg-[var(--shell-surface)] text-sm font-bold text-[var(--shell-text)] transition hover:bg-[var(--shell-surface-alt)]"
                        :title="`Thema: ${currentThemeLabel}`"
                        @click="cycleTheme"
                    >
                        <AppThemeIcon :mode="currentTheme" />
                    </button>
                    <button
                        type="button"
                        class="app-interactive flex h-9 w-9 items-center justify-center rounded-full border border-[var(--shell-border)] bg-[var(--shell-surface)] text-[12px] font-bold tracking-[0.08em] text-[var(--shell-text)] uppercase transition hover:bg-[var(--shell-surface-alt)]"
                        @click="cycleLocale"
                    >
                        {{ currentLanguageShort }}
                    </button>
                </div>
            </div>
        </header>

        <div
            v-if="mobileMenuOpen"
            class="fixed inset-0 z-[70] bg-[#0f172a]/28 backdrop-blur-[1px] md:hidden"
            @click="closeMobileMenu"
        >
            <aside
                class="absolute inset-y-0 left-0 flex w-[min(21rem,88vw)] flex-col border-r border-[var(--shell-border)] bg-[var(--shell-surface)] px-4 pt-[max(env(safe-area-inset-top),1rem)] pb-6 shadow-[0_20px_48px_rgba(20,20,35,0.18)]"
                @click.stop
            >
                <div class="flex items-center justify-between">
                    <Link
                        href="/"
                        class="app-interactive block"
                        @click="closeMobileMenu"
                    >
                        <div
                            class="text-2xl font-extrabold tracking-[-0.04em] text-[var(--shell-accent)]"
                        >
                            Zertify
                        </div>
                        <div
                            class="mt-1 text-[0.65rem] font-bold tracking-[0.22em] text-[var(--shell-muted)] uppercase"
                        >
                            zertify.app
                        </div>
                    </Link>

                    <button
                        type="button"
                        class="app-interactive flex h-10 w-10 items-center justify-center rounded-full border border-[var(--shell-border)] bg-[var(--shell-surface)] text-[var(--shell-text)] transition hover:bg-[var(--shell-surface-alt)]"
                        @click="closeMobileMenu"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M6 6l12 12M18 6 6 18"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            />
                        </svg>
                    </button>
                </div>

                <nav class="mt-8 space-y-2">
                    <Link
                        v-for="item in sideItems"
                        :key="`mobile-${item.key}`"
                        :href="item.href"
                        class="app-interactive flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold transition"
                        :class="
                            item.href === currentPath
                                ? 'bg-[var(--shell-accent-soft)] text-[var(--shell-accent)]'
                                : 'text-[var(--shell-muted)] hover:bg-[var(--shell-surface-alt)] hover:text-[var(--shell-text)]'
                        "
                        @click="closeMobileMenu"
                    >
                        <span class="flex h-6 w-6 items-center justify-center">
                            <svg
                                v-if="item.icon === 'dashboard'"
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    d="M3 3h8v8H3V3Zm10 0h8v8h-8V3ZM3 13h8v8H3v-8Zm10 0h8v8h-8v-8Z"
                                />
                            </svg>
                            <svg
                                v-else
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path
                                    d="M4 6.5A2.5 2.5 0 0 1 6.5 4H20v13.5A2.5 2.5 0 0 0 17.5 15H4V6.5Z"
                                />
                                <path
                                    d="M20 17.5A2.5 2.5 0 0 1 17.5 20H6.5A2.5 2.5 0 0 1 4 17.5V15h13.5A2.5 2.5 0 0 1 20 17.5Z"
                                />
                            </svg>
                        </span>
                        <span>{{ item.label }}</span>
                    </Link>

                    <button
                        type="button"
                        class="app-interactive flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold text-[var(--shell-muted)] transition hover:bg-[var(--shell-surface-alt)] hover:text-[var(--shell-text)]"
                        @click="
                            closeMobileMenu();
                            toggleMobileSettings();
                        "
                    >
                        <span class="flex h-6 w-6 items-center justify-center">
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M4 6h16M4 12h16M4 18h16" />
                                <circle
                                    cx="8"
                                    cy="6"
                                    r="2"
                                    fill="currentColor"
                                    stroke="none"
                                />
                                <circle
                                    cx="15"
                                    cy="12"
                                    r="2"
                                    fill="currentColor"
                                    stroke="none"
                                />
                                <circle
                                    cx="11"
                                    cy="18"
                                    r="2"
                                    fill="currentColor"
                                    stroke="none"
                                />
                            </svg>
                        </span>
                        <span>{{ t('layout.sidebar.settings') }}</span>
                    </button>
                </nav>

                <div class="mt-auto space-y-3">
                    <Link
                        :href="primaryActionHref"
                        class="app-interactive block w-full rounded-2xl bg-[var(--shell-secondary)] px-4 py-3 text-center font-bold text-white shadow-[0_10px_24px_rgba(140,74,0,0.16)]"
                        @click="closeMobileMenu"
                    >
                        {{ primaryActionLabel }}
                    </Link>

                    <div
                        class="rounded-2xl border border-[var(--shell-border)] bg-[var(--shell-surface-muted)] px-4 py-3"
                    >
                        <div
                            class="truncate text-sm font-semibold text-[var(--shell-text)]"
                        >
                            {{ quickMenuPrimaryLabel }}
                        </div>
                        <div
                            v-if="quickMenuSecondaryLabel"
                            class="mt-1 truncate text-xs font-medium text-[var(--shell-muted)]"
                        >
                            {{ quickMenuSecondaryLabel }}
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <header
            class="fixed inset-x-0 top-0 z-50 hidden border-b border-[var(--shell-border)] bg-[var(--shell-surface)]/96 backdrop-blur md:block xl:hidden"
        >
            <div
                class="mx-auto flex max-w-[1440px] items-center justify-between px-6 py-3"
            >
                <Link href="/" class="app-interactive block">
                    <div
                        class="text-2xl font-extrabold tracking-[-0.04em] text-[var(--shell-accent)]"
                    >
                        Zertify
                    </div>
                    <div
                        class="mt-1 text-[0.65rem] font-bold tracking-[0.22em] text-[var(--shell-muted)] uppercase"
                    >
                        zertify.app
                    </div>
                </Link>
                <nav class="flex items-center gap-5">
                    <a
                        v-for="item in navItems"
                        :key="`tablet-${item.key}`"
                        href="#"
                        class="text-sm font-bold transition"
                        :class="
                            item.key === 'ai'
                                ? 'border-b-2 border-[var(--shell-accent)] pb-1 text-[var(--shell-accent)]'
                                : 'text-[var(--shell-muted)] hover:text-[var(--shell-text)]'
                        "
                    >
                        {{ item.label }}
                    </a>
                </nav>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="app-interactive flex h-9 w-9 items-center justify-center rounded-full border border-[var(--shell-border)] bg-[var(--shell-surface)] text-sm font-bold text-[var(--shell-text)] transition hover:bg-[var(--shell-surface-alt)]"
                        :title="`Thema: ${currentThemeLabel}`"
                        @click="cycleTheme"
                    >
                        <AppThemeIcon :mode="currentTheme" />
                    </button>
                    <button
                        type="button"
                        class="app-interactive flex h-9 w-9 items-center justify-center rounded-full border border-[var(--shell-border)] bg-[var(--shell-surface)] text-[12px] font-bold tracking-[0.08em] text-[var(--shell-text)] uppercase transition hover:bg-[var(--shell-surface-alt)]"
                        @click="cycleLocale"
                    >
                        {{ currentLanguageShort }}
                    </button>
                    <Link
                        :href="secondaryActionHref"
                        class="rounded-sm px-3 py-2 text-sm font-bold text-[var(--shell-accent)] transition hover:bg-[var(--shell-surface-alt)]"
                    >
                        {{ secondaryActionLabel }}
                    </Link>
                    <div
                        class="grid h-9 w-9 place-items-center rounded-full bg-[var(--shell-secondary-soft)] text-[var(--shell-secondary-text)]"
                    >
                        ◎
                    </div>
                </div>
            </div>
        </header>

        <header
            class="sticky top-0 z-50 hidden border-b border-[var(--shell-border)] bg-[var(--shell-surface)]/92 backdrop-blur xl:block"
        >
            <div
                class="mx-auto flex max-w-[1440px] items-center justify-between px-6 py-5"
            >
                <Link href="/" class="app-interactive block">
                    <div
                        class="text-2xl font-extrabold tracking-[-0.04em] text-[var(--shell-accent)]"
                    >
                        Zertify
                    </div>
                    <div
                        class="mt-1 text-[0.65rem] font-bold tracking-[0.22em] text-[var(--shell-muted)] uppercase"
                    >
                        zertify.app
                    </div>
                </Link>
                <nav class="hidden items-center gap-10 md:flex">
                    <a
                        v-for="item in navItems"
                        :key="item.key"
                        href="#"
                        class="text-sm font-bold transition"
                        :class="
                            item.key === 'ai'
                                ? 'border-b-2 border-[var(--shell-accent)] pb-1 text-[var(--shell-accent)]'
                                : 'text-[var(--shell-muted)] hover:text-[var(--shell-text)]'
                        "
                    >
                        {{ item.label }}
                    </a>
                </nav>
                <div class="flex items-center gap-4">
                    <button
                        type="button"
                        class="app-interactive flex h-10 w-10 items-center justify-center rounded-full border border-[var(--shell-border)] bg-[var(--shell-surface)] text-sm font-bold text-[var(--shell-text)] transition hover:bg-[var(--shell-surface-alt)]"
                        :title="`Thema: ${currentThemeLabel}`"
                        @click="cycleTheme"
                    >
                        <AppThemeIcon :mode="currentTheme" />
                    </button>

                    <details ref="languageDetailsRef" class="relative">
                        <summary
                            class="app-interactive flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-full border border-[var(--shell-border)] bg-[var(--shell-surface)] text-[15px] font-bold text-[var(--shell-text)] transition hover:bg-[var(--shell-surface-alt)] [&::-webkit-details-marker]:hidden"
                        >
                            <span>{{ currentLanguageShort }}</span>
                        </summary>
                        <div
                            class="absolute z-20 mt-2 w-44 rounded-md border border-[var(--shell-border)] bg-[var(--shell-surface)] p-2 shadow-lg"
                            :class="languageMenuAlignmentClass"
                        >
                            <button
                                v-for="option in localeOptions"
                                :key="option.code"
                                type="button"
                                class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-start text-sm font-medium transition hover:bg-[var(--shell-surface-alt)]"
                                :class="
                                    currentLocale === option.code
                                        ? 'bg-[var(--shell-surface-alt)] text-[var(--shell-accent)]'
                                        : 'text-[var(--shell-muted)]'
                                "
                                @click="setLanguage(option.code)"
                            >
                                <span>{{ option.label }}</span>
                                <span
                                    class="text-xs font-bold tracking-[0.14em] uppercase"
                                    >{{ option.short }}</span
                                >
                            </button>
                        </div>
                    </details>
                    <Link
                        :href="secondaryActionHref"
                        class="rounded-lg px-5 py-2 font-bold text-[var(--shell-accent)] transition hover:bg-[var(--shell-surface-alt)]"
                    >
                        {{ secondaryActionLabel }}
                    </Link>
                    <div
                        class="grid h-10 w-10 place-items-center rounded-full bg-[var(--shell-secondary-soft)] text-[var(--shell-secondary-text)]"
                    >
                        ◎
                    </div>
                </div>
            </div>
        </header>

        <div class="flex xl:min-h-0 xl:flex-1">
            <aside
                class="relative hidden shrink-0 flex-col self-stretch border-r border-[var(--shell-border)] bg-[var(--shell-surface-muted)] transition-[width] duration-200 xl:flex"
                :class="
                    sidebarOpen
                        ? 'app-sidebar-shell--open'
                        : 'app-sidebar-shell--collapsed'
                "
            >
                <div
                    class="relative flex flex-col xl:sticky xl:top-[var(--desktop-shell-header)] xl:h-[calc(100dvh-var(--desktop-shell-header))]"
                >
                    <div
                        class="bg-[var(--shell-surface-muted)] py-6 transition-[padding] duration-200"
                        :class="sidebarOpen ? 'px-4' : 'px-3'"
                    >
                        <div
                            v-if="sidebarOpen"
                            class="text-3xl font-bold text-[var(--shell-accent)]"
                        >
                            {{ t('layout.sidebar.title') }}
                        </div>
                        <button
                            type="button"
                            class="absolute top-8 z-20 inline-flex h-8 w-8 items-center justify-center rounded-sm border border-[var(--shell-border)] bg-[var(--shell-surface)] text-[var(--shell-muted)] transition hover:bg-[var(--shell-surface-alt)]"
                            :class="sidebarToggleEdgeClass"
                            @click="toggleSidebar"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    :d="sidebarTogglePath"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </button>
                    </div>
                    <nav
                        class="mt-2 min-h-0 flex-1 space-y-2 overflow-y-auto px-4 pb-4 transition-[padding] duration-200 xl:pb-44"
                        :class="sidebarOpen ? 'px-4' : 'px-3'"
                    >
                        <a
                            v-for="(item, index) in sideItems"
                            :key="item.key"
                            :href="item.href"
                            class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition"
                            :class="[
                                sidebarOpen
                                    ? 'justify-start'
                                    : 'justify-center px-0',
                                index === 0
                                    ? 'bg-[var(--shell-surface)] text-[var(--shell-accent)] shadow-[0_8px_20px_rgba(37,47,61,0.05)]'
                                    : 'text-[var(--shell-muted)] hover:bg-[var(--shell-surface-alt)]',
                            ]"
                            :title="item.label"
                        >
                            <span
                                class="flex h-6 w-6 items-center justify-center"
                            >
                                <svg
                                    v-if="item.icon === 'dashboard'"
                                    class="h-6 w-6"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M3 3h8v8H3V3Zm10 0h8v8h-8V3ZM3 13h8v8H3v-8Zm10 0h8v8h-8v-8Z"
                                    />
                                </svg>
                                <svg
                                    v-else-if="item.icon === 'courses'"
                                    class="h-6 w-6"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M4 6.5A2.5 2.5 0 0 1 6.5 4H20v13.5A2.5 2.5 0 0 0 17.5 15H4V6.5Z"
                                    />
                                    <path
                                        d="M20 17.5A2.5 2.5 0 0 1 17.5 20H6.5A2.5 2.5 0 0 1 4 17.5V15h13.5A2.5 2.5 0 0 1 20 17.5Z"
                                    />
                                </svg>
                                <svg
                                    v-else-if="item.icon === 'vocab'"
                                    class="h-6 w-6"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <circle cx="12" cy="12" r="8" />
                                    <path
                                        d="M4 12h16M12 4a14 14 0 0 1 0 16M12 4a14 14 0 0 0 0 16"
                                    />
                                </svg>
                                <svg
                                    v-else-if="item.icon === 'ai-chat'"
                                    class="h-6 w-6"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="m12 3 1.6 3.2L17 7.8l-3.4 1.6L12 12.8l-1.6-3.4L7 7.8l3.4-1.6L12 3Z"
                                    />
                                    <path
                                        d="m5 13 1 2 2 1-2 1-1 2-1-2-2-1 2-1 1-2Z"
                                    />
                                    <path
                                        d="m19 13 .8 1.6L21.4 15l-1.6.8L19 17.4l-.8-1.6L16.6 15l1.6-.8L19 13Z"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    class="h-6 w-6"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="M4 6h16M4 12h16M4 18h16" />
                                    <circle
                                        cx="8"
                                        cy="6"
                                        r="2"
                                        fill="currentColor"
                                        stroke="none"
                                    />
                                    <circle
                                        cx="15"
                                        cy="12"
                                        r="2"
                                        fill="currentColor"
                                        stroke="none"
                                    />
                                    <circle
                                        cx="11"
                                        cy="18"
                                        r="2"
                                        fill="currentColor"
                                        stroke="none"
                                    />
                                </svg>
                            </span>
                            <span v-if="sidebarOpen">{{ item.label }}</span>
                        </a>
                    </nav>
                    <div
                        class="mt-auto border-t border-[var(--shell-border)] bg-[var(--shell-surface-muted)] pt-6 pb-6 transition-[padding] duration-200 xl:fixed xl:left-0 xl:z-30 xl:border-r"
                        :class="sidebarOpen ? 'px-4' : 'px-3'"
                        :style="desktopQuickDockStyle"
                    >
                        <Link
                            :href="primaryActionHref"
                            class="app-interactive mb-4 block w-full rounded-xl bg-[var(--shell-secondary)] px-4 py-3 text-center font-bold text-white shadow-[0_10px_24px_rgba(140,74,0,0.16)]"
                            :class="sidebarOpen ? '' : 'text-sm'"
                        >
                            {{
                                sidebarOpen
                                    ? primaryActionLabel
                                    : isRtl
                                      ? 'اذهب'
                                      : 'Go'
                            }}
                        </Link>
                        <details
                            ref="quickMenuDetailsRef"
                            class="relative block"
                        >
                            <summary
                                class="app-interactive flex w-full cursor-pointer list-none items-center justify-center rounded-xl border border-[var(--shell-border)] bg-[var(--shell-surface)] px-4 py-3 text-sm font-semibold text-[var(--shell-text)] hover:bg-[var(--shell-surface-alt)] [&::-webkit-details-marker]:hidden"
                                :class="
                                    sidebarOpen
                                        ? 'gap-3'
                                        : 'gap-0 px-0 text-center'
                                "
                            >
                                <svg
                                    class="h-5 w-5 shrink-0"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="M4 6h16M4 12h16M4 18h16" />
                                    <circle
                                        cx="8"
                                        cy="6"
                                        r="2"
                                        fill="currentColor"
                                        stroke="none"
                                    />
                                    <circle
                                        cx="15"
                                        cy="12"
                                        r="2"
                                        fill="currentColor"
                                        stroke="none"
                                    />
                                    <circle
                                        cx="11"
                                        cy="18"
                                        r="2"
                                        fill="currentColor"
                                        stroke="none"
                                    />
                                </svg>
                                <span
                                    v-if="sidebarOpen"
                                    class="flex min-w-0 flex-1 flex-col text-start leading-tight"
                                >
                                    <span
                                        class="truncate text-sm font-semibold text-[var(--shell-text)]"
                                        >{{ quickMenuPrimaryLabel }}</span
                                    >
                                    <span
                                        v-if="quickMenuSecondaryLabel"
                                        class="truncate text-xs font-medium text-[var(--shell-muted)]"
                                        >{{ quickMenuSecondaryLabel }}</span
                                    >
                                </span>
                            </summary>
                            <div
                                class="app-popover absolute bottom-full z-30 mb-2 min-w-[12rem] space-y-1 rounded-md border border-[var(--shell-border)] bg-[var(--shell-surface)] p-2 shadow-lg"
                                :class="[
                                    sidebarOpen ? 'w-full' : 'w-[13rem]',
                                    quickMenuAlignmentClass,
                                ]"
                            >
                                <Link
                                    v-if="isAuthenticated"
                                    href="/dashboard"
                                    class="app-interactive block rounded-sm px-3 py-2 text-sm text-[var(--shell-muted)] hover:bg-[var(--shell-surface-alt)]"
                                    @click="closeQuickMenu"
                                >
                                    {{ t('layout.quick.open_dashboard') }}
                                </Link>
                                <Link
                                    v-if="isAuthenticated"
                                    href="/dashboard"
                                    class="app-interactive block rounded-sm px-3 py-2 text-sm text-[var(--shell-muted)] hover:bg-[var(--shell-surface-alt)]"
                                    @click="closeQuickMenu"
                                >
                                    {{ t('layout.quick.profile') }}
                                </Link>
                                <Link
                                    v-if="!isAuthenticated"
                                    href="/login"
                                    class="app-interactive block rounded-sm px-3 py-2 text-sm text-[var(--shell-muted)] hover:bg-[var(--shell-surface-alt)]"
                                    @click="closeQuickMenu"
                                >
                                    {{ t('layout.quick.login') }}
                                </Link>
                                <Link
                                    v-if="!isAuthenticated"
                                    href="/register"
                                    class="app-interactive block rounded-sm px-3 py-2 text-sm text-[var(--shell-muted)] hover:bg-[var(--shell-surface-alt)]"
                                    @click="closeQuickMenu"
                                >
                                    {{ t('layout.quick.register') }}
                                </Link>
                                <button
                                    type="button"
                                    class="app-interactive block w-full rounded-sm px-3 py-2 text-start text-sm text-[var(--shell-muted)] hover:bg-[var(--shell-surface-alt)]"
                                    @click="closeQuickMenu"
                                >
                                    {{ t('layout.quick.help') }}
                                </button>
                                <Link
                                    v-if="isAuthenticated"
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    class="app-interactive block w-full rounded-sm px-3 py-2 text-start text-sm font-semibold text-[#b42318] hover:bg-[var(--shell-highlight)]"
                                    @click="closeQuickMenu"
                                >
                                    {{ t('layout.quick.logout') }}
                                </Link>
                                <div
                                    v-if="canUseTypeLab"
                                    class="mt-2 border-t border-[var(--shell-border)] pt-3"
                                >
                                    <div class="flex items-start justify-between gap-3 px-1">
                                        <div>
                                            <div class="app-label">Type Lab</div>
                                            <p class="mt-1 text-xs leading-5 text-[var(--shell-muted)]">
                                                Compare whole-app typography presets.
                                            </p>
                                        </div>
                                        <span class="rounded-full bg-[var(--shell-surface-alt)] px-2.5 py-1 text-[0.65rem] font-bold tracking-[0.08em] text-[var(--shell-accent)] uppercase">
                                            {{ currentTypographyLabel }}
                                        </span>
                                    </div>
                                    <div class="mt-3 grid grid-cols-2 gap-2">
                                        <button
                                            v-for="preset in typographyPresets"
                                            :key="`desktop-type-${preset.value}`"
                                            type="button"
                                            class="app-typography-pill px-3 py-2 text-left"
                                            :class="preset.value === currentTypographyPreset ? 'app-typography-pill--active' : ''"
                                            :title="`${preset.body} body, ${preset.display} headings`"
                                            @click="setTypographyPreset(preset.value)"
                                        >
                                            {{ preset.label }}
                                        </button>
                                    </div>
                                    <div class="mt-3 rounded-xl bg-[var(--shell-surface-alt)] px-3 py-3">
                                        <div class="text-[0.68rem] font-black tracking-[0.12em] text-[var(--shell-muted)] uppercase">
                                            Body
                                        </div>
                                        <div class="mt-1 text-sm font-semibold text-[var(--shell-text)]">
                                            {{ currentTypographyPresetMeta.body }}
                                        </div>
                                        <div class="mt-2 text-[0.68rem] font-black tracking-[0.12em] text-[var(--shell-muted)] uppercase">
                                            Headings
                                        </div>
                                        <div class="mt-1 text-sm font-semibold text-[var(--shell-text)]">
                                            {{ currentTypographyPresetMeta.display }}
                                        </div>
                                        <p class="mt-3 text-xs leading-5 text-[var(--shell-muted)]">
                                            {{ currentTypographyPresetMeta.note }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>
            </aside>

            <main
                class="flex-1 overflow-x-hidden bg-[var(--shell-bg)] px-4 pt-[calc(max(env(safe-area-inset-top),0.75rem)+3.75rem)] pb-[calc(1.5rem+env(safe-area-inset-bottom))] sm:px-6 md:px-8 md:pt-24 md:pb-8 xl:px-10 xl:py-8 2xl:px-14"
            >
                <div class="mx-auto max-w-[1320px]">
                    <slot />
                </div>
            </main>
        </div>

        <footer
            ref="footerRef"
            class="hidden border-t border-[var(--shell-border)] bg-[var(--shell-surface-muted)] md:block"
        >
            <div
                class="mx-auto flex max-w-[1440px] flex-col gap-8 px-8 py-12 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <Link
                        href="/"
                        class="app-interactive block text-2xl font-black tracking-[-0.04em] text-[var(--shell-accent)]"
                        >Zertify</Link
                    >
                    <p
                        class="mt-3 max-w-xs text-sm leading-7 text-[var(--shell-muted)]"
                    >
                        {{ t('layout.footer.tagline') }}
                    </p>
                </div>
                <div
                    class="flex flex-wrap gap-8 text-xs font-medium tracking-[0.18em] text-[var(--shell-muted)] uppercase"
                >
                    <a href="#">{{ t('layout.footer.privacy') }}</a>
                    <a href="#">{{ t('layout.footer.imprint') }}</a>
                    <a href="#">{{ t('layout.footer.contact') }}</a>
                    <a href="#">{{ t('layout.footer.blog') }}</a>
                </div>
                <div
                    class="text-xs tracking-[0.18em] text-[var(--shell-muted)] uppercase"
                >
                    zertify.app
                </div>
            </div>
        </footer>

        <div
            v-if="mobileSettingsOpen"
            class="fixed inset-0 z-[60] bg-[#0f172a]/28 backdrop-blur-[1px] md:hidden"
            @click="closeMobileSettings"
        >
            <div
                id="mobile-settings-panel"
                class="absolute inset-x-4 rounded-[1.5rem] border border-[var(--shell-border)] bg-[var(--shell-surface)] p-3 shadow-[0_20px_48px_rgba(20,20,35,0.18)]"
                :class="
                    isModulePage
                        ? 'bottom-[calc(4.75rem+env(safe-area-inset-bottom))]'
                        : 'bottom-[calc(5.5rem+env(safe-area-inset-bottom))]'
                "
                @click.stop
            >
                <div class="space-y-1">
                    <Link
                        v-if="isAuthenticated"
                        href="/dashboard"
                        class="app-interactive block rounded-xl px-4 py-3 text-sm font-semibold text-[var(--shell-muted)] hover:bg-[var(--shell-surface-alt)]"
                        @click="closeMobileSettings"
                    >
                        {{ t('layout.quick.open_dashboard') }}
                    </Link>
                    <Link
                        v-if="isAuthenticated"
                        href="/dashboard"
                        class="app-interactive block rounded-xl px-4 py-3 text-sm font-semibold text-[var(--shell-muted)] hover:bg-[var(--shell-surface-alt)]"
                        @click="closeMobileSettings"
                    >
                        {{ t('layout.quick.profile') }}
                    </Link>
                    <Link
                        v-if="!isAuthenticated"
                        href="/login"
                        class="app-interactive block rounded-xl px-4 py-3 text-sm font-semibold text-[var(--shell-muted)] hover:bg-[var(--shell-surface-alt)]"
                        @click="closeMobileSettings"
                    >
                        {{ t('layout.quick.login') }}
                    </Link>
                    <Link
                        v-if="!isAuthenticated"
                        href="/register"
                        class="app-interactive block rounded-xl px-4 py-3 text-sm font-semibold text-[var(--shell-muted)] hover:bg-[var(--shell-surface-alt)]"
                        @click="closeMobileSettings"
                    >
                        {{ t('layout.quick.register') }}
                    </Link>
                    <button
                        type="button"
                        class="app-interactive block w-full rounded-xl px-4 py-3 text-start text-sm font-semibold text-[var(--shell-muted)] hover:bg-[var(--shell-surface-alt)]"
                        @click="closeMobileSettings"
                    >
                        {{ t('layout.quick.help') }}
                    </button>
                    <Link
                        v-if="isAuthenticated"
                        href="/logout"
                        method="post"
                        as="button"
                        class="app-interactive block w-full rounded-xl px-4 py-3 text-start text-sm font-semibold text-[#b42318] hover:bg-[var(--shell-highlight)]"
                        @click="closeMobileSettings"
                    >
                        {{ t('layout.quick.logout') }}
                    </Link>
                </div>
                <div
                    v-if="canUseTypeLab"
                    class="mt-3 border-t border-[var(--shell-border)] pt-3"
                >
                    <div class="flex items-start justify-between gap-3 px-1">
                        <div>
                            <div class="app-label">Type Lab</div>
                            <p class="mt-1 text-xs leading-5 text-[var(--shell-muted)]">
                                Compare whole-app typography presets.
                            </p>
                        </div>
                        <span class="rounded-full bg-[var(--shell-surface-alt)] px-2.5 py-1 text-[0.65rem] font-bold tracking-[0.08em] text-[var(--shell-accent)] uppercase">
                            {{ currentTypographyLabel }}
                        </span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <button
                            v-for="preset in typographyPresets"
                            :key="`mobile-type-${preset.value}`"
                            type="button"
                            class="app-typography-pill px-3 py-2 text-left"
                            :class="preset.value === currentTypographyPreset ? 'app-typography-pill--active' : ''"
                            :title="`${preset.body} body, ${preset.display} headings`"
                            @click="setTypographyPreset(preset.value)"
                        >
                            {{ preset.label }}
                        </button>
                    </div>
                    <div class="mt-3 rounded-xl bg-[var(--shell-surface-alt)] px-3 py-3">
                        <div class="text-[0.68rem] font-black tracking-[0.12em] text-[var(--shell-muted)] uppercase">
                            Body
                        </div>
                        <div class="mt-1 text-sm font-semibold text-[var(--shell-text)]">
                            {{ currentTypographyPresetMeta.body }}
                        </div>
                        <div class="mt-2 text-[0.68rem] font-black tracking-[0.12em] text-[var(--shell-muted)] uppercase">
                            Headings
                        </div>
                        <div class="mt-1 text-sm font-semibold text-[var(--shell-text)]">
                            {{ currentTypographyPresetMeta.display }}
                        </div>
                        <p class="mt-3 text-xs leading-5 text-[var(--shell-muted)]">
                            {{ currentTypographyPresetMeta.note }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
