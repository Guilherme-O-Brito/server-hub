import { onBeforeUnmount, onMounted, watch } from 'vue';

const minecraftGame = {
    key: 'minecraft',
    name: 'Minecraft',
    icon: '/imgs/icons/minecraft.png',
};

const gameForServerType = (serverType) => (
    serverType === minecraftGame.key || serverType?.endsWith('MinecraftServer')
        ? minecraftGame
        : { key: 'unknown', name: 'Não informado', icon: '' }
);

export const useExecutionSlotStatusUpdates = (executionSlots) => {
    const pendingUpdates = new Map();

    const updateLoadedSlot = (slotId, update) => {
        const slot = executionSlots.value.find(
            (executionSlot) => String(executionSlot.id) === String(slotId),
        );

        if (!slot) {
            return false;
        }

        slot.status = update.status;

        if (Object.prototype.hasOwnProperty.call(update, 'server')) {
            if (!update.server) {
                slot.server = null;
                slot.serverName = null;
                slot.gameName = null;
            } else {
                const server = slot.server ?? {};
                const game = Object.prototype.hasOwnProperty.call(update, 'server_type')
                    ? gameForServerType(update.server_type)
                    : server.game ?? gameForServerType(null);

                Object.assign(server, {
                    id: update.server.id ?? server.id ?? null,
                    name: update.server.server_name ?? update.server.name ?? server.name,
                    game,
                    owner: update.server.owner?.name ?? server.owner ?? 'Não informado',
                    status: server.status ?? 'stopped',
                    access: server.access ?? 'owner',
                    players: server.players ?? { online: 0, limit: 10 },
                    executionSlotId: server.executionSlotId ?? slot.id,
                });

                slot.server = server;
                slot.serverName = server.name;
                slot.gameName = game.name;
            }
        }

        return true;
    };

    const handleStatusChanged = (update = {}) => {
        const { id, status } = update;

        if (id == null || typeof status !== 'string') {
            return;
        }

        if (!updateLoadedSlot(id, update)) {
            pendingUpdates.set(String(id), update);
        }
    };

    const stopWatchingSlots = watch(executionSlots, (slots) => {
        slots.forEach((slot) => {
            const slotId = String(slot.id);

            if (pendingUpdates.has(slotId)) {
                updateLoadedSlot(slot.id, pendingUpdates.get(slotId));
                pendingUpdates.delete(slotId);
            }
        });
    });

    onMounted(() => {
        window.Echo
            .private('execution-slots')
            .listen('.slot.status.changed', handleStatusChanged);
    });

    onBeforeUnmount(() => {
        stopWatchingSlots();
        window.Echo.leave('execution-slots');
    });
};
