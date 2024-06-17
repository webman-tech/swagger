<?php

namespace Tests\Fixtures;

use OpenApi\Attributes as OA;

#[OA\Schema(required: ['int'])]
class SchemaNested
{
    #[OA\Property]
    public string $string;
    #[OA\Property]
    public int $int;
}