import axios from 'axios';
import { route } from 'ziggy-js';

// defining game standards
const minecraftGame = {
    key: 'minecraft',
    name: 'Minecraft',
    icon: '/imgs/icons/minecraft.png',
};

const unwrapServers = (payload) => {
    if (Array.isArray(payload?.data)) {
        return payload.data;
    }

    return Array.isArray(payload) ? payload : [];
};

const normalizeMinecraftServer = (server) => ({
    id: server.id,
    name: server.server_name,
    game: minecraftGame,
    status: server.status ?? 'stopped',
    access: server.access_role ?? 'owner',
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

export const fetchServerPageData = async (page = 1) => {
    
    const [serverResponse, slotResponse] = await Promise.all([
        axios.get(route('index.minecraftServer'), { params: { page } }),
        axios.get(route('index.execution_slot')),
    ]);

    const servers = unwrapServers(serverResponse.data).map(normalizeMinecraftServer);
    const slots = Array.isArray(slotResponse.data) ? slotResponse.data : [];

    return {
        servers,
        executionSlots: slots.map((slot) => normalizeExecutionSlot(slot, servers)),
        pagination: {
            currentPage: Number(serverResponse.data?.current_page) || 1,
            totalPages: Number(serverResponse.data?.last_page) || 1,
        },
    };
};

export const fetchMinecraftVersions = async () => {
    const response = await axios.get(
        route('index.minecraftVersion'),
    );

    return Array.isArray(response.data) ? response.data : [];
};

export const createMinecraftServer = (payload) => axios.post(
    route('create.minecraftServer'),
    payload,
);
