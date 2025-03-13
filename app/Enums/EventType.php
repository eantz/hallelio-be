<?php

namespace App\Enums;

enum EventType: string
{
    case SundayService = 'sunday_service';
    case KidsSundayService = 'kids_sunday_service';
    case CellGroup = 'cell_group';
    case Community = 'community';
}
