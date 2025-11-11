import './bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import '@fortawesome/fontawesome-free/css/all.min.css';
import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { start as startProgressIndicator } from '@inertiajs/progress';

const appName = (import.meta.env.VITE_APP_NAME as string | undefined) ?? 'Laravel';

startProgressIndicator({
    color: '#2563eb',
    includeCSS: true,
    showSpinner: true,
});

createInertiaApp({
    title: (title) => (title ? `${title} | ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        const vueApp = createApp({ render: () => h(App, props) });

        vueApp.use(plugin).use(pinia).mount(el);
        return vueApp;
    },
});

