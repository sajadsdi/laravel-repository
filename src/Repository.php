<?php

namespace Sajadsdi\LaravelRepository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Sajadsdi\LaravelRepository\Concerns\Filterable;
use Sajadsdi\LaravelRepository\Concerns\Joinable;
use Sajadsdi\LaravelRepository\Concerns\Searchable;
use Sajadsdi\LaravelRepository\Concerns\Sortable;

/**
 * Abstract repository base class to standardize and centralize the common functionality of repositories.
 * This class should be extended by other specific repository classes that handle business logic related to the database interactions.
 */
abstract class Repository
{
    use Joinable, Filterable, Sortable, Searchable;

    /**
     * The name of the database connection that should be used by the model.
     * @var string $connection
     */
    protected string $connection;

    /**
     * The Model instance. This will be used to interact with the database.
     * @var null|Model
     */
    private ?Model $model;

    /**
     * The Builder instance. It helps in building complex SQL queries.
     * @var null|Builder
     */
    private ?Builder $query;

    /**
     * Fetch the corresponding model class name.
     *
     * @return string Model class name (e.g., User::class)
     */
    abstract public function getModelName(): string;

    /**
     * Fetch the searchable columns for the model. This is used for search functionality.
     *
     * @return array An array of searchable columns (e.g., ['id','name','email'])
     */
    abstract public function getSearchable(): array;

    /**
     * Fetch the filterable columns for the model. This is used for filtering functionality.
     *
     * @return array An array of filterable columns (e.g., ['id','name','email'])
     */
    abstract public function getFilterable(): array;

    /**
     * Fetch the sortable columns for the model. This is used for sorting functionality.
     *
     * @return array An array of sortable columns (e.g., ['id','name','email'])
     */
    abstract public function getSortable(): array;

    /**
     * Repository constructor.
     * Initializes the repository by setting up the database connection.
     */
    public function __construct()
    {
        $this->setConnection();
    }

    /**
     * Gets the model instance via a singleton pattern.
     *
     * @return Model The Model instance
     */
    private function model(): Model
    {
        if (!(isset($this->model) && $this->model)) {
            $name        = $this->getModelName();
            $this->model = new $name();
        }
        return $this->model;
    }

    /**
     * Retrieves the fillable attributes of the model.
     *
     * @return array The fillable attributes of the model
     */
    public function getFillable(): array
    {
        return $this->model()->getFillable();
    }

    /**
     * Sets the database connection name to the model.
     *
     * @return void
     */
    private function setConnection(): void
    {
        if (isset($this->connection)) {
            $this->model()->setConnection($this->connection);
        }
    }

    /**
     * Gets or creates a new query builder instance for the model.
     *
     * @return Builder The query builder instance
     */
    protected function query(): Builder
    {
        if (!(isset($this->query) && $this->query)) {
            $this->setQuery($this->model()->query());
        }
        return $this->query;
    }

    /**
     * Sets the query property with a given Builder instance.
     * @param Builder $query The query Builder instance
     *
     * @return void
     */
    private function setQuery(Builder $query): void
    {
        $this->query = $query;
    }

    /**
     * Reset the query builder instance.
     * This method clears out the existing query builder and its conditions to start with a fresh builder.
     *
     * @return void
     */
    protected function resetQuery(): void
    {
        $this->query   = null;
        $this->joins   = [];
        $this->selects = [];
    }

    /**
     * Overloads method calls to enable method forwarding to
     * the model instance and query builder dynamically.
     * This allows the use of model's scopes and
     * builder methods fluidly.
     * @param string $name of the method being called
     * @param array $arguments passed to the method
     *
     * @return Collection|Model|static|mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        $query = $this->query()->{$name}(...$arguments);

        if ($query instanceof Builder) {
            $this->setQuery($query);

            return $this;
        } else {
            $this->resetQuery();
        }

        return $query;
    }

    /**
     * Paginates the result set according to the given page size.
     * @param int $perPage The number of items per page
     *
     * @return LengthAwarePaginator The paginator instance
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage)->appends(['search' => $this->search, 'filter' => $this->filter, 'sort' => $this->sort]);
    }

    /**
     * Paginate the given query into a simple paginator.
     * @param int $perPage The number of items per page
     *
     * @return Paginator The paginator instance
     */
    public function simplePaginate(int $perPage = 15): Paginator
    {
        return $this->query()->simplePaginate($perPage)->appends(['search' => $this->search, 'filter' => $this->filter, 'sort' => $this->sort]);
    }


    /**
     * @param string $column
     * @return string
     */
    private function getColumn(string $column): string
    {
        if (!str_contains($column, '.')) {
            $column = $this->model()->getTable() . '.' . $column;
        }

        return $column;
    }

}
