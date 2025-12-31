<?php

namespace App\Http\Resources;

class AccommodationCollection extends PaginatedResourceCollection
{
    public $collects = AccommodationResource::class;
}
