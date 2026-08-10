<template>
    <span
        class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
        :class="statusConfig.classes"
    >
        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
        {{ statusConfig.label }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
    context: {
        type: String,
        default: 'server',
    },
});

const serverStatuses = {
    provisioning: {
        label: 'Provisionando',
        classes: 'bg-amber-200 text-yellow-600',
    },
    stopped: {
        label: 'Parado',
        classes: 'bg-gray-200 text-gray-700',
    },
    starting: {
        label: 'Iniciando',
        classes: 'bg-amber-200 text-yellow-600',
    },
    running: {
        label: 'Em execução',
        classes: 'bg-green-200 text-green-600',
    },
    stopping: {
        label: 'Parando',
        classes: 'bg-amber-200 text-yellow-600',
    },
    restarting: {
        label: 'Reiniciando',
        classes: 'bg-amber-200 text-yellow-600',
    },
    failed: {
        label: 'Com erro',
        classes: 'bg-red-200 text-red-600',
    },
    deleting: {
        label: 'Excluindo',
        classes: 'bg-gray-200 text-gray-700',
    },
    delete_failed: {
        label: 'Erro ao excluir',
        classes: 'bg-red-200 text-red-600',
    },
};

const slotStatuses = {
    free: {
        label: 'Livre',
        classes: 'bg-gray-200 text-gray-700',
    },
    provisioning: {
        label: 'Provisionando',
        classes: 'bg-amber-200 text-yellow-600',
    },
    deleting: {
        label: 'Excluindo',
        classes: 'bg-gray-200 text-gray-700',
    },
    allocated: {
        label: 'Em uso',
        classes: 'bg-green-200 text-green-600',
    },
    failed: {
        label: 'Com erro',
        classes: 'bg-red-200 text-red-600',
    },
};

const fallbackStatus = {
    label: 'Desconhecido',
    classes: 'bg-gray-200 text-gray-700',
};

const statusConfig = computed(() => {
    const statuses = props.context === 'slot' ? slotStatuses : serverStatuses;

    return statuses[props.status] ?? fallbackStatus;
});
</script>
