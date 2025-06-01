<?php

namespace Usermp\LaravelFilter\Traits;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

trait Filterable
{
    protected string $filterRequestKey = 'filter';

    public function scopeFilter(Builder $query, Request $request): Builder
    {
        $rawFilters = $request->input($this->getFilterRequestKey(), []);

        if (!is_array($rawFilters) || empty($rawFilters)) {
            return $query;
        }

        $processedFilters = $rawFilters;

        $allowedAttributes = $this->getFilterableAttributes();
        $allowedRelationBaseNames = $this->getFilterableRelations();

        foreach ($processedFilters as $filterKey => $value) {
            if (!isset($value) || ($value === '' && $value !== '0' && $value !== 0)) {
                continue;
            }

            $isRelation = false;
            $baseRelationName = null;

            if (strpos($filterKey, '.') !== false) {
                $baseRelationName = explode('.', $filterKey, 2)[0];
                if (in_array($baseRelationName, $allowedRelationBaseNames)) {
                    $isRelation = true;
                }
            }

            if ($isRelation) {
                $this->applyRelationFilter($query, $filterKey, $value);
            } elseif (in_array($filterKey, $allowedAttributes)) {
                $this->applyDirectFilter($query, $filterKey, $value);
            }
        }

        return $query;
    }

    protected function getFilterRequestKey(): string
    {
        return property_exists($this, 'filterRequestKeyOverride') ? $this->filterRequestKeyOverride : $this->filterRequestKey;
    }

    protected function getFilterableAttributes(): array
    {
        if (!property_exists($this, 'filterable') || !is_array($this->filterable)) {
            return [];
        }
        return $this->filterable;
    }

    protected function getFilterableRelations(): array
    {
        if (!property_exists($this, 'filterableRelations') || !is_array($this->filterableRelations)) {
            return [];
        }
        return $this->filterableRelations;
    }

    protected function applyDirectFilter(Builder $query, string $filterAttribute, $value): void
    {
        $this->applyWhereConditions($query, $filterAttribute, $value);
    }

    protected function applyRelationFilter(Builder $query, string $relationFilterKey, $value): void
    {
        $parts = explode('.', $relationFilterKey);
        $attributeName = array_pop($parts);
        $relationPath = implode('.', $parts);

        $filterLogic = function (Builder $relationQuery) use ($attributeName, $value) {
            $this->applyWhereConditions($relationQuery, $attributeName, $value);
        };

        $query->whereHas($relationPath, $filterLogic);
        $query->with([$relationPath => $filterLogic]);
    }

    protected function applyWhereConditions(Builder $query, string $field, $value): void
    {
        if (is_array($value)) {
            $query->where(function (Builder $subQuery) use ($field, $value) {
                foreach ($value as $operator => $operand) {
                    $operand = urldecode($operand);
                    switch (strtolower(trim($operator))) {
                        case 'equal': case '=':
                            $subQuery->where($field, '=', $operand);
                            break;
                        case 'notequal': case '!=': case '<>':
                            $subQuery->where($field, '!=', $operand);
                            break;
                        case 'gt': case '>':
                            $subQuery->where($field, '>', $operand);
                            break;
                        case 'gte': case '>=':
                            $subQuery->where($field, '>=', $operand);
                            break;
                        case 'lt': case '<':
                            $subQuery->where($field, '<', $operand);
                            break;
                        case 'lte': case '<=':
                            $subQuery->where($field, '<=', $operand);
                            break;
                        case 'like':
                            $subQuery->where($field, 'like', '%' . $operand . '%');
                            break;
                        case 'notlike':
                             $subQuery->where($field, 'not like', '%' . $operand . '%');
                             break;
                        case 'startswith':
                            $subQuery->where($field, 'like', $operand . '%');
                            break;
                        case 'endswith':
                            $subQuery->where($field, 'like', '%' . $operand);
                            break;
                        case 'in':
                            $actualValues = is_array($operand) ? $operand : explode(',', $operand);
                            $subQuery->whereIn($field, array_map('urldecode', $actualValues));
                            break;
                        case 'notin':
                            $actualValues = is_array($operand) ? $operand : explode(',', $operand);
                            $subQuery->whereNotIn($field, array_map('urldecode', $actualValues));
                            break;
                        case 'between':
                            if (is_array($operand) && count($operand) == 2) {
                                $subQuery->whereBetween($field, [urldecode($operand[0]), urldecode($operand[1])]);
                            }
                            break;
                        case 'notbetween':
                             if (is_array($operand) && count($operand) == 2) {
                                 $subQuery->whereNotBetween($field, [urldecode($operand[0]), urldecode($operand[1])]);
                             }
                             break;
                        case 'null':
                            $subQuery->whereNull($field);
                            break;
                        case 'notnull':
                            $subQuery->whereNotNull($field);
                            break;
                        default:
                            break;
                    }
                }
            });
        } else {
            $query->where($field, 'like', '%' . urldecode($value) . '%');
        }
    }
}
