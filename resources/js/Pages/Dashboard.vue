<script setup lang="ts">
import AppBottomActionSheet from '@/Components/App/AppBottomActionSheet.vue';
import {
    buildDashboardQuickActions,
    type DashboardQuickAction,
} from '@/Components/Dashboard/dashboardQuickActions';
import PublicExamFormatCard from '@/Components/Public/PublicExamFormatCard.vue';
import PublicExamLevelCard from '@/Components/Public/PublicExamLevelCard.vue';
import PublicModuleProgressCard from '@/Components/Public/PublicModuleProgressCard.vue';
import PublicAppLayout from '@/Layouts/PublicAppLayout.vue';
import {
    type PublicLocale,
    usePublicLocale,
} from '@/composables/usePublicLocale';
import { Head, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { computed, onMounted, ref, watch } from 'vue';

defineOptions({
    layout: PublicAppLayout,
});

type LevelKey = 'B1' | 'B2' | 'C1';
type FormatKey = 'goethe' | 'telc' | 'beruf' | 'osd';
type ToneKey = 'primary' | 'secondary' | 'tertiary';

type ExamFormat = {
    key: FormatKey;
    category: string;
    title: string;
    body: string;
    icon: string;
    badge?: string;
};

type ModuleCard = {
    key: string;
    title: string;
    body: string;
    progressValue: number;
    progressLabel: string;
    ctaLabel: string;
    icon: string;
    order: string;
    tone: ToneKey;
    href?: string;
};

const page = usePage<{
    auth: {
        user: {
            name: string;
            email: string;
        } | null;
    };
}>();

const { t, tr, currentLocale } = usePublicLocale();
const DASHBOARD_SELECTION_KEY = 'zertify:dashboard-selection';

const userName = computed(
    () => page.props.auth.user?.name ?? t('dashboard.user_fallback'),
);
const welcomeTitle = computed(() =>
    tr('dashboard.welcome', { name: userName.value }),
);

const selectedLevel = ref<LevelKey | null>(null);
const selectedFormat = ref<FormatKey | null>(null);
const selectedModuleKey = ref<string | null>(null);
const quickActionsOpen = ref(false);

const pick = (variants: Partial<Record<PublicLocale, string>>): string => {
    return variants[currentLocale.value] ?? variants.de ?? variants.en ?? '';
};

const formatDisplayName = (format: FormatKey, level: LevelKey): string => {
    const labels: Record<FormatKey, string> = {
        goethe: `Goethe ${level}`,
        telc: `telc ${level} · Allgemein`,
        beruf: `${level} Beruf`,
        osd: `ÖSD ${level}`,
    };

    return labels[format];
};

const formatActiveBadge = computed(() =>
    pick({ de: 'Aktiv', en: 'Active', uk: 'Активно', ru: 'Активно' }),
);
const moduleCtaStart = computed(() =>
    pick({ de: 'Start', en: 'Start', uk: 'Старт', ru: 'Старт' }),
);
const moduleProgressLabel = (percent: number): string =>
    pick({
        de: `${percent}% abgeschlossen`,
        en: `${percent}% complete`,
        uk: `${percent}% завершено`,
        ru: `${percent}% завершено`,
    });

const dashboardCopy = computed(() => ({
    mobileKicker: pick({
        de: 'Willkommen',
        en: 'Welcome',
        uk: 'Ласкаво просимо',
        ru: 'Добро пожаловать',
    }),
    mobileStreakValue: '12',
    heroKicker: pick({
        de: 'KI-Empfehlung',
        en: 'AI Recommendation',
        uk: 'AI-рекомендація',
        ru: 'AI-рекомендация',
    }),
    heroTitle: pick({
        de: `Steigere deine akademische Sprachsicherheit auf ${selectedLevel.value ?? 'B2'}.`,
        en: `Elevate your academic linguistic mastery at ${selectedLevel.value ?? 'B2'}.`,
        uk: `Прокачай академічну мовну впевненість на рівні ${selectedLevel.value ?? 'B2'}.`,
        ru: `Прокачай академическую языковую уверенность на уровне ${selectedLevel.value ?? 'B2'}.`,
    }),
    heroBody: pick({
        de: `Dein AI-Tutor empfiehlt heute ${selectedLevel.value ?? 'B2'}-Module, um die nächste Prüfungsrunde gezielt vorzubereiten.`,
        en: `Your AI tutor recommends focused ${selectedLevel.value ?? 'B2'} modules today to prepare for the next exam cycle.`,
        uk: `AI-тьютор радить сьогодні сфокусуватися на модулях ${selectedLevel.value ?? 'B2'}, щоб підготуватися до наступного екзаменаційного циклу.`,
        ru: `AI-тьютор советует сегодня сфокусироваться на модулях ${selectedLevel.value ?? 'B2'}, чтобы подготовиться к следующему экзаменационному циклу.`,
    }),
    heroStreakTitle: pick({
        de: 'Daily Streak',
        en: 'Daily Streak',
        uk: 'Щоденна серія',
        ru: 'Дневная серия',
    }),
    heroStreakValue: '14',
    heroStreakSuffix: pick({ de: 'Tage', en: 'Days', uk: 'днів', ru: 'дней' }),
    levelsTitle: pick({
        de: 'Sprachniveau wählen',
        en: 'Choose language level',
        uk: 'Обери мовний рівень',
        ru: 'Выбери языковой уровень',
    }),
    levelsTitleDesktop: pick({
        de: 'Prüfungsniveau',
        en: 'Exam level',
        uk: 'Рівень іспиту',
        ru: 'Уровень экзамена',
    }),
    levelsMobileBadge: pick({
        de: 'Stufe',
        en: 'Level',
        uk: 'Рівень',
        ru: 'Уровень',
    }),
    formatsTitle: pick({
        de: 'Prüfungsformat',
        en: 'Exam format',
        uk: 'Формат іспиту',
        ru: 'Формат экзамена',
    }),
    formatsTitleDesktop: pick({
        de: 'Prüfungsformat wählen',
        en: 'Select exam format',
        uk: 'Оберіть формат іспиту',
        ru: 'Выберите формат экзамена',
    }),
    modulesHeading: pick({
        de: selectedLevel.value
            ? `${selectedLevel.value} Lernmodule`
            : 'Lernmodule',
        en: selectedLevel.value
            ? `${selectedLevel.value} mastery modules`
            : 'Mastery modules',
        uk: selectedLevel.value
            ? `${selectedLevel.value} модулі навчання`
            : 'Модулі навчання',
        ru: selectedLevel.value
            ? `${selectedLevel.value} учебные модули`
            : 'Учебные модули',
    }),
    modulesSubtitle: pick({
        de:
            selectedFormat.value && selectedLevel.value
                ? `Fokus auf Kernkompetenzen im Format ${formatDisplayName(selectedFormat.value, selectedLevel.value)}.`
                : 'Wähle zuerst Niveau und Prüfungsformat, dann aktiviere ein Modul.',
        en:
            selectedFormat.value && selectedLevel.value
                ? `Focused on core competencies for the ${formatDisplayName(selectedFormat.value, selectedLevel.value)} track.`
                : 'Choose a level and exam format first, then activate a module.',
        uk:
            selectedFormat.value && selectedLevel.value
                ? `Фокус на ключових компетенціях для формату ${formatDisplayName(selectedFormat.value, selectedLevel.value)}.`
                : 'Спершу обери рівень і формат іспиту, а потім активуй модуль.',
        ru:
            selectedFormat.value && selectedLevel.value
                ? `Фокус на ключевых компетенциях для формата ${formatDisplayName(selectedFormat.value, selectedLevel.value)}.`
                : 'Сначала выбери уровень и формат экзамена, а потом активируй модуль.',
    }),
    aiMobileTitle: pick({
        de: 'KI Mentor',
        en: 'AI Mentor',
        uk: 'AI Ментор',
        ru: 'AI Ментор',
    }),
    aiMobileBody: pick({
        de: `Dein ${selectedLevel.value}-Sprachgefühl hat sich um 15% verbessert. Willst du eine Simulation starten?`,
        en: `Your ${selectedLevel.value} language feel improved by 15%. Do you want to start a simulation?`,
        uk: `Твоє мовне чуття на ${selectedLevel.value} покращилося на 15%. Хочеш запустити симуляцію?`,
        ru: `Твоё языковое чутьё на ${selectedLevel.value} улучшилось на 15%. Хочешь запустить симуляцию?`,
    }),
    aiTitle: pick({
        de: 'AI Practice Companion',
        en: 'AI Practice Companion',
        uk: 'AI помічник практики',
        ru: 'AI помощник практики',
    }),
    aiBody: pick({
        de: `Simuliere eine echte Prüfungssituation mit neuralem Tutor und Echtzeit-Feedback für dein ${selectedLevel.value}-Sprachniveau.`,
        en: `Simulate a high-stakes exam environment with real-time AI feedback for your ${selectedLevel.value} skills.`,
        uk: `Симулюй реальну екзаменаційну ситуацію з AI-фідбеком у реальному часі для навичок ${selectedLevel.value}.`,
        ru: `Симулируй реальную экзаменационную ситуацию с AI-фидбеком в реальном времени для навыков ${selectedLevel.value}.`,
    }),
    aiCta: pick({
        de: 'Jetzt üben',
        en: 'Enter session',
        uk: 'Почати сесію',
        ru: 'Начать сессию',
    }),
    metricResultLabel: pick({
        de: 'Letztes Ergebnis',
        en: 'Latest result',
        uk: 'Останній результат',
        ru: 'Последний результат',
    }),
    metricResultBody: pick({
        de: 'Akademischer Essay C1',
        en: 'Academic essay C1',
        uk: 'Академічне есе C1',
        ru: 'Академическое эссе C1',
    }),
    metricTimeLabel: pick({
        de: 'Zeit investiert',
        en: 'Time spent',
        uk: 'Час інвестиції',
        ru: 'Потрачено времени',
    }),
    metricTimeBody: pick({
        de: 'Ziel dieser Woche',
        en: 'Current week goal',
        uk: 'Ціль цього тижня',
        ru: 'Цель этой недели',
    }),
    communityTitle: pick({
        de: 'Community Practice Session',
        en: 'Community Practice Session',
        uk: 'Сесія практики спільноти',
        ru: 'Сессия практики сообщества',
    }),
    communityBody: pick({
        de: `Schließe dich 14 weiteren ${selectedLevel.value}-Lernenden zu einer Peer-Review-Session in 20 Minuten an.`,
        en: `Join 14 other ${selectedLevel.value} learners for a peer review session starting in 20 minutes.`,
        uk: `Приєднуйся до 14 інших учнів ${selectedLevel.value} на peer-review сесію, що стартує за 20 хвилин.`,
        ru: `Присоединяйся к 14 другим ученикам ${selectedLevel.value} на peer-review сессию, которая начнётся через 20 минут.`,
    }),
    communityCta: pick({
        de: 'Jetzt beitreten',
        en: 'Join now',
        uk: 'Приєднатися',
        ru: 'Присоединиться',
    }),
}));

const examLevels = computed(() => [
    {
        key: 'B1' as const,
        eyebrow: pick({
            de: 'Intermediate',
            en: 'Intermediate',
            uk: 'Intermediate',
            ru: 'Intermediate',
        }),
        title: 'Level B1',
        progressLabel: pick({
            de: 'Abgeschlossen',
            en: 'Completed',
            uk: 'Завершено',
            ru: 'Завершено',
        }),
        progressValue: 100,
        numeral: '1',
        completed: true,
    },
    {
        key: 'B2' as const,
        eyebrow: pick({
            de: 'Upper Intermediate',
            en: 'Upper Intermediate',
            uk: 'Upper Intermediate',
            ru: 'Upper Intermediate',
        }),
        title: 'Level B2',
        progressLabel: '68% Progress',
        progressValue: 68,
        numeral: '2',
    },
    {
        key: 'C1' as const,
        eyebrow: pick({
            de: 'Advanced Mastery',
            en: 'Advanced Mastery',
            uk: 'Advanced Mastery',
            ru: 'Advanced Mastery',
        }),
        title: 'Level C1',
        progressLabel: '12% Progress',
        progressValue: 12,
        numeral: '3',
    },
]);

const examFormatsByLevel = computed<Record<LevelKey, ExamFormat[]>>(() => ({
    B1: [
        {
            key: 'goethe',
            category: 'Goethe',
            title: 'Goethe B1',
            body: pick({
                de: 'International anerkannte Prüfung für Studium und klassische Zertifizierung.',
                en: 'Internationally recognized exam for study and classic certification.',
                uk: 'Міжнародно визнаний іспит для навчання та класичної сертифікації.',
                ru: 'Международно признанный экзамен для учёбы и классической сертификации.',
            }),
            icon: '✺',
        },
        {
            key: 'telc',
            category: 'Allgemein',
            title: 'telc B1',
            body: pick({
                de: 'Das ist der telc-Weg für Alltag, Arbeit und Integration. Oft wird er einfach „Allgemein“ genannt.',
                en: 'This is the telc track for everyday life, work, and integration. It is often simply called “Allgemein”.',
                uk: 'Це telc-трек для побуту, роботи та інтеграції. Його часто просто називають “Allgemein”.',
                ru: 'Это telc-трек для быта, работы и интеграции. Его часто просто называют “Allgemein”.',
            }),
            icon: '◔',
        },
        {
            key: 'beruf',
            category: 'Beruf',
            title: 'B1 Beruf',
            body: pick({
                de: 'Berufliche Kommunikation, institutionelle Situationen und formelle Aufgaben.',
                en: 'Workplace communication, institutional situations, and formal tasks.',
                uk: 'Професійна комунікація, інституційні ситуації та формальні завдання.',
                ru: 'Профессиональная коммуникация, институциональные ситуации и формальные задания.',
            }),
            icon: '▣',
        },
        {
            key: 'osd',
            category: 'ÖSD',
            title: 'ÖSD B1',
            body: pick({
                de: 'Österreichischer Prüfungsweg mit Fokus auf modulare Prüfungsteile.',
                en: 'Austrian exam track with a focus on modular exam parts.',
                uk: 'Австрійський трек іспиту з фокусом на модульні частини.',
                ru: 'Австрийский экзаменационный трек с фокусом на модульные части.',
            }),
            icon: '◎',
        },
    ],
    B2: [
        {
            key: 'goethe',
            category: 'Goethe',
            title: 'Goethe B2',
            body: pick({
                de: 'International anerkannte B2-Prüfung mit eigenständigen Modulen Lesen, Hören, Schreiben und Sprechen.',
                en: 'Internationally recognized B2 exam with standalone reading, listening, writing, and speaking modules.',
                uk: 'Міжнародно визнаний B2-іспит з окремими модулями читання, аудіювання, письма та говоріння.',
                ru: 'Международно признанный экзамен B2 с отдельными модулями чтения, аудирования, письма и говорения.',
            }),
            icon: '✺',
        },
        {
            key: 'telc',
            category: 'Allgemein',
            title: 'telc B2',
            body: pick({
                de: 'Das ist telc B2: genau dieser Track wird sehr oft einfach nur „Allgemein“ genannt.',
                en: 'This is telc B2: this exact track is very often referred to simply as “Allgemein”.',
                uk: 'Це telc B2: саме цей трек дуже часто називають просто “Allgemein”.',
                ru: 'Это telc B2: именно этот трек очень часто называют просто “Allgemein”.',
            }),
            icon: '◔',
        },
        {
            key: 'beruf',
            category: 'Beruf',
            title: 'B2 Beruf',
            body: pick({
                de: 'Beruflich orientierter Prüfungsweg mit Arbeitswelt, institutionellen Aufgaben und formeller Sprache.',
                en: 'Career-oriented exam path focused on workplace tasks, institutions, and formal language.',
                uk: 'Професійно орієнтований трек з робочими ситуаціями, інституціями та формальною мовою.',
                ru: 'Профессионально ориентированный трек с рабочими ситуациями, институтами и формальной речью.',
            }),
            icon: '▣',
        },
        {
            key: 'osd',
            category: 'ÖSD',
            title: 'ÖSD B2',
            body: pick({
                de: 'Österreichischer B2-Track mit eigenem Prüfungsaufbau und modularem Fokus.',
                en: 'Austrian B2 track with its own exam structure and modular focus.',
                uk: 'Австрійський B2-трек із власною структурою іспиту та модульним фокусом.',
                ru: 'Австрийский B2-трек с собственной структурой экзамена и модульным фокусом.',
            }),
            icon: '◎',
        },
    ],
    C1: [
        {
            key: 'goethe',
            category: 'Goethe',
            title: 'Goethe C1',
            body: pick({
                de: 'Die klare Route für fortgeschrittene akademische Zertifizierung und modulare Leistung.',
                en: 'The clearest route for advanced academic certification and modular performance.',
                uk: 'Найчіткіший шлях до просунутої академічної сертифікації та модульної оцінки.',
                ru: 'Самый прямой путь к продвинутой академической сертификации и модульной оценке.',
            }),
            icon: '✺',
        },
        {
            key: 'telc',
            category: 'Allgemein',
            title: 'telc C1',
            body: pick({
                de: 'Der telc-C1-Weg für anspruchsvolle Texte, Hören und formelle Kommunikation. Auch hier sagen viele einfach „Allgemein“.',
                en: 'The telc C1 track for demanding texts, listening, and formal communication. Many still simply say “Allgemein”.',
                uk: 'Це telc C1 для складних текстів, аудіювання та формальної комунікації. Багато хто все одно каже просто “Allgemein”.',
                ru: 'Это telc C1 для сложных текстов, аудирования и формальной коммуникации. Многие всё равно говорят просто “Allgemein”.',
            }),
            icon: '◔',
        },
        {
            key: 'beruf',
            category: 'Beruf',
            title: 'C1 Beruf',
            body: pick({
                de: 'Fortgeschrittene Berufskommunikation, Berichte und institutionelle Genauigkeit.',
                en: 'Advanced workplace communication, reports, and institutional precision.',
                uk: 'Просунута професійна комунікація, звіти та інституційна точність.',
                ru: 'Продвинутая профессиональная коммуникация, отчёты и институциональная точность.',
            }),
            icon: '▣',
        },
        {
            key: 'osd',
            category: 'ÖSD',
            title: 'ÖSD C1',
            body: pick({
                de: 'Österreichischer C1-Track für präzise Sprachleistung und modulare Prüfungsteile.',
                en: 'Austrian C1 track for precise language performance and modular exam parts.',
                uk: 'Австрійський C1-трек для точної мовної продуктивності та модульних частин.',
                ru: 'Австрийский C1-трек для точной языковой продуктивности и модульных частей.',
            }),
            icon: '◎',
        },
    ],
}));

const modulesBySelection = computed<
    Record<LevelKey, Record<FormatKey, ModuleCard[]>>
>(() => ({
    B1: {
        goethe: [
            {
                key: 'hoeren',
                title: 'Hören',
                body: pick({
                    de: 'Langsame Hörtexte und klare Aufgabenlogik für sichere Antworten.',
                    en: 'Slower listening texts and clear task logic for reliable answers.',
                    uk: 'Повільніші аудіотексти й зрозуміла логіка завдань.',
                    ru: 'Более медленные аудиотексты и понятная логика заданий.',
                }),
                progressValue: 74,
                progressLabel: moduleProgressLabel(74),
                ctaLabel: moduleCtaStart.value,
                icon: '◖',
                order: '01',
                tone: 'primary',
            },
            {
                key: 'lesen',
                title: 'Lesen',
                body: pick({
                    de: 'Kurze Sachtexte, Hinweise und Fragen Schritt für Schritt entschlüsseln.',
                    en: 'Decode short factual texts, hints, and questions step by step.',
                    uk: 'Розбирай короткі тексти, підказки й питання крок за кроком.',
                    ru: 'Разбирай короткие тексты, подсказки и вопросы шаг за шагом.',
                }),
                progressValue: 58,
                progressLabel: moduleProgressLabel(58),
                ctaLabel: moduleCtaStart.value,
                icon: '▤',
                order: '02',
                tone: 'secondary',
            },
            {
                key: 'schreiben',
                title: 'Schreiben',
                body: pick({
                    de: 'Einfache Mitteilungen, Aufbau und klare Satzverbindungen trainieren.',
                    en: 'Practice simple writing, structure, and clear sentence links.',
                    uk: 'Тренуй прості тексти, структуру й чіткі зв’язки між реченнями.',
                    ru: 'Тренируй простые тексты, структуру и чёткие связи между предложениями.',
                }),
                progressValue: 33,
                progressLabel: moduleProgressLabel(33),
                ctaLabel: moduleCtaStart.value,
                icon: '✎',
                order: '03',
                tone: 'tertiary',
            },
        ],
        telc: [
            {
                key: 'lesen',
                title: 'Lesen',
                body: pick({
                    de: 'Allgemeiner telc-Lesetrack mit Zuordnung, Überblick und Prüfungstiming.',
                    en: 'General telc reading track with matching, overview, and exam timing.',
                    uk: 'Загальний telc-трек читання з матчингом, оглядом і таймінгом іспиту.',
                    ru: 'Общий telc-трек чтения с матчингом, обзором и таймингом экзамена.',
                }),
                progressValue: 52,
                progressLabel: moduleProgressLabel(52),
                ctaLabel: moduleCtaStart.value,
                icon: '▤',
                order: '01',
                tone: 'primary',
                href: '/modules/lesen',
            },
            {
                key: 'hoeren',
                title: 'Hören',
                body: pick({
                    de: 'Praktische Hörsituationen für Alltag, Behörde und Arbeit.',
                    en: 'Practical listening situations for everyday, work, and official contexts.',
                    uk: 'Практичні аудіоситуації для побуту, роботи й офіційних тем.',
                    ru: 'Практические аудиоситуации для быта, работы и официальных тем.',
                }),
                progressValue: 62,
                progressLabel: moduleProgressLabel(62),
                ctaLabel: moduleCtaStart.value,
                icon: '◖',
                order: '02',
                tone: 'secondary',
            },
            {
                key: 'sprach',
                title: 'Sprachbausteine',
                body: pick({
                    de: 'Formelhafte Strukturen und präzise Auswahl unter Zeitdruck.',
                    en: 'Formulaic structures and precise selection under time pressure.',
                    uk: 'Шаблонні структури й точний вибір під тиском часу.',
                    ru: 'Шаблонные структуры и точный выбор под давлением времени.',
                }),
                progressValue: 49,
                progressLabel: moduleProgressLabel(49),
                ctaLabel: moduleCtaStart.value,
                icon: '✦',
                order: '03',
                tone: 'tertiary',
            },
        ],
        beruf: [
            {
                key: 'lesen',
                title: 'Lesen',
                body: pick({
                    de: 'Arbeitswelt, institutionelle Hinweise und berufsnahe Szenarien gezielt lesen.',
                    en: 'Read workplace, institutional, and job-oriented scenarios with focus.',
                    uk: 'Прицільно читай робочі, інституційні та професійні сценарії.',
                    ru: 'Прицельно читай рабочие, институциональные и профессиональные сценарии.',
                }),
                progressValue: 44,
                progressLabel: moduleProgressLabel(44),
                ctaLabel: moduleCtaStart.value,
                icon: '▤',
                order: '01',
                tone: 'primary',
            },
            {
                key: 'hoeren',
                title: 'Hören',
                body: pick({
                    de: 'Telefonate, Besprechungen und institutionelle Gespräche sicher verstehen.',
                    en: 'Understand calls, meetings, and institutional conversations confidently.',
                    uk: 'Упевнено розумій дзвінки, наради та інституційні розмови.',
                    ru: 'Уверенно понимай звонки, совещания и институциональные разговоры.',
                }),
                progressValue: 31,
                progressLabel: moduleProgressLabel(31),
                ctaLabel: moduleCtaStart.value,
                icon: '◖',
                order: '02',
                tone: 'secondary',
            },
            {
                key: 'schreiben',
                title: 'Schreiben',
                body: pick({
                    de: 'Mails, Berichte und formelle Antworten für den Berufsalltag trainieren.',
                    en: 'Train emails, reports, and formal responses for working life.',
                    uk: 'Тренуй листи, звіти й формальні відповіді для робочого життя.',
                    ru: 'Тренируй письма, отчёты и формальные ответы для рабочей жизни.',
                }),
                progressValue: 21,
                progressLabel: moduleProgressLabel(21),
                ctaLabel: moduleCtaStart.value,
                icon: '✎',
                order: '03',
                tone: 'tertiary',
            },
        ],
        osd: [
            {
                key: 'lesen',
                title: 'Lesen',
                body: pick({
                    de: 'Österreichisch geprägte Leselogik mit modularem Aufbau.',
                    en: 'Austrian-leaning reading logic with a modular structure.',
                    uk: 'Читання з австрійським акцентом і модульною структурою.',
                    ru: 'Чтение с австрийским акцентом и модульной структурой.',
                }),
                progressValue: 29,
                progressLabel: moduleProgressLabel(29),
                ctaLabel: moduleCtaStart.value,
                icon: '▤',
                order: '01',
                tone: 'primary',
            },
            {
                key: 'hoeren',
                title: 'Hören',
                body: pick({
                    de: 'Austro-Varianten, Interviews und modulare Hörteile trainieren.',
                    en: 'Train Austrian variants, interviews, and modular listening parts.',
                    uk: 'Тренуй австрійські варіанти, інтерв’ю та модульні аудіочастини.',
                    ru: 'Тренируй австрийские варианты, интервью и модульные аудиочасти.',
                }),
                progressValue: 24,
                progressLabel: moduleProgressLabel(24),
                ctaLabel: moduleCtaStart.value,
                icon: '◖',
                order: '02',
                tone: 'secondary',
            },
            {
                key: 'schreiben',
                title: 'Schreiben',
                body: pick({
                    de: 'Modulare schriftliche Aufgaben mit klarer Struktur und Registerkontrolle.',
                    en: 'Modular writing tasks with clear structure and register control.',
                    uk: 'Модульні письмові завдання з чіткою структурою та контролем регістру.',
                    ru: 'Модульные письменные задания с чёткой структурой и контролем регистра.',
                }),
                progressValue: 18,
                progressLabel: moduleProgressLabel(18),
                ctaLabel: moduleCtaStart.value,
                icon: '✎',
                order: '03',
                tone: 'tertiary',
            },
        ],
    },
    B2: {
        goethe: [
            {
                key: 'hoeren',
                title: 'Hören',
                body: pick({
                    de: 'Akademische Vorträge und strukturierte Debatten im Fokus.',
                    en: 'Academic lectures and structured debates in focus.',
                    uk: 'У фокусі академічні лекції та структуровані дискусії.',
                    ru: 'В фокусе академические лекции и структурированные дискуссии.',
                }),
                progressValue: 85,
                progressLabel: moduleProgressLabel(85),
                ctaLabel: moduleCtaStart.value,
                icon: '◖',
                order: '01',
                tone: 'primary',
            },
            {
                key: 'lesen',
                title: 'Lesen',
                body: pick({
                    de: 'Komplexe Sachtexte, Hinweise und Antwortlogik sauber analysieren.',
                    en: 'Analyze complex texts, hints, and answer logic cleanly.',
                    uk: 'Аналізуй складні тексти, підказки й логіку відповідей.',
                    ru: 'Анализируй сложные тексты, подсказки и логику ответов.',
                }),
                progressValue: 42,
                progressLabel: moduleProgressLabel(42),
                ctaLabel: moduleCtaStart.value,
                icon: '▤',
                order: '02',
                tone: 'secondary',
                href: '/modules/lesen',
            },
            {
                key: 'bausteine',
                title: 'Sprachbausteine',
                body: pick({
                    de: 'Syntaktische Präzision und Strukturen für den Prüfungstransfer.',
                    en: 'Syntactic precision and structures for exam transfer.',
                    uk: 'Синтаксична точність і структури для переносу в іспит.',
                    ru: 'Синтаксическая точность и структуры для переноса в экзамен.',
                }),
                progressValue: 71,
                progressLabel: moduleProgressLabel(71),
                ctaLabel: moduleCtaStart.value,
                icon: '✦',
                order: '03',
                tone: 'tertiary',
            },
        ],
        telc: [
            {
                key: 'lesen',
                title: 'Lesen',
                body: pick({
                    de: 'Der umgesetzte allgemeine telc-B2-Track mit Lesen Teil 1 und Prüfungsnavigation.',
                    en: 'The implemented general telc B2 track with Reading Part 1 and exam navigation.',
                    uk: 'Реалізований загальний telc B2 з Lesen Teil 1 та навігацією іспиту.',
                    ru: 'Реализованный общий telc B2 с Lesen Teil 1 и экзаменационной навигацией.',
                }),
                progressValue: 67,
                progressLabel: moduleProgressLabel(67),
                ctaLabel: moduleCtaStart.value,
                icon: '▤',
                order: '01',
                tone: 'primary',
                href: '/modules/lesen',
            },
            {
                key: 'hoeren',
                title: 'Hören',
                body: pick({
                    de: 'Allgemeine Hörsituationen mit Alltag, Interviews und klaren Antwortmustern.',
                    en: 'General listening situations with everyday speech, interviews, and clear answer patterns.',
                    uk: 'Загальні аудіоситуації з побутом, інтерв’ю та чіткими шаблонами відповідей.',
                    ru: 'Общие аудиоситуации с бытом, интервью и чёткими шаблонами ответов.',
                }),
                progressValue: 76,
                progressLabel: moduleProgressLabel(76),
                ctaLabel: moduleCtaStart.value,
                icon: '◖',
                order: '02',
                tone: 'secondary',
            },
            {
                key: 'sprach',
                title: 'Sprachbausteine',
                body: pick({
                    de: 'Präzise Auswahl von Formen und Strukturen in Prüfungslücken.',
                    en: 'Precise selection of forms and structures in exam gaps.',
                    uk: 'Точний вибір форм і структур у пропусках іспиту.',
                    ru: 'Точный выбор форм и структур в экзаменационных пропусках.',
                }),
                progressValue: 64,
                progressLabel: moduleProgressLabel(64),
                ctaLabel: moduleCtaStart.value,
                icon: '✦',
                order: '03',
                tone: 'tertiary',
            },
        ],
        beruf: [
            {
                key: 'lesen',
                title: 'Lesen',
                body: pick({
                    de: 'Arbeitswelt, Stellenausschreibungen und institutionelle Kommunikation im Fokus.',
                    en: 'Workplace texts, job notices, and institutional communication in focus.',
                    uk: 'У фокусі робочі тексти, вакансії та інституційна комунікація.',
                    ru: 'В фокусе рабочие тексты, вакансии и институциональная коммуникация.',
                }),
                progressValue: 38,
                progressLabel: moduleProgressLabel(38),
                ctaLabel: moduleCtaStart.value,
                icon: '▤',
                order: '01',
                tone: 'primary',
            },
            {
                key: 'hoeren',
                title: 'Hören',
                body: pick({
                    de: 'Telefonate, Arbeitsgespräche und betriebliche Situationen verstehen.',
                    en: 'Understand calls, workplace discussions, and business situations.',
                    uk: 'Розумій дзвінки, робочі розмови та бізнес-ситуації.',
                    ru: 'Понимай звонки, рабочие разговоры и бизнес-ситуации.',
                }),
                progressValue: 27,
                progressLabel: moduleProgressLabel(27),
                ctaLabel: moduleCtaStart.value,
                icon: '◖',
                order: '02',
                tone: 'secondary',
            },
            {
                key: 'schreiben',
                title: 'Schreiben',
                body: pick({
                    de: 'Berichte, Mails und formelle Antworten für den Berufsalltag.',
                    en: 'Reports, emails, and formal responses for working life.',
                    uk: 'Звіти, листи й формальні відповіді для професійного життя.',
                    ru: 'Отчёты, письма и формальные ответы для профессиональной жизни.',
                }),
                progressValue: 21,
                progressLabel: moduleProgressLabel(21),
                ctaLabel: moduleCtaStart.value,
                icon: '✎',
                order: '03',
                tone: 'tertiary',
            },
        ],
        osd: [
            {
                key: 'lesen',
                title: 'Lesen',
                body: pick({
                    de: 'Österreichischer B2-Lesetrack mit eigener Struktur und Aufgabenlogik.',
                    en: 'Austrian B2 reading track with its own structure and task logic.',
                    uk: 'Австрійський B2-трек читання з власною структурою та логікою завдань.',
                    ru: 'Австрийский B2-трек чтения с собственной структурой и логикой заданий.',
                }),
                progressValue: 26,
                progressLabel: moduleProgressLabel(26),
                ctaLabel: moduleCtaStart.value,
                icon: '▤',
                order: '01',
                tone: 'primary',
            },
            {
                key: 'hoeren',
                title: 'Hören',
                body: pick({
                    de: 'Austro-Varianten und modulare Hörteile mit eigenem Tempo.',
                    en: 'Austrian variants and modular listening parts with their own pace.',
                    uk: 'Австрійські варіанти та модульні аудіочастини з власним темпом.',
                    ru: 'Австрийские варианты и модульные аудиочасти со своим темпом.',
                }),
                progressValue: 22,
                progressLabel: moduleProgressLabel(22),
                ctaLabel: moduleCtaStart.value,
                icon: '◖',
                order: '02',
                tone: 'secondary',
            },
            {
                key: 'schreiben',
                title: 'Schreiben',
                body: pick({
                    de: 'Modulare schriftliche Aufgaben für den österreichischen Prüfungsstil.',
                    en: 'Modular writing tasks for the Austrian exam style.',
                    uk: 'Модульні письмові завдання для австрійського стилю іспиту.',
                    ru: 'Модульные письменные задания для австрийского стиля экзамена.',
                }),
                progressValue: 17,
                progressLabel: moduleProgressLabel(17),
                ctaLabel: moduleCtaStart.value,
                icon: '✎',
                order: '03',
                tone: 'tertiary',
            },
        ],
    },
    C1: {
        goethe: [
            {
                key: 'listening',
                title: 'Hören',
                body: pick({
                    de: 'Dichte Vorträge, Nuancen und implizite Hinweise erkennen.',
                    en: 'Recognize dense lectures, nuance, and implicit hints.',
                    uk: 'Розпізнавай щільні лекції, нюанси та приховані підказки.',
                    ru: 'Распознавай плотные лекции, нюансы и скрытые подсказки.',
                }),
                progressValue: 32,
                progressLabel: moduleProgressLabel(32),
                ctaLabel: moduleCtaStart.value,
                icon: '◖',
                order: '01',
                tone: 'primary',
            },
            {
                key: 'reading',
                title: 'Lesen',
                body: pick({
                    de: 'Komplexe Textarchitektur, Positionen und Abstraktion trainieren.',
                    en: 'Train complex text architecture, positions, and abstraction.',
                    uk: 'Тренуй складну архітектуру тексту, позиції та абстракцію.',
                    ru: 'Тренируй сложную архитектуру текста, позиции и абстракцию.',
                }),
                progressValue: 24,
                progressLabel: moduleProgressLabel(24),
                ctaLabel: moduleCtaStart.value,
                icon: '▤',
                order: '02',
                tone: 'secondary',
            },
            {
                key: 'analysis',
                title: 'Analyse',
                body: pick({
                    de: 'Feinabstimmung für Übergang in C-Level-Präzision.',
                    en: 'Fine tuning for the transition into C-level precision.',
                    uk: 'Тонке налаштування для переходу в C-level точність.',
                    ru: 'Тонкая настройка для перехода к точности уровня C.',
                }),
                progressValue: 18,
                progressLabel: moduleProgressLabel(18),
                ctaLabel: moduleCtaStart.value,
                icon: '◫',
                order: '03',
                tone: 'tertiary',
            },
        ],
        telc: [
            {
                key: 'writing',
                title: 'Schreiben',
                body: pick({
                    de: 'Allgemeiner telc-C1-Weg mit Fokus auf institutionelles Schreiben.',
                    en: 'General telc C1 path focused on institutional writing.',
                    uk: 'Загальний telc C1 з фокусом на інституційне письмо.',
                    ru: 'Общий telc C1 с фокусом на институциональное письмо.',
                }),
                progressValue: 28,
                progressLabel: moduleProgressLabel(28),
                ctaLabel: moduleCtaStart.value,
                icon: '✎',
                order: '01',
                tone: 'primary',
            },
            {
                key: 'structures',
                title: 'Sprachbausteine',
                body: pick({
                    de: 'Grammatik, Register und Nuancen für formelle Aufgaben.',
                    en: 'Grammar, register, and nuance for formal tasks.',
                    uk: 'Граматика, регістр і нюанси для формальних завдань.',
                    ru: 'Грамматика, регистр и нюансы для формальных задач.',
                }),
                progressValue: 21,
                progressLabel: moduleProgressLabel(21),
                ctaLabel: moduleCtaStart.value,
                icon: '✦',
                order: '02',
                tone: 'secondary',
            },
            {
                key: 'analysis',
                title: 'telc Analyse',
                body: pick({
                    de: 'Fehlerbilder auswerten und nächste C1-Lücken präzise schließen.',
                    en: 'Evaluate error patterns and close the next C1 gaps precisely.',
                    uk: 'Оцінюй патерни помилок і точно закривай наступні прогалини C1.',
                    ru: 'Оценивай паттерны ошибок и точно закрывай следующие пробелы C1.',
                }),
                progressValue: 16,
                progressLabel: moduleProgressLabel(16),
                ctaLabel: moduleCtaStart.value,
                icon: '◫',
                order: '03',
                tone: 'tertiary',
            },
        ],
        beruf: [
            {
                key: 'writing',
                title: 'Schreiben',
                body: pick({
                    de: 'Komplexe formelle Kommunikation, Berichte und Präzision im Beruf.',
                    en: 'Complex formal communication, reports, and workplace precision.',
                    uk: 'Складна формальна комунікація, звіти та точність у професії.',
                    ru: 'Сложная формальная коммуникация, отчёты и точность в профессии.',
                }),
                progressValue: 23,
                progressLabel: moduleProgressLabel(23),
                ctaLabel: moduleCtaStart.value,
                icon: '✎',
                order: '01',
                tone: 'primary',
            },
            {
                key: 'reading',
                title: 'Lesen',
                body: pick({
                    de: 'Dichte Fachtexte, Arbeitswelt und institutionelle Dokumente.',
                    en: 'Dense professional texts, workplace language, and institutional documents.',
                    uk: 'Щільні професійні тексти, робоча мова та інституційні документи.',
                    ru: 'Плотные профессиональные тексты, рабочая речь и институциональные документы.',
                }),
                progressValue: 19,
                progressLabel: moduleProgressLabel(19),
                ctaLabel: moduleCtaStart.value,
                icon: '▤',
                order: '02',
                tone: 'secondary',
            },
            {
                key: 'analysis',
                title: 'Beruf Analyse',
                body: pick({
                    de: 'Fehlerbilder in beruflichen Szenarien erkennen und schließen.',
                    en: 'Recognize and close error patterns in professional scenarios.',
                    uk: 'Розпізнавай і закривай патерни помилок у професійних сценаріях.',
                    ru: 'Распознавай и закрывай паттерны ошибок в профессиональных сценариях.',
                }),
                progressValue: 14,
                progressLabel: moduleProgressLabel(14),
                ctaLabel: moduleCtaStart.value,
                icon: '◫',
                order: '03',
                tone: 'tertiary',
            },
        ],
        osd: [
            {
                key: 'writing',
                title: 'Schreiben',
                body: pick({
                    de: 'Österreichischer C1-Schreibweg mit modularer Struktur.',
                    en: 'Austrian C1 writing path with a modular structure.',
                    uk: 'Австрійський C1-шлях письма з модульною структурою.',
                    ru: 'Австрийский путь письма C1 с модульной структурой.',
                }),
                progressValue: 20,
                progressLabel: moduleProgressLabel(20),
                ctaLabel: moduleCtaStart.value,
                icon: '✎',
                order: '01',
                tone: 'primary',
            },
            {
                key: 'reading',
                title: 'Lesen',
                body: pick({
                    de: 'Komplexe österreichische Leselogik und modulare Textarbeit.',
                    en: 'Complex Austrian reading logic and modular text work.',
                    uk: 'Складна австрійська логіка читання та модульна робота з текстом.',
                    ru: 'Сложная австрийская логика чтения и модульная работа с текстом.',
                }),
                progressValue: 16,
                progressLabel: moduleProgressLabel(16),
                ctaLabel: moduleCtaStart.value,
                icon: '▤',
                order: '02',
                tone: 'secondary',
            },
            {
                key: 'analysis',
                title: 'ÖSD Analyse',
                body: pick({
                    de: 'Muster, Register und Bewertungslogik des österreichischen Tracks.',
                    en: 'Patterns, register, and assessment logic of the Austrian track.',
                    uk: 'Патерни, регістр і логіка оцінювання австрійського треку.',
                    ru: 'Паттерны, регистр и логика оценивания австрийского трека.',
                }),
                progressValue: 11,
                progressLabel: moduleProgressLabel(11),
                ctaLabel: moduleCtaStart.value,
                icon: '◫',
                order: '03',
                tone: 'tertiary',
            },
        ],
    },
}));

const currentFormats = computed(() =>
    selectedLevel.value ? examFormatsByLevel.value[selectedLevel.value] : [],
);
const currentModules = computed(() =>
    selectedLevel.value && selectedFormat.value
        ? modulesBySelection.value[selectedLevel.value][selectedFormat.value]
        : [],
);
const activeModule = computed(
    () =>
        currentModules.value.find(
            (module) => module.key === selectedModuleKey.value,
        ) ?? null,
);
const secondaryActionHref = computed(
    () =>
        activeModule.value?.href ??
        currentModules.value.find((module) => module.href)?.href ??
        '/dashboard',
);
const modulesActiveCount = computed(() =>
    pick({
        de: `${currentModules.value.length} Module aktiv`,
        en: `${currentModules.value.length} modules active`,
        uk: `${currentModules.value.length} модулі активні`,
        ru: `${currentModules.value.length} модуля активны`,
    }),
);
watch(selectedLevel, (level) => {
    if (!level) {
        selectedFormat.value = null;
        return;
    }

    const nextFormats = examFormatsByLevel.value[level];

    if (
        !selectedFormat.value ||
        !nextFormats.some((format) => format.key === selectedFormat.value)
    ) {
        selectedFormat.value = null;
    }
});

watch([selectedLevel, selectedFormat], () => {
    selectedModuleKey.value = null;
});

watch(currentModules, (modules) => {
    if (
        selectedModuleKey.value &&
        !modules.some((module) => module.key === selectedModuleKey.value)
    ) {
        selectedModuleKey.value = null;
    }
});

onMounted(() => {
    if (typeof window === 'undefined') {
        return;
    }

    const rawState = window.sessionStorage.getItem(DASHBOARD_SELECTION_KEY);

    if (!rawState) {
        return;
    }

    try {
        const parsed = JSON.parse(rawState) as {
            selectedLevel?: LevelKey | null;
            selectedFormat?: FormatKey | null;
            selectedModuleKey?: string | null;
        };

        if (
            parsed.selectedLevel &&
            ['B1', 'B2', 'C1'].includes(parsed.selectedLevel)
        ) {
            selectedLevel.value = parsed.selectedLevel;
        }

        if (
            parsed.selectedFormat &&
            ['goethe', 'telc', 'beruf', 'osd'].includes(parsed.selectedFormat)
        ) {
            selectedFormat.value = parsed.selectedFormat;
        }

        if (typeof parsed.selectedModuleKey === 'string') {
            selectedModuleKey.value = parsed.selectedModuleKey;
        }
    } catch {
        window.sessionStorage.removeItem(DASHBOARD_SELECTION_KEY);
    }
});

watch(
    [selectedLevel, selectedFormat, selectedModuleKey],
    ([level, format, moduleKey]) => {
        if (typeof window === 'undefined') {
            return;
        }

        window.sessionStorage.setItem(
            DASHBOARD_SELECTION_KEY,
            JSON.stringify({
                selectedLevel: level,
                selectedFormat: format,
                selectedModuleKey: moduleKey,
            }),
        );
    },
    { deep: false },
);

const practiceCompanionParts = computed(() => {
    const marker = '__LEVEL__';
    const body = dashboardCopy.value.aiBody.replace(
        selectedLevel.value,
        marker,
    );
    const [before, after] = body.split(marker);

    return {
        before: before ?? '',
        level: selectedLevel.value,
        after: after ?? '',
    };
});

const metricCards = computed(() => [
    {
        label: dashboardCopy.value.metricResultLabel,
        value: '94',
        suffix: '%',
        body: dashboardCopy.value.metricResultBody,
        suffixClass: 'text-[var(--shell-accent)]',
    },
    {
        label: dashboardCopy.value.metricTimeLabel,
        value: '12.5',
        suffix: 'HRS',
        body: dashboardCopy.value.metricTimeBody,
        suffixClass: 'text-[var(--shell-secondary)]',
    },
]);

const quickActionsTitle = computed(() =>
    pick({
        de: 'Schnellzugriff',
        en: 'Quick actions',
        uk: 'Швидкі дії',
        ru: 'Быстрые действия',
    }),
);

const quickActionsDescription = computed(() => {
    if (activeModule.value) {
        return pick({
            de: `Setze ${activeModule.value.title} fort oder springe direkt in deine nächste fokussierte Einheit.`,
            en: `Resume ${activeModule.value.title} or jump straight into your next focused session.`,
            uk: `Продовжуй ${activeModule.value.title} або одразу переходь до наступної сфокусованої сесії.`,
            ru: `Продолжай ${activeModule.value.title} или сразу переходи к следующей сфокусированной сессии.`,
        });
    }

    return pick({
        de: 'Öffne das nächste sinnvolle Modul oder halte deinen Lernfluss mit einer kurzen Aktion am Laufen.',
        en: 'Open the next sensible module or keep your learning flow moving with one quick action.',
        uk: 'Відкрий наступний доречний модуль або підтримай навчальний ритм однією швидкою дією.',
        ru: 'Открой следующий уместный модуль или поддержи учебный ритм одним быстрым действием.',
    });
});

const quickActionSelectionBadges = computed(() =>
    [
        selectedLevel.value,
        selectedFormat.value
            ? formatDisplayName(
                  selectedFormat.value,
                  selectedLevel.value ?? 'B2',
              )
            : null,
        activeModule.value?.title ?? null,
    ].filter((value): value is string => Boolean(value)),
);

const quickActions = computed(() =>
    buildDashboardQuickActions({
        activeModule: activeModule.value
            ? {
                  key: activeModule.value.key,
                  title: activeModule.value.title,
                  body: activeModule.value.body,
                  href: activeModule.value.href,
                  icon: activeModule.value.icon,
              }
            : null,
        currentModules: currentModules.value.map((module) => ({
            key: module.key,
            title: module.title,
            body: module.body,
            href: module.href,
            icon: module.icon,
        })),
        aiTitle: dashboardCopy.value.aiTitle,
        aiBody: dashboardCopy.value.aiBody,
        aiCta: dashboardCopy.value.aiCta,
        communityTitle: dashboardCopy.value.communityTitle,
        communityBody: dashboardCopy.value.communityBody,
        communityCta: dashboardCopy.value.communityCta,
    }),
);

const handleQuickAction = (action: DashboardQuickAction): void => {
    if (action.key === 'practice') {
        toast(dashboardCopy.value.aiCta, {
            description: pick({
                de: 'Die mobile Trainingsoberfläche kommt als nächster Schritt. Ich habe dir den direkten Einstieg vorbereitet.',
                en: 'The mobile practice surface is the next step. Your direct entry flow is ready.',
                uk: 'Мобільний тренувальний екран буде наступним кроком. Прямий вхід уже підготовлено.',
                ru: 'Мобильный тренировочный экран будет следующим шагом. Прямой вход уже подготовлен.',
            }),
        });

        return;
    }

    if (action.key === 'community') {
        toast(dashboardCopy.value.communityTitle, {
            description: pick({
                de: 'Community-Mechaniken sind vorgemerkt. Die Karte markiert den nächsten sinnvollen Ausbau.',
                en: 'Community mechanics are queued. This card marks the next sensible extension point.',
                uk: 'Механіки спільноти вже в черзі. Ця картка позначає наступну логічну точку розвитку.',
                ru: 'Механики сообщества уже в очереди. Эта карточка отмечает следующую логичную точку развития.',
            }),
        });
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-6 md:space-y-8 xl:space-y-10">
        <section
            v-motion
            :initial="{ opacity: 0, y: 18 }"
            :enter="{ opacity: 1, y: 0, transition: { duration: 320 } }"
            class="space-y-4 min-[700px]:hidden md:hidden"
        >
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p
                        class="text-xs font-black tracking-[0.24em] text-[var(--shell-muted)]/80 uppercase"
                    >
                        {{ dashboardCopy.mobileKicker }}
                    </p>
                    <h1
                        class="mt-2 text-[3.25rem] leading-[0.94] font-extrabold text-[var(--shell-text)]"
                    >
                        {{ welcomeTitle }}
                    </h1>
                </div>
                <div
                    class="flex items-center gap-3 rounded-[1.4rem] px-4 py-3 shell-card"
                >
                    <span class="text-xl text-[var(--shell-secondary)]"
                        >🔥</span
                    >
                    <span
                        class="text-xl font-extrabold text-[var(--shell-text)]"
                        >{{ dashboardCopy.mobileStreakValue }}</span
                    >
                </div>
            </div>
        </section>

        <section
            v-motion
            :initial="{ opacity: 0, y: 20 }"
            :enter="{
                opacity: 1,
                y: 0,
                transition: { duration: 360, delay: 40 },
            }"
            class="hidden items-end justify-between gap-8 xl:flex"
        >
            <div class="max-w-4xl">
                <p class="app-kicker">{{ dashboardCopy.heroKicker }}</p>
                <h1
                    class="mt-3 max-w-5xl text-5xl leading-[0.94] font-extrabold text-[var(--shell-text)] xl:text-[4.85rem]"
                >
                    {{ dashboardCopy.heroTitle }}
                </h1>
                <p
                    class="mt-5 max-w-3xl text-lg leading-8 text-[var(--shell-muted)] xl:text-[1.9rem] xl:leading-10"
                >
                    {{ dashboardCopy.heroBody }}
                </p>
            </div>
            <div
                class="app-frost-panel hidden min-w-[14rem] rounded-[2rem] border-l-4 border-l-[var(--shell-secondary)] px-6 py-5 shadow-[0_24px_48px_rgba(37,47,61,0.08)] xl:block"
            >
                <div class="flex items-center gap-3">
                    <span class="text-xl text-[var(--shell-secondary)]"
                        >⚡</span
                    >
                    <span class="text-lg font-bold text-[var(--shell-text)]">{{
                        dashboardCopy.heroStreakTitle
                    }}</span>
                </div>
                <div class="mt-3 flex items-end gap-2">
                    <span
                        class="text-5xl leading-none font-black text-[var(--shell-text)]"
                        >{{ dashboardCopy.heroStreakValue }}</span
                    >
                    <span class="pb-1 text-xl text-[var(--shell-muted)]">{{
                        dashboardCopy.heroStreakSuffix
                    }}</span>
                </div>
            </div>
        </section>

        <section
            v-motion
            :initial="{ opacity: 0, y: 20 }"
            :enter="{
                opacity: 1,
                y: 0,
                transition: { duration: 340, delay: 30 },
            }"
            class="hidden gap-6 min-[700px]:grid min-[700px]:grid-cols-[1.35fr_0.75fr] min-[700px]:items-end md:grid md:grid-cols-[1.35fr_0.75fr] md:items-end xl:hidden"
        >
            <div class="max-w-3xl">
                <p class="app-kicker">{{ dashboardCopy.heroKicker }}</p>
                <h1
                    class="mt-3 text-[3.85rem] leading-[0.94] font-extrabold text-[var(--shell-text)]"
                >
                    {{ dashboardCopy.heroTitle }}
                </h1>
                <p
                    class="mt-4 max-w-2xl text-[1.02rem] leading-8 text-[var(--shell-muted)]"
                >
                    {{ dashboardCopy.heroBody }}
                </p>
            </div>
            <div
                class="app-frost-panel justify-self-end rounded-[1.9rem] border-l-4 border-l-[var(--shell-secondary)] px-5 py-5 shadow-[0_18px_36px_rgba(37,47,61,0.07)]"
            >
                <div class="flex items-center gap-3">
                    <span class="text-lg text-[var(--shell-secondary)]"
                        >⚡</span
                    >
                    <span
                        class="text-base font-bold text-[var(--shell-text)]"
                        >{{ dashboardCopy.heroStreakTitle }}</span
                    >
                </div>
                <div class="mt-3 flex items-end gap-2">
                    <span
                        class="text-[2.8rem] leading-none font-black text-[var(--shell-text)]"
                        >{{ dashboardCopy.heroStreakValue }}</span
                    >
                    <span class="pb-1 text-base text-[var(--shell-muted)]">{{
                        dashboardCopy.heroStreakSuffix
                    }}</span>
                </div>
            </div>
        </section>

        <section
            v-motion
            :initial="{ opacity: 0, y: 22 }"
            :enter="{
                opacity: 1,
                y: 0,
                transition: { duration: 320, delay: 70 },
            }"
            class="space-y-4 md:space-y-5"
        >
            <div class="flex items-center gap-3 min-[700px]:hidden md:hidden">
                <span
                    class="h-7 w-1.5 rounded-full bg-[var(--shell-accent)]"
                ></span>
                <h2 class="text-3xl font-extrabold text-[var(--shell-text)]">
                    {{ dashboardCopy.levelsTitle }}
                </h2>
            </div>
            <div class="hidden items-center gap-4 min-[700px]:flex md:flex">
                <h2
                    class="text-sm font-black tracking-[0.22em] text-[var(--shell-text)]/78 uppercase"
                >
                    {{ dashboardCopy.levelsTitleDesktop }}
                </h2>
                <div
                    class="h-px flex-1 bg-[color:color-mix(in_srgb,var(--shell-border)_90%,transparent)]"
                ></div>
            </div>

            <div
                class="grid gap-3 min-[700px]:grid-cols-3 min-[700px]:gap-6 md:grid-cols-3 md:gap-6"
                :class="selectedLevel ? 'grid-cols-4' : 'grid-cols-3'"
            >
                <PublicExamLevelCard
                    v-for="level in examLevels"
                    :key="level.key"
                    :eyebrow="level.eyebrow"
                    :title="level.title"
                    :progress-label="level.progressLabel"
                    :progress-value="level.progressValue"
                    :numeral="level.numeral"
                    :active="selectedLevel === level.key"
                    :completed="level.completed"
                    size="tablet"
                    class="hidden min-[700px]:flex md:flex xl:hidden"
                    @click="selectedLevel = level.key"
                />
                <PublicExamLevelCard
                    v-for="level in examLevels"
                    :key="`${level.key}-desktop`"
                    :eyebrow="level.eyebrow"
                    :title="level.title"
                    :progress-label="level.progressLabel"
                    :progress-value="level.progressValue"
                    :numeral="level.numeral"
                    :active="selectedLevel === level.key"
                    :completed="level.completed"
                    size="desktop"
                    class="hidden xl:flex"
                    @click="selectedLevel = level.key"
                />
                <PublicExamLevelCard
                    v-for="level in examLevels"
                    :key="`${level.key}-mobile`"
                    :eyebrow="dashboardCopy.levelsMobileBadge"
                    :title="level.key"
                    :progress-label="level.progressLabel"
                    :progress-value="level.progressValue"
                    :numeral="level.numeral"
                    :active="selectedLevel === level.key"
                    :completed="level.completed"
                    size="mobile"
                    :expand-active="true"
                    class="min-[700px]:hidden md:hidden"
                    @click="selectedLevel = level.key"
                />
            </div>
        </section>

        <section
            v-motion
            :initial="{ opacity: 0, y: 22 }"
            :enter="{
                opacity: 1,
                y: 0,
                transition: { duration: 320, delay: 110 },
            }"
            class="space-y-4 md:space-y-5"
        >
            <div class="flex items-center gap-3 min-[700px]:hidden md:hidden">
                <span
                    class="h-7 w-1.5 rounded-full bg-[var(--shell-secondary)]"
                ></span>
                <h2 class="text-3xl font-extrabold text-[var(--shell-text)]">
                    {{ dashboardCopy.formatsTitle }}
                </h2>
            </div>
            <div class="hidden items-center gap-4 min-[700px]:flex md:flex">
                <h2
                    class="text-sm font-black tracking-[0.22em] text-[var(--shell-text)]/78 uppercase"
                >
                    {{ dashboardCopy.formatsTitleDesktop }}
                </h2>
                <div
                    class="h-px flex-1 bg-[color:color-mix(in_srgb,var(--shell-border)_90%,transparent)]"
                ></div>
            </div>
            <div
                class="grid gap-3 min-[700px]:grid-cols-2 min-[700px]:gap-4 md:grid-cols-2 md:gap-4 xl:grid-cols-4"
            >
                <PublicExamFormatCard
                    v-for="format in currentFormats"
                    :key="format.key"
                    :category="format.category"
                    :title="format.title"
                    :body="format.body"
                    :icon="format.icon"
                    :active="selectedFormat === format.key"
                    :badge="
                        selectedFormat === format.key ? formatActiveBadge : ''
                    "
                    size="tablet"
                    class="hidden min-[700px]:flex md:flex xl:hidden"
                    :class="
                        currentFormats.length % 2 === 1 &&
                        currentFormats.indexOf(format) ===
                            currentFormats.length - 1
                            ? 'min-[700px]:col-span-2 md:col-span-2'
                            : ''
                    "
                    @click="selectedFormat = format.key"
                />
                <PublicExamFormatCard
                    v-for="format in currentFormats"
                    :key="`${format.key}-desktop`"
                    :category="format.category"
                    :title="format.title"
                    :body="format.body"
                    :icon="format.icon"
                    :active="selectedFormat === format.key"
                    :badge="
                        selectedFormat === format.key ? formatActiveBadge : ''
                    "
                    class="hidden xl:flex"
                    @click="selectedFormat = format.key"
                />
                <PublicExamFormatCard
                    v-for="format in currentFormats"
                    :key="`${format.key}-mobile`"
                    :category="format.category"
                    :title="format.title"
                    :body="format.body"
                    :icon="format.icon"
                    :active="selectedFormat === format.key"
                    :badge="
                        selectedFormat === format.key ? formatActiveBadge : ''
                    "
                    size="mobile"
                    class="min-[700px]:hidden md:hidden"
                    @click="selectedFormat = format.key"
                />
            </div>
        </section>

        <section
            v-motion
            :initial="{ opacity: 0, y: 22 }"
            :enter="{
                opacity: 1,
                y: 0,
                transition: { duration: 320, delay: 150 },
            }"
            class="space-y-4 md:space-y-5"
        >
            <div class="flex items-center gap-3 min-[700px]:hidden md:hidden">
                <span
                    class="h-7 w-1.5 rounded-full bg-[var(--shell-accent)]"
                ></span>
                <h2 class="text-3xl font-extrabold text-[var(--shell-text)]">
                    {{ dashboardCopy.modulesHeading }}
                </h2>
            </div>

            <article
                class="rounded-[2rem] p-4 shell-panel min-[700px]:hidden md:hidden"
            >
                <div
                    class="grid justify-items-center gap-4 min-[700px]:justify-items-stretch"
                >
                    <PublicModuleProgressCard
                        v-for="module in currentModules"
                        :key="`${module.key}-mobile`"
                        :title="module.title"
                        :body="module.body"
                        :progress-label="module.progressLabel"
                        :progress-value="module.progressValue"
                        :cta-label="module.ctaLabel"
                        :icon="module.icon"
                        :order="module.order"
                        :tone="module.tone"
                        :href="module.href"
                        :active="activeModule?.key === module.key"
                        size="mobile"
                        class="w-full"
                        @click="selectedModuleKey = module.key"
                    />
                </div>
            </article>

            <article
                class="hidden rounded-[2.15rem] bg-[var(--shell-surface-alt)] p-7 shell-panel min-[700px]:block md:block xl:hidden"
            >
                <div class="flex items-start justify-between gap-6">
                    <div>
                        <h2
                            class="text-[2.25rem] font-extrabold text-[var(--shell-text)]"
                        >
                            {{ dashboardCopy.modulesHeading }}
                        </h2>
                        <p
                            class="mt-2 max-w-2xl text-[1rem] leading-7 text-[var(--shell-muted)]"
                        >
                            {{ dashboardCopy.modulesSubtitle }}
                        </p>
                    </div>
                    <span
                        class="rounded-full bg-[color:color-mix(in_srgb,var(--shell-secondary-soft)_40%,white)] px-4 py-2 text-[11px] font-black tracking-[0.18em] text-[var(--shell-secondary)] uppercase"
                    >
                        {{ modulesActiveCount }}
                    </span>
                </div>

                <div class="mt-7 grid grid-cols-2 gap-5">
                    <PublicModuleProgressCard
                        v-for="(module, index) in currentModules"
                        :key="module.key"
                        :title="module.title"
                        :body="module.body"
                        :progress-label="module.progressLabel"
                        :progress-value="module.progressValue"
                        :cta-label="module.ctaLabel"
                        :icon="module.icon"
                        :order="module.order"
                        :tone="module.tone"
                        :href="module.href"
                        :active="activeModule?.key === module.key"
                        size="tablet"
                        :class="
                            currentModules.length % 2 === 1 &&
                            index === currentModules.length - 1
                                ? 'col-span-2'
                                : ''
                        "
                        @click="selectedModuleKey = module.key"
                    />
                </div>
            </article>

            <article
                class="hidden rounded-[2.2rem] bg-[var(--shell-surface-alt)] p-8 shell-panel xl:block xl:p-8 2xl:p-10"
            >
                <div class="hidden items-start justify-between gap-6 md:flex">
                    <div>
                        <h2
                            class="text-4xl font-extrabold text-[var(--shell-text)] xl:text-[3rem]"
                        >
                            {{ dashboardCopy.modulesHeading }}
                        </h2>
                        <p
                            class="mt-2 max-w-3xl text-base leading-7 text-[var(--shell-muted)] xl:text-lg"
                        >
                            {{ dashboardCopy.modulesSubtitle }}
                        </p>
                    </div>
                    <span
                        class="rounded-full bg-[color:color-mix(in_srgb,var(--shell-secondary-soft)_40%,white)] px-5 py-2 text-xs font-black tracking-[0.2em] text-[var(--shell-secondary)] uppercase"
                    >
                        {{ modulesActiveCount }}
                    </span>
                </div>

                <div class="app-dashboard-modules-grid md:mt-8">
                    <PublicModuleProgressCard
                        v-for="(module, index) in currentModules"
                        :key="module.key"
                        :title="module.title"
                        :body="module.body"
                        :progress-label="module.progressLabel"
                        :progress-value="module.progressValue"
                        :cta-label="module.ctaLabel"
                        :icon="module.icon"
                        :order="module.order"
                        :tone="module.tone"
                        :href="module.href"
                        :active="activeModule?.key === module.key"
                        class="flex"
                        :class="
                            currentModules.length % 2 === 1 &&
                            index === currentModules.length - 1
                                ? 'app-dashboard-module-card--stack-last'
                                : ''
                        "
                        @click="selectedModuleKey = module.key"
                    />
                </div>
            </article>
        </section>

        <section
            v-motion
            :initial="{ opacity: 0, y: 24 }"
            :enter="{
                opacity: 1,
                y: 0,
                transition: { duration: 320, delay: 180 },
            }"
            class="grid gap-4 min-[700px]:hidden md:hidden"
        >
            <article
                class="overflow-hidden rounded-[2.4rem] bg-[color:color-mix(in_srgb,var(--shell-accent)_94%,black_6%)] p-8 text-white shadow-[0_26px_50px_color-mix(in_srgb,var(--shell-accent)_18%,transparent)]"
            >
                <h2 class="text-[2.6rem] leading-tight font-extrabold">
                    {{ dashboardCopy.aiMobileTitle }}
                </h2>
                <p class="mt-4 text-lg leading-8 text-white/84">
                    {{ dashboardCopy.aiMobileBody }}
                </p>
                <button
                    type="button"
                    class="mt-7 rounded-full bg-[color:color-mix(in_srgb,var(--shell-accent-soft)_35%,black_2%)] px-5 py-3 text-base font-black text-[var(--shell-accent-soft)]"
                    @click="quickActionsOpen = true"
                >
                    {{ dashboardCopy.aiCta }}
                </button>
            </article>
        </section>

        <section class="hidden gap-5 min-[700px]:grid md:grid xl:hidden">
            <PublicAiPanel
                :kicker="dashboardCopy.heroKicker"
                :title="dashboardCopy.aiTitle"
                :body-before="practiceCompanionParts.before"
                :body-highlight="practiceCompanionParts.level"
                :body-after="practiceCompanionParts.after"
                :primary-href="secondaryActionHref"
                :primary-label="dashboardCopy.aiCta"
                size="tablet"
            />

            <div class="grid grid-cols-2 gap-5">
                <article
                    v-for="metric in metricCards"
                    :key="`${metric.label}-tablet`"
                    class="rounded-[1.9rem] p-7 shell-card"
                >
                    <p
                        class="text-[10px] font-black tracking-[0.28em] text-[var(--shell-muted)] uppercase"
                    >
                        {{ metric.label }}
                    </p>
                    <div class="mt-5 flex items-end gap-2">
                        <span
                            class="text-5xl leading-none font-black text-[var(--shell-text)]"
                            >{{ metric.value }}</span
                        >
                        <span
                            class="pb-1 text-2xl font-black"
                            :class="metric.suffixClass"
                            >{{ metric.suffix }}</span
                        >
                    </div>
                    <p
                        class="mt-3 text-base leading-7 text-[var(--shell-muted)]"
                    >
                        {{ metric.body }}
                    </p>
                </article>

                <article
                    class="col-span-2 flex items-center justify-between gap-5 rounded-[1.9rem] border border-[color:color-mix(in_srgb,var(--shell-secondary)_18%,transparent)] bg-[color:color-mix(in_srgb,var(--shell-secondary-soft)_22%,white)] p-7"
                >
                    <div class="max-w-[30rem]">
                        <h3
                            class="text-[1.75rem] font-extrabold text-[var(--shell-secondary-text)]"
                        >
                            {{ dashboardCopy.communityTitle }}
                        </h3>
                        <p
                            class="mt-3 text-base leading-7 text-[color:color-mix(in_srgb,var(--shell-secondary-text)_76%,transparent)]"
                        >
                            {{ dashboardCopy.communityBody }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-full bg-[var(--shell-secondary)] px-7 py-4 text-lg font-black text-white shadow-[0_16px_32px_color-mix(in_srgb,var(--shell-secondary)_18%,transparent)]"
                    >
                        {{ dashboardCopy.communityCta }}
                    </button>
                </article>
            </div>
        </section>

        <section
            class="hidden gap-6 xl:grid xl:grid-cols-[1.05fr_0.95fr] xl:gap-8"
        >
            <article
                class="relative overflow-hidden rounded-[2.25rem] bg-[#263246] p-10 text-white shadow-[0_26px_56px_rgba(20,28,41,0.22)]"
            >
                <div class="relative z-10 max-w-[32rem]">
                    <h2 class="text-[3.1rem] leading-[1.02] font-extrabold">
                        {{ dashboardCopy.aiTitle }}
                    </h2>
                    <p class="mt-6 text-xl leading-9 text-white/82">
                        {{ practiceCompanionParts.before
                        }}<span
                            class="font-bold text-[var(--shell-accent-soft)]"
                            >{{ practiceCompanionParts.level }}</span
                        >{{ practiceCompanionParts.after }}
                    </p>
                    <button
                        type="button"
                        class="mt-8 flex items-center gap-3 rounded-full bg-[var(--shell-accent)] px-8 py-4 text-lg font-black text-white shadow-[0_16px_32px_color-mix(in_srgb,var(--shell-accent)_24%,transparent)]"
                    >
                        <span>◉</span>
                        {{ dashboardCopy.aiCta }}
                    </button>
                </div>
                <div
                    class="pointer-events-none absolute right-0 -bottom-6 text-[18rem] leading-none font-black text-white/10"
                >
                    ◫
                </div>
            </article>

            <div class="grid gap-6">
                <div class="grid grid-cols-2 gap-6">
                    <article
                        v-for="metric in metricCards"
                        :key="metric.label"
                        class="rounded-[2rem] p-8 shell-card"
                    >
                        <p
                            class="text-[10px] font-black tracking-[0.34em] text-[var(--shell-muted)] uppercase"
                        >
                            {{ metric.label }}
                        </p>
                        <div class="mt-6 flex items-end gap-2">
                            <span
                                class="text-6xl leading-none font-black text-[var(--shell-text)]"
                                >{{ metric.value }}</span
                            >
                            <span
                                class="pb-2 text-3xl font-black"
                                :class="metric.suffixClass"
                                >{{ metric.suffix }}</span
                            >
                        </div>
                        <p class="mt-4 text-lg text-[var(--shell-muted)]">
                            {{ metric.body }}
                        </p>
                    </article>
                </div>

                <article
                    class="flex items-center justify-between gap-6 rounded-[2rem] border border-[color:color-mix(in_srgb,var(--shell-secondary)_18%,transparent)] bg-[color:color-mix(in_srgb,var(--shell-secondary-soft)_22%,white)] p-8"
                >
                    <div class="max-w-[28rem]">
                        <h3
                            class="text-[2rem] font-extrabold text-[var(--shell-secondary-text)]"
                        >
                            {{ dashboardCopy.communityTitle }}
                        </h3>
                        <p
                            class="mt-3 text-lg leading-8 text-[color:color-mix(in_srgb,var(--shell-secondary-text)_76%,transparent)]"
                        >
                            {{ dashboardCopy.communityBody }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-full bg-[var(--shell-secondary)] px-8 py-5 text-xl font-black text-white shadow-[0_18px_36px_color-mix(in_srgb,var(--shell-secondary)_18%,transparent)]"
                    >
                        {{ dashboardCopy.communityCta }}
                    </button>
                </article>
            </div>
        </section>

        <AppBottomActionSheet
            v-model:open="quickActionsOpen"
            :title="quickActionsTitle"
            :description="quickActionsDescription"
            :badges="quickActionSelectionBadges"
            :actions="quickActions"
            @action="handleQuickAction"
        />
    </div>
</template>
