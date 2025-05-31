# Laravel Filterable

![usermp-laravel-filterable](https://banners.beyondco.de/Laravel%20Filterable.png?theme=light&packageManager=composer+require&packageName=usermp%2Flaravel-filter&pattern=bankNote&style=style_1&description=The+Filterable+trait+is+designed+to+be+used+within+Eloquent+models+in+a+Laravel+application.&md=1&showWatermark=0&fontSize=100px&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg)


## Overview

The `Filterable` trait is designed to be used within Eloquent models in a Laravel application. It provides a convenient way to apply filters to Eloquent queries based on HTTP request parameters. This trait supports filtering by model attributes as well as by related models.

## Installation

1. **Add the package to your project using Composer:**

```bash
composer require usermp/laravel-filter:dev-master
```

2. **Add the `Filterable` trait to your Eloquent model:**

```php
use Usermp\LaravelFilter\Traits\Filterable;

class YourModel extends Model
{
    use Filterable;

    // Define the attributes that can be filtered
    protected $filterable = [
        'attribute1',
        'attribute2',
        // Add other filterable attributes
    ];

    // Define the relationships that can be filtered
    protected $filterableRelations = [
        'relation1',
        'relation2',
        // Add other filterable relations
    ];
}
```

## Usage

To use the `Filterable` trait, simply call the `filter` scope on your model query and pass the HTTP request parameters.

```php
use App\Models\YourModel;

$filteredResults = YourModel::filter()->get();
```

The trait will automatically process the request parameters, apply the relevant filters to the query, and return the filtered results.

## Examples

### Single Select Filtering

Assume you have a `Post` model with `title` and `content` as filterable attributes, and a `User` relation as a filterable relation.

```php
use Usermp\LaravelFilter\Traits\Filterable;

class Post extends Model
{
    use Filterable;

    protected $filterable = ['title', 'content'];
    protected $filterableRelations = ['user'];
}
```

You can filter posts by title, content, or user attributes using the following HTTP request parameters:

```bash
GET /posts?title=example&user.name=john
```

This request will filter posts with a title containing "example" and related user names containing "john".

### Multi-Select Filtering

Assume you want to filter posts by multiple categories. The `categories` attribute is an array of category IDs.

```php
use Usermp\LaravelFilter\Traits\Filterable;

class Post extends Model
{
    use Filterable;

    protected $filterable = ['title', 'content', 'categories'];
    protected $filterableRelations = ['user'];
}
```

You can filter posts by multiple categories using the following HTTP request parameters:

```bash
GET /posts?categories[]=1&categories[]=2&categories[]=3
```

This request will filter posts that belong to categories with IDs 1, 2, and 3.

### Filtering by Related Models with Multi-Select

You can also filter related models using multi-select. For instance, filtering posts by multiple user roles:

```php
use Usermp\LaravelFilter\Traits\Filterable;

class Post extends Model
{
    use Filterable;

    protected $filterable = ['title', 'content'];
    protected $filterableRelations = ['user'];
}
```

Filter posts by users with multiple roles:

```bash
GET /posts?user---role['equal']=admin
```

This request will filter posts authored by users who have either the "admin".

## License

This package is open-sourced software licensed under the MIT license.
