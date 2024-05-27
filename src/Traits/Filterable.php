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
        $filters = Request::all();
        $filterable = $this->getFilterableAttributes($filters);

        foreach ($filters as $filter => $value) {
            if (in_array($filter, $filterable)) {
                $this->applyFilter($query, $filter, $value);
            }
        }

        return $query;
    }

    /**
     * Get the filterable attributes.
     *
     * @param array $filters
     * @return array
     */
    protected function getFilterableAttributes(array $filters): array
    {
        return property_exists($this, 'filterable') ? $this->filterable : array_keys($filters);
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
            $query->whereIn($filter, $value);
        } else {
            $query->where($filter, 'like', '%' . urldecode($value) . '%');
        }
    }
}
