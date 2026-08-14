<?php

namespace App\Enums;

enum RotationTaskStatus: string
{
    case Open = 'open';
    case Completed = 'completed';
    case RiskAccepted = 'risk_accepted';
}
