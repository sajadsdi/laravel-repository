<?php

namespace Sajadsdi\LaravelRepository\Contracts;

interface ReadCrudRepositoryInterface
{
    public function read(string|int $id): array;

    public function index(string $search = null, string $filter = null, string $sort = null, int $perPage = 15): array;
}
