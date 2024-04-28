<?php

namespace Sajadsdi\LaravelRepository\Concerns;

trait Searchable
{
    /**
     * Search term for filtering the list of models.
     * @var null|string $search
     */
    private ?string $search = null;

    /**
     * Adds search functionality to the query based on searchable column definitions.
     * @param null|string $search The search term
     *
     * @return static The repository instance for method chaining
     */
    public function search(?string $search = null): static
    {
        if ($search) {
            $this->search = $search;

            $searchable = $this->getSearchable();

            $this->where(function ($query) use ($search, $searchable) {
                for ($i = 0; $i < count($searchable); $i++) {
                    if($i == 0) {
                        $query = $query->where($searchable[$i], 'LIKE', "%" . $search . "%");
                    }else{
                        $query = $query->orWhere($searchable[$i], 'LIKE', "%" . $search . "%");
                    }
                }
            });
        }

        return $this;
    }
}
