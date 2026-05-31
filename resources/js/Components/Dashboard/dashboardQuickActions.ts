import type {
    AppBottomAction,
    AppBottomActionTone,
} from '@/Components/App/appBottomActionSheet';

export type DashboardModuleQuickAction = {
    key: string;
    title: string;
    body: string;
    href?: string;
    icon: string;
};

export type DashboardQuickAction = AppBottomAction;

type BuildDashboardQuickActionsInput = {
    activeModule: DashboardModuleQuickAction | null;
    currentModules: DashboardModuleQuickAction[];
    aiTitle: string;
    aiBody: string;
    aiCta: string;
    communityTitle: string;
    communityBody: string;
    communityCta: string;
};

export const buildDashboardQuickActions = ({
    activeModule,
    currentModules,
    aiTitle,
    aiBody,
    aiCta,
    communityTitle,
    communityBody,
    communityCta,
}: BuildDashboardQuickActionsInput): DashboardQuickAction[] => {
    const actions: DashboardQuickAction[] = [];

    if (activeModule) {
        actions.push({
            key: `module:${activeModule.key}`,
            title: activeModule.title,
            body: activeModule.body,
            href: activeModule.href,
            icon: activeModule.icon,
            tone: 'accent' satisfies AppBottomActionTone,
        });
    }

    const suggestedModule = currentModules.find(
        (module) => module.href && module.key !== activeModule?.key,
    );

    if (suggestedModule) {
        actions.push({
            key: `module:${suggestedModule.key}`,
            title: suggestedModule.title,
            body: suggestedModule.body,
            href: suggestedModule.href,
            icon: suggestedModule.icon,
            tone: (activeModule
                ? 'secondary'
                : 'accent') satisfies AppBottomActionTone,
        });
    }

    actions.push({
        key: 'practice',
        title: aiCta,
        body: aiBody,
        icon: '✦',
        tone: (actions.length === 0
            ? 'accent'
            : 'secondary') satisfies AppBottomActionTone,
    });

    actions.push({
        key: 'community',
        title: communityCta,
        body: communityTitle || communityBody,
        icon: '◎',
        tone: 'neutral' satisfies AppBottomActionTone,
    });

    return actions.slice(0, 4);
};
