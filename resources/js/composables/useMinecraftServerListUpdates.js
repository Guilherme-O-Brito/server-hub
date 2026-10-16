import { onBeforeUnmount, onMounted } from 'vue';

export const useMinecraftServerListUpdates = (servers, userId) => {
    let channelName = null;

    const handleStatusChanged = ({ id, status } = {}) => {
        if (id == null || typeof status !== 'string') {
            return;
        }

        const server = servers.value.find(
            (item) => String(item.id) === String(id),
        );

        if (server) {
            server.status = status;
        }
    };

    const handleServerDeleted = ({ id } = {}) => {
        if (id == null) {
            return;
        }

        servers.value = servers.value.filter(
            (server) => String(server.id) !== String(id),
        );
    };

    onMounted(() => {
        if (userId == null) {
            return;
        }

        channelName = `users.${userId}.servers`;

        window.Echo
            .private(channelName)
            .listen('.server.status.changed', handleStatusChanged)
            .listen('.server.deleted', handleServerDeleted);
    });

    onBeforeUnmount(() => {
        if (channelName) {
            window.Echo.leave(channelName);
        }
    });
};
