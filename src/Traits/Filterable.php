<?php
namespace Usermp\LaravelFilter\Traits;

use Illuminate\Support\Facades\Request;
use Illuminate\Contracts\Database\Eloquent\Builder;


trait Filterable
{

    public function scopeFilter(Builder $query)
    {
        $filters = Request::all();
        foreach ($filters as $filter => $value) {
        	$value = urldecode($value);
            if (in_array($filter, $this->filterable ?? [])) {
                if (is_array($value)) {
                    $query->whereIn($filter, 'like' , '%'.$value.'%');
                }else {
                    $query->where($filter, 'like', "%$value%");
                }
            }
        }

        return $query;
    }
}