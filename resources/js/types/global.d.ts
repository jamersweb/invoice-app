import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { AxiosInstance } from 'axios';
import { PageProps as AppPageProps } from './';

// Route function type - available globally via ZiggyVue plugin
type RouteFunction = (name: string, params?: Record<string, any> | any, absolute?: boolean) => string;

declare global {
    interface Window {
        axios: AxiosInstance;
    }

    /* eslint-disable no-var */
    var route: RouteFunction;
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: RouteFunction;
    }
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, AppPageProps {}
}
