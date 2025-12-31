<?php

namespace App\Http\Resources;

class UserCollection extends PaginatedResourceCollection
{
    public $collects = UserResource::class;
}
