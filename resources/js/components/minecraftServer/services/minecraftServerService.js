import axios from 'axios';
import { route } from 'ziggy-js';

const hasOwn = (object, key) => Object.prototype.hasOwnProperty.call(object, key);

const normalizeServer = (server, current = {}) => {
    const value = (key, fallback = null) => (
        hasOwn(server, key) ? server[key] : fallback
    );

    const ownerId = value('owner_id', current.ownerId);

    return {
        ...current,
        id: server.id == null ? current.id : Number(server.id),
        ownerId: ownerId == null ? null : Number(ownerId),
        name: value('server_name', current.name),
        motd: value('motd', current.motd),
        difficulty: value('difficulty', current.difficulty),
        version: value('version', current.version),
        forceGamemode: Boolean(value('force_gamemode', current.forceGamemode ?? false)),
        allowFlight: Boolean(value('allow_flight', current.allowFlight ?? false)),
        accessRole: value('access_role', current.accessRole),
        status: value('status', current.status),
        lastError: value('last_error', current.lastError),
        executionSlot: value('execution_slot', current.executionSlot),
        players: value('players', current.players),
        resources: value('resources', value('metrics', current.resources)),
    };
};

export const unwrapMinecraftServerUpdate = (payload) => {
    if (Array.isArray(payload)) {
        return payload[0] ?? null;
    }

    if (payload && hasOwn(payload, '0')) {
        return payload[0];
    }

    return payload?.server ?? payload ?? null;
};

export const mergeMinecraftServerUpdate = (payload, current, viewerId) => {
    const server = unwrapMinecraftServerUpdate(payload);

    if (!server || server.id == null) {
        return null;
    }

    const updated = normalizeServer(server, current);

    if (!hasOwn(server, 'access_role')) {
        if (Number(updated.ownerId) === Number(viewerId)) {
            updated.accessRole = 'owner';
        } else if (Array.isArray(server.admins)) {
            updated.accessRole = server.admins.some(
                (admin) => Number(admin.id) === Number(viewerId),
            ) ? 'admin' : null;
        }
    }

    return updated;
};

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
