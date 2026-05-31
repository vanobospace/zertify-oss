import '@fontsource-variable/inter';
import '@fontsource-variable/ibm-plex-sans';
import '@fontsource-variable/manrope';
import '@fontsource-variable/source-sans-3';
import '../css/app.css';

import AppToaster from '@/Components/App/AppToaster.vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { MotionPlugin } from '@vueuse/motion';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { Fragment, createApp, h } from 'vue';

createInertiaApp({
    title: (title) => (title ? `${title} · Zertify` : 'Zertify'),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({
            render: () => h(Fragment, [h(App, props), h(AppToaster)]),
        })
            .use(plugin)
            .use(MotionPlugin)
            .mount(el);
    },
    progress: {
        color: '#0049e6',
    },
});
