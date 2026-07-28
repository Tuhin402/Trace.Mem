<?php

namespace App\Enums;

enum CatalogPlanStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
