<?php

namespace Sajadsdi\LaravelRepository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Abstract repository base class to standardize and centralize the common functionality of repositories.
 * This class should be extended by other specific repository classes that handle business logic related to the database interactions.
 */
abstract class Repository
{
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
     * Search term for filtering the list of models.
     * @var null|string $search
     */
    private ?string $search = null;

    /**
     * Filter query string for advanced filtering options.
     * @var null|string $filter
     */
    private ?string $filter = null;

    /**
     * Sort query string for sorting the list of models.
     * @var null|string $sort
     */
    private ?string $sort = null;

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
    private function setConnection()
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
            $this->setQuery($this->model()->newQuery());
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
     * Resets the query Builder instance.
     *
     * @return void
     */
    protected function resetQuery()
    {
        $this->query = null;
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
     * Adds search functionality to the query based on searchable column definitions.
     * @param ?string $search The search term
     *
     * @return static The repository instance for method chaining
     */
    public function search(string $search = null): static
    {
        if ($search) {
            $this->search = $search;

            foreach ($this->getSearchable() as $column) {
                $this->orWhere($column, 'LIKE', "%" . $search . "%");
            }
        }

        return $this;
    }

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

                if (isset($eSort[1]) && in_array($column, $sortable)) {
                    $s = strtoupper($eSort[1]);
                    if ($s == 'ASC' || $s == 'DESC') {
                        $this->orderBy($column, $s);
                    }
                }

                if($i+1 == $limit){
                    break;
                }
            }
        }

        return $this;
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
     * Set filters on the given query based on a query string.
     * It can interpret complex filtering commands encapsulated within the string,
     * and applies them to the current Builder instance.
     *
     * The filter string uses a special syntax, such as:
     * - "id:1" for equality.
     * - "price:100_200" for range filtering.
     * - "status:f_is_null" for checking if a column is NULL.
     * - "status:f_is_not-null" for checking if a column is NOT NULL,
     * - "id:f_in_2,3,4" for checking if a column value equal 2 or 3 or 4.
     * - "id:f_not_in_2,3,4" for checking if a column value not equal 2 and 3 and 4.
     * - "id:f_not_between_2,6" for checking if a column is not in range 2 to 6.
     * - "name:f_not_like_john" for like condition.
     * - "id:f_not_equal_2" for not equal condition.
     * - "@" is used for separating multiple filter conditions.
     *
     * @param ?string $query The filter string defining all filters to be applied.
     * @param int $limit The maximum number of separate filter conditions to
     * accept from the filter string.
     *
     * @return static The repository instance for method chaining.
     */
    public function filter(string $query = null, int $limit = 5): static
    {
        if ($query) {
            $this->filter = $query;
            $filters      = explode('@', $query);
            $filterable   = $this->getFilterable();

            foreach ($filters as $i => $filter) {
                $eFilter = explode(':', $filter, 2);
                $column  = $eFilter[0];

                if (isset($eFilter[1]) && in_array($column, $filterable)) {
                    $qry = explode('_', $eFilter[1]);
                    $this->setFullFilter($column, $qry);
                }

                if ($i + 1 == $limit) {
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * Applies multiple filters to the query as defined by the interaction of the $column
     * argument and $filter array.
     *
     * with following indices representing the values or conditions for the filter.
     *
     * @param string $column The column to apply the filters on.
     * @param array $filter The array containing the filter
     * key and associated values.
     *
     * @return void
     */
    private function setFullFilter(string $column, array $filter)
    {
        $count = count($filter);
        if ($count) {
            switch ($count) {
                case 1:
                    $this->setEqualFilter($column, $filter[0]);
                    break;
                case 2:
                    $this->setBetweenFilter($column, $filter[0], $filter[1]);
                    break;
                default:
                    $this->setFilterFunction($column, $filter);
            }
        }
    }

    /**
     * Applies a simple equality filter to the query, where the $column is
     * expected to be equal with the $equal value.
     *
     * @param string $column The column to apply the equality filter on.
     * @param string $equal The value to which the column is compared.
     *
     * @return void
     */
    private function setEqualFilter(string $column, string $equal): void
    {
        $this->where($column, $equal);
    }

    /**
     * Applies a range filter to the query, where the $column's value is
     * expected to be within the inclusive range of $min and $max.
     *
     * @param string $column The column to apply the range filter on.
     * @param string $min The minimum value of the range.
     * @param string $max The maximum value of the range.
     *
     * @return void
     */
    private function setBetweenFilter(string $column, string $min, string $max): void
    {
        $this->where($column, '>=', $min);
        $this->where($column, '<=', $max);
    }

    /**
     * Interprets and applies advanced filter functions to the query.
     * It understands prefixes like 'f_in_*,*' , 'f_is_null' , 'f_not_in_*,*',
     * 'f_not_like_*' , 'f_not_between_min,max' , and 'f_not_equal_*'.
     * to apply more complex filters based on the filter function and its arguments.
     *
     * @param string $column The database column to which the filter should be applied.
     * @param array $filter An array where the first element is the filter function
     * name and the remaining elements are the arguments.
     *
     * @return void
     */
    private function setFilterFunction(string $column, array $filter)
    {
        if (count($filter) >= 3 && $filter[0] == 'f') {

            switch ($filter[1]) {
                // Like "f_in_2,3,4"
                case 'in':
                    $this->whereIn($column, explode(',', $filter[2]));
                    break;

                // Like "f_is_null" or "f_is_not-null"
                case 'is':
                    switch ($filter[2]) {
                        case 'null':
                            $this->whereNull($column);
                            break;
                        case 'not-null':
                            $this->whereNotNull($column);
                            break;
                    }
                    break;

                // Like "f_not_in_2,3,4" or "f_not_like_john" or "f_not_between_2,8" or "f_not_equal_100"
                case 'not':
                    if (isset($filter[3])) {
                        switch ($filter[2]) {
                            case 'in':
                                $this->whereNotIn($column, explode(',', $filter[3]));
                                break;
                            case 'like':
                                $this->where($column, 'NOT LIKE', '%' . $filter[3] . '%');
                                break;
                            case 'between':
                                if (count($between = explode(',', $filter[3])) >= 2) {
                                    $this->whereNotBetween($column, [$between[0], $between[1]]);
                                }
                                break;
                            case 'equal' :
                                $this->where($column, '!=', $filter[3]);
                        }
                    }
                    break;
            }
        }
    }

}
