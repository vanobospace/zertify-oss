export type AppBottomActionTone = 'accent' | 'secondary' | 'neutral';

export type AppBottomAction = {
    key: string;
    title: string;
    body: string;
    href?: string;
    icon: string;
    tone: AppBottomActionTone;
};
