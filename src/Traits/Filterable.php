<?php
namespace Usermp\LaravelFilter\Traits;

use Illuminate\Support\Facades\Request;
use Illuminate\Contracts\Database\Eloquent\Builder;

trait Filterable
{
    /**
     * Apply filters to the query.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeFilter(Builder $query): Builder
    {
        $filters = $this->processFilters(Request::all());
        $filterable = $this->getFilterableAttributes();
        $relations = $this->getFilterableRelations();

        foreach ($filters as $filter => $value) {
            if ($this->isRelationFilter($filter, $relations)) {
                $this->applyRelationFilter($query, $filter, $value);
            } elseif (in_array($filter, $filterable)) {
                $this->applyFilter($query, $filter, $value);
            }
        }

        return $query;
    }

    /**
     * Process filters by replacing underscores with dots in the keys.
     *
     * @param array $filters
     * @return array
     */
    protected function processFilters(array $filters): array
    {
        $processedFilters = [];
        foreach ($filters as $key => $value) {
            $processedKey = str_replace('_', '.', $key);
            $processedFilters[$processedKey] = $value;
        }
        return $processedFilters;
    }

    /**
     * Get the filterable attributes.
     *
     * @return array
     */
    protected function getFilterableAttributes(): array
    {
        return property_exists($this, 'filterable') ? $this->filterable : array_keys(Request::all());
    }

    /**
     * Get the filterable relations.
     *
     * @return array
     */
    protected function getFilterableRelations(): array
    {
        return property_exists($this, 'filterableRelations') ? $this->filterableRelations : array_keys(Request::all());
    }

    /**
     * Check if the filter is for a relation.
     *
     * @param string $filter
     * @param array $relations
     * @return bool
     */
    protected function isRelationFilter(string $filter, array $relations): bool
    {
        return strpos($filter, '.') !== false && in_array(explode('.', $filter)[0], $relations);
    }

    /**
     * Apply a single filter to the query.
     *
     * @param Builder $query
     * @param string $filter
     * @param mixed $value
     * @return void
     */
    protected function applyFilter(Builder $query, string $filter, $value): void
    {
        if (is_array($value)) {
            $query->where(function ($query) use ($filter, $value) {
                foreach ($value as $key => $v) {
                    if($key == "equal"){
                        $query->where($filter,  urldecode($v));
                    }else{
                        $query->orWhere($filter, 'like', '%' . urldecode($v) . '%');
                    }
                }
            });
        } else {
            $query->where($filter, 'like', '%' . urldecode($value) . '%');
        }
    }

    /**
     * Apply a filter to a related model query.
     *
     * @param Builder $query
     * @param string $filter
     * @param mixed $value
     * @return void
     */
    protected function applyRelationFilter(Builder $query, string $filter, $value): void
    {
        [$relation, $relationFilter] = explode('.', $filter, 2);

        if (is_array($value)) {
            $query->whereHas($relation, function ($relationQuery) use ($relationFilter, $value) {
                $relationQuery->whereIn($relationFilter, array_map('urldecode', $value));
            })->with([$relation => function ($query) use ($relationFilter, $value) {
                $query->whereIn($relationFilter, array_map('urldecode', $value));
            }]);
        } else {
            $query->whereHas($relation, function ($relationQuery) use ($relationFilter, $value) {
                $relationQuery->where($relationFilter, 'like', '%' . urldecode($value) . '%');
            })->with([$relation => function ($query) use ($relationFilter, $value) {
                $query->where($relationFilter, 'like', '%' . urldecode($value) . '%');
            }]);
        }
    }
}
