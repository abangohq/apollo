<?php

namespace App\Enums;

enum Status: string
{
    case SUCCESSFUL = 'successful';
    case PROCESSING = 'processing';
    case FAILED = 'failed';
    case REJECTED = 'rejected';
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case IDLE = 'idle';
}
