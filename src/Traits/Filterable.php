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
        $filters = $this->processFilters($this->withoutFilter(Request::all()));
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
            $processedKey = str_replace('---', '.', $key);
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
        return property_exists($this, 'filterable') ? $this->filterable : array_keys($this->withoutFilter(Request::all()));
    }

    /**
     * Get the filterable relations.
     *
     * @return array
     */
    protected function getFilterableRelations(): array
    {
        return property_exists($this, 'filterableRelations') ? $this->filterableRelations : array_map(function($filter){
            $explode = explode("---",$filter);
            return $explode[0];
        },array_keys($this->withoutFilter(Request::all())));
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

        $query->whereHas($relation, function ($relationQuery) use ($relationFilter, $value) {
            if (is_array($value)) {
                $relationQuery->whereIn($relationFilter, array_map('urldecode', $value));
            } else {
                $relationQuery->where($relationFilter, 'like', '%' . urldecode($value) . '%');
            }
        });

        // Eager load the related model with the filter applied
        $query->with([$relation => function ($relationQuery) use ($relationFilter, $value) {
            if (is_array($value)) {
                $relationQuery->whereIn($relationFilter, array_map('urldecode', $value));
            } else {
                $relationQuery->where($relationFilter, 'like', '%' . urldecode($value) . '%');
            }
        }]);
    }
    private function withoutFilter($filters)
    {
        unset($filters['page']);
        unset($filters["_"]);
        unset($filters["per_page"]);
        

        return $filters;
    }
}
