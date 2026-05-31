export type PublicLocale = 'de' | 'en' | 'uk' | 'ru' | 'tr' | 'ar';

export const STORAGE_KEY = 'zertify-language';

export const localeOptions: Array<{
    code: PublicLocale;
    label: string;
    short: string;
}> = [
    { code: 'de', label: 'Deutsch', short: 'DE' },
    { code: 'en', label: 'English', short: 'EN' },
    { code: 'uk', label: 'Українська', short: 'UK' },
    { code: 'ru', label: 'Русский', short: 'RU' },
    { code: 'tr', label: 'Türkçe', short: 'TR' },
    { code: 'ar', label: 'العربية', short: 'AR' },
];

export const DEFAULT_PUBLIC_LOCALE: PublicLocale = 'en';

const supportedLocales = new Set<PublicLocale>(
    localeOptions.map((option) => option.code),
);

const localeAliases: Record<string, PublicLocale> = {
    ua: 'uk',
};

export const isSupportedPublicLocale = (
    value: string | null | undefined,
): value is PublicLocale => {
    if (!value) {
        return false;
    }

    return supportedLocales.has(value as PublicLocale);
};

export const normalizePublicLocale = (
    value: string | null | undefined,
): PublicLocale | null => {
    if (!value) {
        return null;
    }

    const normalized = value.toLowerCase().replace('_', '-');
    const [baseLocale] = normalized.split('-');

    if (!baseLocale) {
        return null;
    }

    const resolvedLocale = localeAliases[baseLocale] ?? baseLocale;

    return isSupportedPublicLocale(resolvedLocale) ? resolvedLocale : null;
};

export const detectPreferredPublicLocale = (
    candidates: Array<string | null | undefined>,
): PublicLocale => {
    for (const candidate of candidates) {
        const locale = normalizePublicLocale(candidate);

        if (locale) {
            return locale;
        }
    }

    return DEFAULT_PUBLIC_LOCALE;
};
