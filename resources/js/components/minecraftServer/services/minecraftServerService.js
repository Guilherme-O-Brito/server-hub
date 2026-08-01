import axios from 'axios';
import { route } from 'ziggy-js';

const normalizeServer = (server) => ({
    id: Number(server.id),
    ownerId: server.owner_id == null ? null : Number(server.owner_id),
    name: server.server_name,
    motd: server.motd,
    difficulty: server.difficulty,
    version: server.version ?? null,
    forceGamemode: Boolean(server.force_gamemode),
    allowFlight: Boolean(server.allow_flight),
    accessRole: server.access_role ?? null,
    status: server.status ?? null,
    lastError: server.last_error ?? null,
    executionSlot: server.execution_slot ?? null,
    players: server.players ?? null,
    resources: server.resources ?? server.metrics ?? null,
});

export const fetchMinecraftServer = async (serverId) => {
    const response = await axios.get(
        route(
            'get.minecraftServer',
            { minecraftServer: serverId },
        ),
    );

    return normalizeServer(response.data);
};

export const fetchMinecraftVersions = async () => {
    const response = await axios.get(
        route('index.minecraftVersion'),
    );

    return Array.isArray(response.data) ? response.data : [];
};

export const updateMinecraftServer = (serverId, payload) => axios.put(
    route(
        'update.minecraftServer',
        { minecraftServer: serverId },
    ),
    payload,
);

export const deleteMinecraftServer = (serverId) => axios.delete(
    route(
        'delete.minecraftServer',
        { minecraftServer: serverId },
    ),
);

export const startMinecraftServer = (serverId) => axios.post(
    route(
        'start.minecraftServer',
        { minecraftServer: serverId },
    ),
);

export const stopMinecraftServer = (serverId) => axios.post(
    route(
        'stop.minecraftServer',
        { minecraftServer: serverId },
    ),
);

export const fetchWhitelist = async (serverId) => {
    const response = await axios.get(
        route(
            'index.minecraftServer.whitelist',
            { minecraftServer: serverId },
        ),
    );

    return Array.isArray(response.data) ? response.data : [];
};

export const addWhitelistNickname = (serverId, nickname) => axios.post(
    route(
        'create.minecraftServer.whitelist',
        { minecraftServer: serverId },
    ),
    { nickname },
);

export const deleteWhitelistNickname = (serverId, whitelistId) => axios.delete(
    route(
        'delete.minecraftServer.whitelist',
        {
            minecraftServer: serverId,
            minecraftWhitelist: whitelistId,
        },
    ),
);

export const fetchOperators = async (serverId) => {
    const response = await axios.get(
        route(
            'index.minecraftServer.operator',
            { minecraftServer: serverId },
        ),
    );

    return Array.isArray(response.data) ? response.data : [];
};

export const addOperatorNickname = (serverId, nickname) => axios.post(
    route(
        'create.minecraftServer.operator',
        { minecraftServer: serverId },
    ),
    { nickname },
);

export const deleteOperatorNickname = (serverId, operatorId) => axios.delete(
    route(
        'delete.minecraftServer.operator',
        {
            minecraftServer: serverId,
            minecraftOperator: operatorId,
        },
    ),
);

export const fetchMinecraftServerAdmins = async (serverId) => {
    const response = await axios.get(
        route(
            'index.minecraftServer.admin',
            { minecraftServer: serverId },
        ),
    );

    if (!Array.isArray(response.data)) {
        return [];
    }

    return response.data.map((user) => ({
        id: Number(user.id),
        name: user.name,
    }));
};

export const addMinecraftServerAdmin = (serverId, userId) => axios.post(
    route(
        'store.minecraftServer.admin',
        {
            minecraftServer: serverId,
            user: userId,
        },
    ),
);

export const deleteMinecraftServerAdmin = (serverId, userId) => axios.delete(
    route(
        'delete.minecraftServer.admin',
        {
            minecraftServer: serverId,
            user: userId,
        },
    ),
);

export const fetchUsers = async ({ search = '', page = 1 } = {}) => {
    const response = await axios.get(
        route('index.user'),
        {
            params: {
                search: search || undefined,
                page,
            },
        },
    );

    const payload = response.data ?? {};

    return {
        users: Array.isArray(payload.data)
            ? payload.data.map((user) => ({
                id: Number(user.id),
                name: user.name,
            }))
            : [],
        pagination: {
            currentPage: Number(payload.current_page) || 1,
            totalPages: Number(payload.last_page) || 1,
        },
    };
};
