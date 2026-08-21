<?php

namespace App\Enums;

enum ShopVisitStatus: string
{
    case Collecting = 'collecting';
    case StepTwo = 'step_two';
    case Completed = 'completed';
}
