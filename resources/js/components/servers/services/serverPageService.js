import axios from 'axios';
import { route } from 'ziggy-js';
import { Ziggy } from '../../../ziggy';
//import { mockExecutionSlots, mockServers } from '../data/mockServerPageData';

// defining game standards
const minecraftGame = {
    key: 'minecraft',
    name: 'Minecraft',
    icon: '/imgs/icons/minecraft.png',
};

const unwrapServers = (payload) => {
    if (Array.isArray(payload?.[0])) {
        return payload[0];
    }

    return Array.isArray(payload) ? payload : [];
};

const normalizeMinecraftServer = (server) => ({
    id: server.id,
    name: server.server_name,
    game: minecraftGame,
    status: server.status ?? 'stopped',
    access: server.access ?? 'owner',
    players: {
        online: server.players?.online ?? server.online_players ?? 0,
        limit: server.players?.limit ?? server.max_players ?? 10,
    },
    owner: server.owner?.name ?? 'Não informado',
    executionSlotId: server.execution_slot?.id ?? null,
});

const normalizeExecutionSlot = (slot, servers) => {
    const linkedServer = servers.find((server) => server.executionSlotId === slot.id);

    return {
        id: slot.id,
        name: `Slot ${slot.slot_number}`,
        status: slot.status,
        server: linkedServer ?? null,
    };
};

export const fetchServerPageData = async () => {
    
    const [serverResponse, slotResponse] = await Promise.all([
        axios.get(route('index.minecraftServer', undefined, undefined, Ziggy)),
        axios.get(route('index.execution_slot', undefined, undefined, Ziggy)),
    ]);

    const servers = unwrapServers(serverResponse.data).map(normalizeMinecraftServer);
    const slots = Array.isArray(slotResponse.data) ? slotResponse.data : [];

    return {
        servers,
        executionSlots: slots.map((slot) => normalizeExecutionSlot(slot, servers)),
    };
};

export const getManagementUrl = (server) => {
    if (server.game.key !== 'minecraft') {
        return null;
    }

    return route(
        'get.minecraftServer',
        { minecraftServer: server.id },
        undefined,
        Ziggy,
    );
};
