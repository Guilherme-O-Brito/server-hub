<?php

namespace App\enums;

enum MinecraftServerStatus: string
{
    case Provisioning = 'provisioning';

    case Stopped = 'stopped';

    case Starting = 'starting';

    case Running = 'running';

    case Stopping = 'stopping';

    case Restarting = 'restarting';

    case Failed = 'failed';

    case Deleting = 'deleting';

    case DeleteFailed = 'delete_failed';
}
