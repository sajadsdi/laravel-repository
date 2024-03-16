<?php

namespace Sajadsdi\LaravelRepository\Concerns;

trait Sortable
{
    use Joinable;

    /**
     * Sort query string for sorting the list of models.
     * @var null|string $sort
     */
    private ?string $sort = null;

    /**
     * Adds sorting functionality to the query based on sortable column definitions.
     * @param ?string $query The sorting term (e.g., "id:desc" or "name:asc@id:desc").
     * @param int $limit for multiple sorting.
     *
     * @return static The repository instance for method chaining
     */
    public function sort(string $query = null, int $limit = 2): static
    {
        if ($query) {
            $this->sort = $query;
            $sorts      = explode('@', $query);
            $sortable   = $this->getSortable();

            foreach ($sorts as $i => $sort) {
                $eSort  = explode(':', $sort, 2);
                $column = $eSort[0];

                if(!isset($eSort[1])){
                    return $this;
                }

                $eSort[1] = strtoupper($eSort[1]);

                if ($eSort[1] == 'ASC' || $eSort[1] == 'DESC') {
                    if (in_array($column, $sortable)) {
                        $this->orderBy($this->getColumn($column), $eSort[1]);
                    } else {
                        $this->setJoinSort($column, $eSort[1]);
                    }
                }

                if ($i + 1 == $limit) {
                    break;
                }
            }
        }

        return $this;
    }
}
