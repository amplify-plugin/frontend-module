import mitt from 'mitt';
import { onUnmounted } from 'vue';

const eventBus = mitt();

export function useEvent() {
    const listeners = [];

    const emit = (event, payload) => {
        eventBus.emit(event, payload);
    };

    const on = (event, callback) => {
        eventBus.on(event, callback);

        listeners.push(() => {
            eventBus.off(event, callback);
        });
    };

    const off = (event, callback) => {
        eventBus.off(event, callback);
    };

    onUnmounted(() => {
        listeners.forEach(remove => remove());
    });

    return {
        emit,
        on,
        off,
    };
}
