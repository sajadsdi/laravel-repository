<?php

namespace Sajadsdi\LaravelRepository\Concerns;

trait Filterable
{
    use Joinable;

    /**
     * Filter query string for advanced filtering options.
     * @var null|string $filter
     */
    private ?string $filter = null;

    /**
     * Set filters on the given query based on a query string.
     * It can interpret complex filtering commands encapsulated within the string,
     * and applies them to the current Builder instance.
     *
     * The filter string uses a special syntax, such as:
     * - "id:equal_1" for equality.
     * - "price:between_100,200" for range filtering.
     * - "status:is_null" for checking if a column is NULL.
     * - "status:is_not-null" for checking if a column is NOT NULL,
     * - "id:in_2,3,4" for checking if a column value equal 2 or 3 or 4.
     * - "price:upper_500" column value upper min range filter.
     * - "price:lower_500" column value lower max range filter.
     * - "full_name:like_john" for use like filter.
     * - "id:not_in_2,3,4" for checking if a column value not equal 2 and 3 and 4.
     * - "id:not_between_2,6" for checking if a column is not in range 2 to 6.
     * - "name:not_like_john" for like condition.
     * - "id:not_equal_2" for not equal condition.
     * - "price:not_upper_100" column value not upper min range filter.
     * - "price:not_lower_200" column value not lower max range filter.
     * - "@" is used for separating multiple filter conditions.
     *
     * @param ?string $query The filter string defining all filters to be applied.
     * @param int $limit The maximum number of separate filter conditions to.
     * @param array $filterable the filterable columns.
     * accept from the filter string.
     *
     * @return static The repository instance for method chaining.
     */
    public function filter(string $query = null, int $limit = 5, array $filterable = []): static
    {
        if ($query) {
            $this->filter = $query;
            $filters      = explode('@', $query);

            if (!$filterable) {
                $filterable = $this->getFilterable();
            }

            foreach ($filters as $i => $filter) {
                $eFilter = explode(':', $filter);
                $column  = $eFilter[0];

                if (isset($eFilter[1])) {
                    if (in_array($column, $filterable)) {
                        $this->setFullFilter($column, $eFilter[1]);
                    } else {
                        $this->setJoinFilter($column, $eFilter[1]);
                    }
                }

                if ($i + 1 == $limit) {
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * Interprets and applies advanced filter functions to the query.
     * It understands prefixes like 'in_*,*' , 'is_null' , 'not_in_*,*',
     * 'not_like_*' , 'not_between_min,max' , and 'not_equal_*'.
     * to apply more complex filters based on the filter and its arguments.
     *
     * @param string $column The database column to which the filter should be applied.
     * @param string $query filter query.
     * name and the remaining elements are the arguments.
     *
     * @return void
     */
    private function setFullFilter(string $column, string $query): void
    {
        $column = $this->getColumn($column);

        $filter = explode('_', $query);

        if (count($filter) > 1) {
            switch ($filter[0]) {

                // e.g. "equal_500"
                case 'equal':
                    $this->where($column, $filter[1]);
                    break;

                // e.g. "like_lorem"
                case 'like':
                    $this->where($column, 'LIKE', '%' . $filter[1] . '%');
                    break;

                // e.g. "between_500,1000"
                case 'between':
                    $this->setBetweenFilter($column, ...explode(',', $filter[1]));
                    break;

                // e.g. "in_2,3,4"
                case 'in':
                    $this->whereIn($column, explode(',', $filter[1]));
                    break;

                // e.g. "upper_500"
                case 'upper':
                    $this->where($column, '>', $filter[1]);
                    break;

                // e.g. "lower_500"
                case 'lower':
                    $this->where($column, '<', $filter[1]);
                    break;

                // e.g. "is_null" or "is_not-null"
                case 'is':
                    switch ($filter[1]) {
                        case 'null':
                            $this->whereNull($column);
                            break;
                        case 'not-null':
                            $this->whereNotNull($column);
                            break;
                    }
                    break;

                // e.g. "not_in_2,3,4" or "not_like_john" or "not_between_2,8" or "not_equal_100" or "not_upper_500" or "not_lower_400"
                case 'not':
                    if (isset($filter[2])) {
                        switch ($filter[1]) {
                            case 'in':
                                $this->whereNotIn($column, explode(',', $filter[2]));
                                break;
                            case 'like':
                                $this->where($column, 'NOT LIKE', '%' . $filter[2] . '%');
                                break;
                            case 'between':
                                $this->setNotBetweenFilter($column, ...explode(',', $filter[2]));
                                break;
                            case 'equal' :
                                $this->where($column, '!=', $filter[2]);
                                break;
                            case 'upper':
                                $this->whereNot($column, '>', $filter[2]);
                                break;
                            case 'lower':
                                $this->whereNot($column, '<', $filter[2]);
                                break;
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Applies a range filter to the query, where the $column's value is
     * expected to be within the inclusive range of $min and $max.
     *
     * @param string $column The column to apply the range filter on.
     * @param string|null $min The minimum value of the range.
     * @param string|null $max The maximum value of the range.
     *
     * @return void
     */
    private function setBetweenFilter(string $column, string $min = null, string $max = null): void
    {
        if (!empty($min) || $min === 0) {
            $this->where($column, '>=', $min);
        }

        if (!empty($max) || $max === 0) {
            $this->where($column, '<=', $max);
        }
    }

    /**
     * Applies a range filter to the query, where the $column's value is Not
     * expected to be within the inclusive range of $min and $max.
     *
     * @param string $column The column to apply the range filter on.
     * @param string|null $min The minimum value of the range.
     * @param string|null $max The maximum value of the range.
     *
     * @return void
     */
    private function setNotBetweenFilter(string $column, string $min = null, string $max = null): void
    {
        if (!empty($min) || $min === 0) {
            $this->whereNot($column, '>=', $min);
        }

        if (!empty($max) || $max === 0) {
            $this->whereNot($column, '<=', $max);
        }
    }
}
