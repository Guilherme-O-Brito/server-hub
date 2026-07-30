import axios from 'axios';
import { route } from 'ziggy-js';
import { Ziggy } from '../../../ziggy';

const csrfPayload = (payload = {}) => ({
    _token: document.querySelector('input[name="_token"]')?.value ?? '',
    ...payload,
});

const normalizeServer = (server) => ({
    id: Number(server.id),
    name: server.server_name,
    motd: server.motd,
    difficulty: server.difficulty,
    version: server.version ?? null,
    forceGamemode: Boolean(server.force_gamemode),
    allowFlight: Boolean(server.allow_flight),
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
            undefined,
            Ziggy,
        ),
    );

    return normalizeServer(response.data);
};

export const fetchMinecraftVersions = async () => {
    const response = await axios.get(
        route('index.minecraftVersion', undefined, undefined, Ziggy),
    );

    return Array.isArray(response.data) ? response.data : [];
};

export const updateMinecraftServer = (serverId, payload) => axios.put(
    route(
        'update.minecraftServer',
        { minecraftServer: serverId },
        undefined,
        Ziggy,
    ),
    csrfPayload(payload),
);

export const startMinecraftServer = (serverId) => axios.post(
    route(
        'start.minecraftServer',
        { minecraftServer: serverId },
        undefined,
        Ziggy,
    ),
    csrfPayload(),
);

export const stopMinecraftServer = (serverId) => axios.post(
    route(
        'stop.minecraftServer',
        { minecraftServer: serverId },
        undefined,
        Ziggy,
    ),
    csrfPayload(),
);

export const fetchWhitelist = async (serverId) => {
    const response = await axios.get(
        route(
            'index.minecraftServer.whitelist',
            { minecraftServer: serverId },
            undefined,
            Ziggy,
        ),
    );

    return Array.isArray(response.data) ? response.data : [];
};

export const addWhitelistNickname = (serverId, nickname) => axios.post(
    route(
        'create.minecraftServer.whitelist',
        { minecraftServer: serverId },
        undefined,
        Ziggy,
    ),
    csrfPayload({ nickname }),
);

export const fetchOperators = async (serverId) => {
    const response = await axios.get(
        route(
            'index.minecraftServer.operator',
            { minecraftServer: serverId },
            undefined,
            Ziggy,
        ),
    );

    return Array.isArray(response.data) ? response.data : [];
};

export const addOperatorNickname = (serverId, nickname) => axios.post(
    route(
        'create.minecraftServer.operator',
        { minecraftServer: serverId },
        undefined,
        Ziggy,
    ),
    csrfPayload({ nickname }),
);
