<?php

namespace Sajadsdi\LaravelRepository\Concerns;

trait Joinable
{
    /**
     * define join relation config for models.
     * @var array $sort
     */
    protected array $joinable = [];

    /**
     * is set join query.
     * @var bool
     */
    private bool $joined = false;


    /**
     * Interprets and applies a complex filter condition to the query builder instance.
     * If joining is required for the filter condition, it will perform the necessary table join.
     *
     * @param string $column The column name to apply the filter condition to (e.g., "users.name")
     * @param string $filter The filter condition in the format 'filterType_value'
     *
     * @return void
     */
    private function setJoinFilter(string $column, string $filter): void
    {
        $eColumn  = explode('.', $column);
        $joinable = $this->getJoinable();

        if (!empty($eColumn[0]) && !empty($eColumn[1]) &&
            !empty($joinable[$eColumn[0]]['rel']) &&
            !empty($joinable[$eColumn[0]]['filterable']) &&
            is_array($joinable[$eColumn[0]]['rel']) &&
            is_array($joinable[$eColumn[0]]['filterable']) &&
            in_array($eColumn[1], $joinable[$eColumn[0]]['filterable'])
        ) {
            $relation = $this->getRelation($joinable[$eColumn[0]]['rel']);

            $this->join($eColumn[0]);

            if ($this->joined) {
                $this->setFullFilter($relation[count($relation) - 1]['tables'][1] . '.' . $eColumn[1], $filter);
            }
        }
    }

    /**
     * Perform a join operation with another table based on the relation configuration.
     * This method applies a join clause to the current Builder query instance. It uses the `joinable` property for configuration
     * to determine the table and column names for the join. Additionally, it checks and applies the soft delete state
     * if necessary to prevent including logically deleted records in the join.
     *
     * @param string $relation The relation name that defines how the join should be performed.
     *
     * @return static The current instance of the repository for method chaining after applying the join.
     */
    public function join(string $relation): static
    {
        $joinable = $this->getJoinable();

        if (!isset($joinable[$relation]['rel'])) {
            return $this;
        }

        $rels  = $this->getRelation($joinable[$relation]['rel']);
        $count = count($rels);

        if ($count >= 1) {
            $query = $this->query();

            $query->select($joinable[$relation]['select'] ?? '*');

            for ($i = 0; $i < $count; $i++) {
                $this->joined = true;

                $query->join($rels[$i]['tables'][1],
                    $rels[$i]['tables'][0] . '.' . $rels[$i]['keys'][0],
                    '=',
                    $rels[$i]['tables'][1] . '.' . $rels[$i]['keys'][1]
                );

                $softDelete = $joinable[$relation]['soft_delete'] ?? [];

                if (is_array($softDelete)) {
                    if (in_array($rels[$i]['tables'][0], $softDelete)) {
                        $query->whereNull($rels[$i]['tables'][0] . '.' . 'deleted_at');
                    }

                    if (in_array($rels[$i]['tables'][1], $softDelete)) {
                        $query->whereNull($rels[$i]['tables'][1] . '.' . 'deleted_at');
                    }
                }

                $this->setQuery($query);
            }
        }

        return $this;
    }

    /**
     * Fetch the Joinable config for the model. This is used for filtering and sorting functionality.
     *
     * @return array an array configs for model joins.
     */
    protected function getJoinable(): array
    {
        return $this->joinable;
    }

    /**
     * Resolve the table relationships required for a join operation.
     * This method interprets the 'rel' on joinable property configuration array and constructs an array of table relationships
     * that includes pairs of tables and their respective keys to be used in join clauses. The method ensures
     * that each defined relationship adheres to the expected [table1.field1 => table2.field2] syntax and transforms
     * it into a standardized array structure convenient for iterating over when building join clauses.
     *
     * @param array $rel An array defining relationships between tables, in the format ['table1.field1' => 'table2.field2'].
     *
     * @return array An array of associations, where each association is an array with 'tables' and 'keys' keys.
     *               Each 'tables' array contains two table names, and each 'keys' array contains the respective fields for joining.
     */
    protected function getRelation(array $rel): array
    {
        $relation = [];

        foreach ($rel as $table1 => $table2) {
            $eTable1 = explode('.', $table1);
            $eTable2 = explode('.', $table2);

            if (!empty($eTable1[0]) && !empty($eTable1[1]) && !empty($eTable2[0]) && !empty($eTable2[1])) {
                $relation[] = [
                    'tables' => [$eTable1[0], $eTable2[0]],
                    'keys'   => [$eTable1[1], $eTable2[1]]
                ];
            }
        }

        return $relation;
    }

    /**
     * Interprets and applies a complex sort condition to the query builder instance.
     * If joining is required for the sort condition, it will perform the necessary table join.
     *
     * @param string $column The column name to apply the sort condition to (e.g., "users.created_at")
     * @param string $filter The sorting order - 'ASC' for ascending, 'DESC' for descending
     *
     * @return void
     */
    private function setJoinSort(string $column, string $filter): void
    {
        $eColumn  = explode('.', $column);
        $joinable = $this->getJoinable();

        if (!empty($eColumn[0]) && !empty($eColumn[1]) &&
            !empty($joinable[$eColumn[0]]['rel']) &&
            !empty($joinable[$eColumn[0]]['sortable']) &&
            is_array($joinable[$eColumn[0]]['rel']) &&
            is_array($joinable[$eColumn[0]]['sortable']) &&
            in_array($eColumn[1], $joinable[$eColumn[0]]['sortable'])
        ) {
            $relation = $this->getRelation($joinable[$eColumn[0]]['rel']);

            $this->join($eColumn[0]);

            if ($this->joined) {
                $this->orderBy($relation[count($relation) - 1]['tables'][1] . '.' . $eColumn[1], $filter);
            }
        }
    }
}
