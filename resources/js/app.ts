import '../css/app.css';
import './bootstrap';
import '../css/custom.css';
import '../custom-js/custom-index.js';


import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, DefineComponent, h } from 'vue';
import { createPinia } from 'pinia';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Ensure dark mode is enabled
if (typeof document !== 'undefined') {
    document.documentElement.classList.add('dark');
    document.body.classList.add('dark');
    document.body.style.backgroundColor = '#1E1E1E';
    document.body.style.color = '#FFFFFF';
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        const app = createApp({ render: () => h(App, props) });
        // Safe fallback for i18n to avoid "$t is not defined" warnings
        // Replace later with vue-i18n and real messages
        // @ts-ignore
        app.config.globalProperties.$t = (key: string) => key;
        app
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue)
            .mount(el);

        // Ensure dark mode after mount
        document.documentElement.classList.add('dark');
        document.body.classList.add('dark');
        document.body.style.backgroundColor = '#1E1E1E';
        document.body.style.color = '#FFFFFF';
    },
    progress: {
        color: '#4B5563',
    },
});
