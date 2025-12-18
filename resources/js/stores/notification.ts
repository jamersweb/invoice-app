import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface Notification {
    id: number;
    message: string;
    type: 'success' | 'error' | 'warning' | 'info';
    duration?: number;
}

export const useNotificationStore = defineStore('notification', () => {
    const notifications = ref<Notification[]>([]);
    let nextId = 1;

    function notify(message: string, type: Notification['type'] = 'info', duration = 5000) {
        const id = nextId++;
        notifications.value.push({ id, message, type, duration });

        if (duration > 0) {
            setTimeout(() => {
                remove(id);
            }, duration);
        }
    }

    function remove(id: number) {
        const index = notifications.value.findIndex((n) => n.id === id);
        if (index !== -1) {
            notifications.value.splice(index, 1);
        }
    }

    function error(message: string, duration = 7000) {
        notify(message, 'error', duration);
    }

    function success(message: string, duration = 5000) {
        notify(message, 'success', duration);
    }

    function warning(message: string, duration = 5000) {
        notify(message, 'warning', duration);
    }

    return {
        notifications,
        notify,
        remove,
        error,
        success,
        warning,
    };
});
