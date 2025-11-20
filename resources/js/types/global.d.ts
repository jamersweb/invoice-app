import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { AxiosInstance } from 'axios';
import { PageProps as AppPageProps } from './';

// Router interface for route() without arguments
interface ZiggyRouter {
    current(): string | undefined;
    current(name: string, params?: any): boolean;
    readonly params: Record<string, string>;
    readonly routeParams: Record<string, string>;
    readonly queryParams: Record<string, any>;
    has(name: string): boolean;
}

// Route function type - available globally via ZiggyVue plugin
// Supports both route() (returns Router) and route(name, params) (returns string)
type RouteFunction = {
    (): ZiggyRouter;
    (name: string, params?: Record<string, any> | any, absolute?: boolean): string;
};

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
