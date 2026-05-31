import assert from 'node:assert/strict';
import test from 'node:test';

import {
    DEFAULT_PUBLIC_LOCALE,
    detectPreferredPublicLocale,
    normalizePublicLocale,
} from '../../resources/js/composables/publicLocale.ts';

test('normalizePublicLocale resolves supported locales and aliases', () => {
    assert.equal(normalizePublicLocale('ru-RU'), 'ru');
    assert.equal(normalizePublicLocale('uk_UA'), 'uk');
    assert.equal(normalizePublicLocale('ua'), 'uk');
    assert.equal(normalizePublicLocale('de'), 'de');
    assert.equal(normalizePublicLocale('fr-FR'), null);
});

test('detectPreferredPublicLocale returns the first supported browser locale', () => {
    assert.equal(
        detectPreferredPublicLocale(['fr-FR', 'tr-TR', 'en-GB']),
        'tr',
    );
    assert.equal(
        detectPreferredPublicLocale(['es-ES', 'ar-EG', 'en-US']),
        'ar',
    );
});

test('detectPreferredPublicLocale falls back to english when nothing matches', () => {
    assert.equal(
        detectPreferredPublicLocale(['es-ES', 'fr-FR', undefined, null]),
        DEFAULT_PUBLIC_LOCALE,
    );
});
