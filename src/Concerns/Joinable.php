<?php

namespace Sajadsdi\LaravelRepository\Concerns;

use Illuminate\Support\Facades\DB;

trait Joinable
{
    /**
     * define join relation config for models.
     * @var array $sort
     */
    protected array $joinable = [];

    /**
     * Stored relations.
     * @var array
     */
    private array $relations = [];

    /**
     * Stored aliases.
     * @var array
     */
    private array $aliases = [];

    /**
     * Stored joined tables.
     * @var array
     */
    private array $joins = [];

    /**
     * store all selects on joins.
     * @var array
     */
    private array $selects = [];

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

        if(!$joinable){
            return $this;
        }

        $rels  = $this->getRelation($relation);

        $count = count($rels);

        if ($count >= 1) {
            $query = $this->query();

            if ($select = $this->getMergedSelects($joinable[$relation]['select'] ?? [])) {
                $query->select($select);
            }

            $rels[0]['tables'][0] = $this->model()->getTable();

            for ($i = 0; $i < $count; $i++) {

                if (!in_array($rels[$i]['tables'][1], $this->joins)) {

                    $this->joins[] = $rels[$i]['tables'][1];

                    $query->join($rels[$i]['tables'][1],
                        $rels[$i]['tables'][0] . '.' . $rels[$i]['keys'][0],
                        '=',
                        $rels[$i]['tables'][1] . '.' . $rels[$i]['keys'][1],
                        $joinable[$relation]['join_type'] ?? 'inner'
                    );

                    $softDelete = $joinable[$relation]['soft_delete'] ?? [];

                    if (is_array($softDelete)) {
                        if ($i != 0 && in_array($rels[$i]['tables'][0], $softDelete)) {
                            $query->whereNull($rels[$i]['tables'][0] . '.' . 'deleted_at');
                        }

                        if (in_array($rels[$i]['tables'][1], $softDelete)) {
                            $query->whereNull($rels[$i]['tables'][1] . '.' . 'deleted_at');
                        }
                    }

                    $this->setQuery($query);
                }
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
     * that include pairs of tables and their respective keys to be used in join clauses. The method ensures
     * that each defined relationship adheres to the expected [table1.field1 => table2.field2] syntax and transforms
     * it into a standardized array structure convenient for iterating over when building join clauses.
     *
     * @param string $name relation name defined on joinable
     * @return array An array of associations, where each association is an array with 'tables' and 'keys' keys.
     *               Each 'tables' array contains two table names, and each 'keys' array contains the respective fields for joining.
     */
    protected function getRelation(string $name): array
    {
        if (isset($this->relations[$name])) {
            return $this->relations[$name];
        }

        $relation = [];

        $joinable = $this->getJoinable();

        if (isset($joinable[$name]['rel'])) {

            foreach ($joinable[$name]['rel'] as $table1 => $table2) {
                $eTable1 = explode('.', $table1);
                $eTable2 = explode('.', $table2);

                if (!empty($eTable1[0]) && !empty($eTable1[1]) && !empty($eTable2[0]) && !empty($eTable2[1])) {
                    $relation[] = [
                        'tables' => [$eTable1[0], $eTable2[0]],
                        'keys'   => [$eTable1[1], $eTable2[1]]
                    ];
                }
            }

            if ($relation) {
                $this->relations[$name] = $relation;
            }

        }

        return $relation;
    }

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
        $relation = $this->getRelation($eColumn[0]);

        if ($relation) {
            $joinable = $this->getJoinable();

            if (!empty($eColumn[1]) && !empty($joinable[$eColumn[0]]['filterable']) && in_array($eColumn[1], $joinable[$eColumn[0]]['filterable'])) {

                $this->join($eColumn[0]);

                if ($this->joins) {
                    $aliases = $this->getAliases($eColumn[0]);

                    $filterColumn = $aliases[$eColumn[1]] ?? $relation[count($relation) - 1]['tables'][1] . '.' . $eColumn[1];

                    $this->setFullFilter($filterColumn, $filter);
                }
            }
        }
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
        $relation = $this->getRelation($eColumn[0]);

        if ($relation) {
            $joinable = $this->getJoinable();

            if (!empty($eColumn[1]) && !empty($joinable[$eColumn[0]]['sortable']) && in_array($eColumn[1], $joinable[$eColumn[0]]['sortable'])) {

                $this->join($eColumn[0]);

                if ($this->joins) {
                    $aliases = $this->getAliases($eColumn[0]);

                    $filterColumn = $aliases[$eColumn[1]] ?? $relation[count($relation) - 1]['tables'][1] . '.' . $eColumn[1];

                    $this->orderBy($filterColumn, $filter);
                }
            }
        }
    }

    /**
     * Get defined aliases.
     *
     * @param string $relation
     * @return array
     */
    private function getAliases(string $relation): array
    {
        if (isset($this->aliases[$relation])) {
            return $this->aliases[$relation];
        }

        $aliases  = [];
        $joinable = $this->getJoinable();

        if (isset($joinable[$relation]['select']) && is_array($joinable[$relation]['select'])) {

            foreach ($joinable[$relation]['select'] as $select) {
                $select  = str_replace(' AS ', ' as ', $select);
                $eSelect = explode(' as ', $select);
                if (count($eSelect) == 2) {
                    $aliases[$eSelect[1]] = $eSelect[0];
                }
            }

            if ($aliases) {
                $this->aliases[$relation] = $aliases;
            }
        }

        return $aliases;
    }

    /**
     * Get selected columns merged with old selected columns.
     *
     * @param array $selects
     * @return array
     */
    private function getMergedSelects(array $selects = []): array
    {
        $this->setModelSelects();

        foreach ($selects as $select) {
            $this->setSelect(str_replace(' AS ', ' as ', $select));
        }

        return $this->selects;
    }

    /**
     * Set a column on selected columns.
     *
     * @param mixed $select
     * @return void
     */
    private function setSelect(mixed $select): void
    {
        if (!in_array($select, $this->selects)) {
            $this->selects[] = $select;
        }
    }

    /**
     * Get base table visible columns.
     *
     * @return array
     */
    protected function getModelSelects(): array
    {
        $table         = $this->model()->getTable();
        $visibleColumn = array_diff(DB::getSchemaBuilder()->getColumnListing($table), $this->model()->getHidden(), ['deleted_at']);
        $cols          = [];

        foreach ($visibleColumn as $col) {
            $cols[] = $table . '.' . $col;
        }

        return $cols;
    }

    /**
     * Set base table select columns.
     *
     * @return void
     */
    protected function setModelSelects(): void
    {
        if (!$this->selects) {
            $this->selects = $this->getModelSelects();
        }
    }

    /**
     * Apply multiple joins on query.
     *
     * @param array $relations
     * @return $this
     */
    public function joins(array $relations): static
    {
        foreach ($relations as $relation) {
            if (is_string($relation)) {
                $this->join($relation);
            }
        }

        return $this;
    }

}
