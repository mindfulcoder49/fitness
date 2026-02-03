import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, watch } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        const vm = app.mount(el);

        // Watch for theme changes and update the body data-theme attribute
        watch(
            () => vm.$page?.props?.auth?.user?.theme,
            (theme) => {
                if (theme) {
                    document.body.setAttribute('data-theme', theme);
                    localStorage.setItem('theme', theme);
                }
            },
            { immediate: true }
        );

        return vm;
    },
    progress: {
        color: '#4B5563',
    },
});
