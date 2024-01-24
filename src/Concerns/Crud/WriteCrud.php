<?php

namespace Sajadsdi\LaravelRepository\Concerns\Crud;

trait WriteCrud
{
    /**
     * Store resource.
     *
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        return $this->query()->create($data)?->toArray() ?? [];
    }

    /**
     * Update a resource by id.
     *
     * @param string|int $id
     * @param array $data
     * @return array
     */
    public function update(int|string $id, array $data): array
    {
        return $this->find($id)?->update($data) ? $this->find($id)?->toArray() ?? [] : [];
    }

    /**
     * Remove a resource by id.
     *
     * @param string|int $id
     * @return bool
     */
    public function delete(int|string $id): bool
    {
        return $this->find($id)?->delete() ?? false;
    }
}
