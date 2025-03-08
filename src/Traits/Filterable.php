<?php
namespace Usermp\LaravelFilter\Traits;

use Illuminate\Support\Facades\Request;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

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

        $relations  = array_map(function($relation){
            return $this->replaceRelation($relation);
        },$this->getFilterableRelations());

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

            $processedKey = $this->replaceRelation($key);
            $processedFilters[$processedKey] = $value;

        }
        return $processedFilters;
    }

    public function replaceRelation($filter){
        $processedKey = preg_replace('/---/', '.', $filter);
        $processedKey = preg_replace('/--/', '.', $processedKey);
        $processedKey = preg_replace('/__/', '_', $processedKey);
        return $processedKey;
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
            $explode = explode(".",$filter);
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

        return strpos($filter, '.') !== false;
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
                    Log::info($v);
                    if ($key === "equal") {
                        $query->where($filter, urldecode($v));
                    } elseif ($key === "gte") {
                        Log::info($v);
                        $query->where($filter, '>=', urldecode($v));
                    } elseif ($key === "lte") {
                        $query->where($filter, '<=', urldecode($v));
                    } else {
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
        $relations = explode('.', $filter);
        $lastAttribute = array_pop($relations);
        $relationPath = implode('.', $relations);
    
        $query->whereHas($relationPath, function ($relationQuery) use ($lastAttribute, $value) {
            if (is_array($value)) {
                $relationQuery->where(function ($query) use ($lastAttribute, $value) {
                    foreach ($value as $key => $v) {
                        $key = str_replace("'","",$key);
                        if ($key == "equal") {
                            $query->where($lastAttribute, urldecode($v));
                        } elseif ($key == "gte") {
                            $query->where($lastAttribute, '>=', urldecode($v));
                        } elseif ($key == "lte") {
                            $query->where($lastAttribute, '<=', urldecode($v));
                        } else {
                            $query->orWhere($lastAttribute, 'like', '%' . urldecode($v) . '%');
                        }
                    }
                });
            } else {
                $relationQuery->where($lastAttribute, 'like', '%' . urldecode($value) . '%');
            }
        });
    
        $query->with([$relationPath => function ($relationQuery) use ($lastAttribute, $value) {
            if (is_array($value)) {
                $relationQuery->where(function ($query) use ($lastAttribute, $value) {
                    foreach ($value as $key => $v) {
                        if ($key == "equal") {
                            $query->where($lastAttribute, urldecode($v));
                        } elseif ($key == "gte") {
                            $query->where($lastAttribute, '>=', urldecode($v));
                        } elseif ($key == "lte") {
                            $query->where($lastAttribute, '<=', urldecode($v));
                        } else {
                            $query->orWhere($lastAttribute, 'like', '%' . urldecode($v) . '%');
                        }
                    }
                });
            } else {
                $relationQuery->where($lastAttribute, 'like', '%' . urldecode($value) . '%');
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
