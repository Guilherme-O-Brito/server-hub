import { onBeforeUnmount, watch } from 'vue';

export const useMinecraftServerUpdates = (serverId, { onUpdated, onDeleted }) => {
    let channelName = null;

    const stopWatchingServer = watch(serverId, (id) => {
        if (channelName) {
            window.Echo.leave(channelName);
            channelName = null;
        }

        if (id == null) {
            return;
        }

        channelName = `servers.minecraft.${id}`;

        window.Echo
            .private(channelName)
            .listen('.server.updated', onUpdated)
            .listen('.server.deleted', onDeleted);
    }, { immediate: true });

    onBeforeUnmount(() => {
        stopWatchingServer();

        if (channelName) {
            window.Echo.leave(channelName);
        }
    });
};
