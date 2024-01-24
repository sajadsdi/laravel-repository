<?php

namespace Sajadsdi\LaravelRepository\Contracts;

interface WriteCrudRepositoryInterface
{
    public function create(array $data): array;

    public function update(string|int $id, array $data): array;

    public function delete(string|int $id): bool;

}
