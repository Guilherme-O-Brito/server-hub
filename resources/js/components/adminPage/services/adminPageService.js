import axios from 'axios';
import { route } from 'ziggy-js';

const minecraftGame = {
    key: 'minecraft',
    name: 'Minecraft',
    icon: '/imgs/icons/minecraft.png',
};

const gameForSlot = (slot) => {
    if (
        slot.server_type === minecraftGame.key
        || slot.server_type?.endsWith('MinecraftServer')
    ) {
        return minecraftGame;
    }

    return {
        key: 'unknown',
        name: 'Não informado',
        icon: '',
    };
};

const normalizeUser = (user) => ({
    id: Number(user.id),
    name: user.name,
    email: user.email ?? null,
    isAdmin: user.is_admin == null ? null : Boolean(user.is_admin),
    createdAt: user.created_at ?? null,
});

const normalizeVersion = (version) => ({
    id: Number(version.id),
    version: version.version,
    isEnabled: Boolean(version.is_enabled),
});

const normalizeExecutionSlot = (slot) => {
    const game = gameForSlot(slot);
    const server = slot.server
        ? {
            id: Number(slot.server.id),
            name: slot.server.server_name ?? slot.server.name,
            game,
            owner: slot.server.owner?.name ?? 'Não informado',
        }
        : null;

    return {
        id: Number(slot.id),
        name: `Slot ${slot.slot_number}`,
        slotNumber: Number(slot.slot_number),
        hostname: slot.hostname ?? '',
        externalPort: Number(slot.external_port),
        serviceName: slot.service_name,
        status: slot.status,
        server,
        serverName: server?.name ?? null,
        gameName: server ? game.name : null,
    };
};

export const fetchAdminUsers = async ({ search = '', page = 1 } = {}) => {
    const response = await axios.get(route('index.user'), {
        params: {
            search: search || undefined,
            page,
            per_page: 20,
        },
    });
    const payload = response.data ?? {};

    return {
        users: Array.isArray(payload.data)
            ? payload.data.map(normalizeUser)
            : [],
        pagination: {
            currentPage: Number(payload.current_page) || 1,
            totalPages: Number(payload.last_page) || 1,
            total: Number(payload.total) || 0,
        },
    };
};

export const createUser = (payload) => axios.post(
    route('create.user'),
    payload,
);

export const updateUser = (userId, payload) => axios.put(
    route('update.user', { user: userId }),
    payload,
);

export const deleteUser = (userId) => axios.delete(
    route('delete.user', { user: userId }),
);

export const fetchMinecraftVersions = async () => {
    const response = await axios.get(route('index.minecraftVersion'));

    return Array.isArray(response.data)
        ? response.data.map(normalizeVersion)
        : [];
};

export const createMinecraftVersion = (payload) => axios.post(
    route('create.minecraftVersion'),
    payload,
);

export const toggleMinecraftVersion = (versionId) => axios.post(
    route('toggle.minecraftVersion', { minecraftVersion: versionId }),
);

export const deleteMinecraftVersion = (versionId) => axios.delete(
    route('delete.minecraftVersion', { minecraftVersion: versionId }),
);

export const fetchExecutionSlots = async () => {
    const response = await axios.get(route('index.execution_slot'));

    return Array.isArray(response.data)
        ? response.data.map(normalizeExecutionSlot)
        : [];
};

export const createExecutionSlot = () => axios.post(
    route('create_one.execution_slot'),
);

export const deleteLastExecutionSlot = () => axios.delete(
    route('delete_last.execution_slot'),
);
