<?php

namespace Sajadsdi\LaravelRepository\Concerns\Crud;

trait ReadCrud
{
    /**
     * Get single resource by id.
     *
     * @param string|int $id
     * @return array
     */
    public function read(int|string $id): array
    {
        return $this->find($id)?->toArray() ?? [];
    }

    /**
     * Get all resource.
     *
     * @param int $perPage
     * @param string|null $search
     * @param string|null $filter
     * @param string|null $sort
     * @return array
     */
    public function index(string $search = null, string $filter = null, string $sort = null, int $perPage = 15): array
    {
        return $this->search($search)->filter($filter)->sort($sort)->simplePaginate($perPage)->toArray();
    }
}
