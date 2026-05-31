import assert from 'node:assert/strict';
import test from 'node:test';

import { buildDashboardQuickActions } from '../../resources/js/Components/Dashboard/dashboardQuickActions.ts';

test('buildDashboardQuickActions prioritizes the active module and keeps helper actions', () => {
    const actions = buildDashboardQuickActions({
        activeModule: {
            key: 'lesen',
            title: 'Lesen',
            body: 'Focused reading practice.',
            href: '/modules/lesen',
            icon: '▤',
        },
        currentModules: [
            {
                key: 'lesen',
                title: 'Lesen',
                body: 'Focused reading practice.',
                href: '/modules/lesen',
                icon: '▤',
            },
            {
                key: 'hoeren',
                title: 'Hören',
                body: 'Listening drills.',
                href: '/modules/hoeren',
                icon: '◖',
            },
        ],
        aiTitle: 'AI Practice Companion',
        aiBody: 'Prepare your next focused simulation.',
        aiCta: 'Start simulation',
        communityTitle: 'Study with peers',
        communityBody: 'Find your next review circle.',
        communityCta: 'Open community',
    });

    assert.equal(actions[0]?.key, 'module:lesen');
    assert.equal(actions[1]?.key, 'module:hoeren');
    assert.equal(actions[2]?.key, 'practice');
    assert.equal(actions[3]?.key, 'community');
});

test('buildDashboardQuickActions falls back to helper actions without navigable modules', () => {
    const actions = buildDashboardQuickActions({
        activeModule: null,
        currentModules: [
            {
                key: 'sprach',
                title: 'Sprachbausteine',
                body: 'Grammar and register.',
                icon: '✦',
            },
        ],
        aiTitle: 'AI Practice Companion',
        aiBody: 'Prepare your next focused simulation.',
        aiCta: 'Start simulation',
        communityTitle: 'Study with peers',
        communityBody: 'Find your next review circle.',
        communityCta: 'Open community',
    });

    assert.deepEqual(
        actions.map((action) => action.key),
        ['practice', 'community'],
    );
    assert.equal(actions[0]?.tone, 'accent');
});
