<?php

namespace App\Http\Resources;

class ActivityCollection extends PaginatedResourceCollection
{
    public $collects = ActivityResource::class;
}
