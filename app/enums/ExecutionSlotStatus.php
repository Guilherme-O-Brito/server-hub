<?php 

namespace App\enums;

enum ExecutionSlotStatus: string
{
    case Free = 'free';

    case Provisioning = 'provisioning';

    case Deleting = 'deleting';

    case Allocated = 'allocated';

    case Failed = 'Failed';
}