# Laravel Filterable

![usermp-laravel-filterable](https://banners.beyondco.de/Laravel%20Filterable.png?theme=light&packageManager=composer+require&packageName=usermp%2Flaravel-filter&pattern=bankNote&style=style_1&description=The+Filterable+trait+is+designed+to+be+used+within+Eloquent+models+in+a+Laravel+application.&md=1&showWatermark=0&fontSize=100px&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg)

## Overview

The `Filterable` trait is designed to be used within Eloquent models in a Laravel application. It provides a convenient way to apply filters to Eloquent queries based on HTTP request parameters. This trait supports filtering by model attributes as well as by attributes of related models, using various operators.

## Installation

1.  **Add the package to your project using Composer:**

    ```bash
    composer require usermp/laravel-filter
    ```

2.  **Add the `Filterable` trait to your Eloquent model:**

    ```php
    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Usermp\LaravelFilter\Traits\Filterable; // Correct path to the Trait

    class YourModel extends Model
    {
        use Filterable;

        // (Optional) Override the main request key containing the filters.
        // protected string $filterRequestKeyOverride = 'my_filters'; // Defaults to 'filter'

        // Attributes of this model that can be filtered
        protected $filterable = [
            'attribute1',
            'attribute2',
            'status',
            'created_at',
            // Add other filterable attributes
        ];

        // Names of relations whose attributes can be filtered
        protected $filterableRelations = [
            'relation1', // Example: for filtering relation1.name
            'user',
            // Add other filterable relations
        ];
    }
    ```

## Usage

To use the `Filterable` trait, call the `filter` scope on your model query and pass the `Illuminate\Http\Request` object. Filters should be passed in the query string under a main key, which defaults to `filter`.

**Example in a Controller:**

```php
namespace App\Http\Controllers;

use App\Models\YourModel;
use Illuminate\Http\Request; // Inject the Request object

class YourModelController extends Controller
{
    public function index(Request $request)
    {
        $results = YourModel::query()
            ->filter($request) // Pass the entire Request object
            ->paginate();

        return response()->json($results);
    }
}
```

## Examples

All filter parameters should be nested under a main key in the query string. The default key is `filter`.

### 1. Basic Attribute Filtering

Assume the `Post` model has `title` and `status` in its `$filterable` array.

* **Filter by title (implicitly uses 'like'):**
    `GET /posts?filter[title]=Example Post`
    *(Finds posts where title contains "Example Post")*

* **Filter by status using 'equal' operator:**
    `GET /posts?filter[status][equal]=published`
    *(Finds posts where status is exactly "published")*

* **Filter by creation date using 'gte' (greater than or equal to):**
    `GET /posts?filter[created_at][gte]=2023-01-01`

### 2. Filtering by Related Model Attributes

Assume the `Post` model has `user` in `$filterableRelations` and the `User` model has a `name` attribute.

* **Filter posts by user's name (implicitly 'like'):**
    `GET /posts?filter[user.name]=John Doe`

* **Filter posts by user's email using 'equal':**
    `GET /posts?filter[user.email][equal]=john.doe@example.com`

### 3. Using Specific Operators

* **`in` operator (for multiple values):**
    Filter posts with status 'published' OR 'pending':
    `GET /posts?filter[status][in]=published,pending`
    Alternatively, using array syntax for the `in` values:
    `GET /posts?filter[status][in][]=published&filter[status][in][]=pending`

    Filter posts belonging to users with specific IDs:
    `GET /posts?filter[user.id][in]=1,5,10`

* **`between` operator (for ranges):**
    Filter posts created between two dates:
    `GET /posts?filter[created_at][between][]=2023-01-01&filter[created_at][between][]=2023-12-31`

* **`null` / `notnull` operators:**
    Filter posts where `published_at` is NULL:
    `GET /posts?filter[published_at][null]`

    Filter posts where `updated_at` is NOT NULL:
    `GET /posts?filter[updated_at][notnull]`

### 4. Customizing the Main Filter Key

If you defined `$filterRequestKeyOverride = 'my_query_filters';` in your model:

`GET /posts?my_query_filters[title]=My%20Post`

## Supported Operators

The following operators can be used by specifying them as a key for the filter value:

| Operator     | Query String Example                                                                    | Description                                      |
| :----------- | :-------------------------------------------------------------------------------------- | :----------------------------------------------- |
| (none)       | `filter[title]=word`                                                                    | Default: `LIKE '%word%'` for string values.      |
| `equal`      | `filter[status][equal]=active`                                                          | Exact match (`=`).                               |
| `notequal`   | `filter[status][notequal]=archived`                                                     | Not equal (`!=`).                                |
| `gt`         | `filter[views][gt]=100`                                                                 | Greater than (`>`).                              |
| `gte`        | `filter[views][gte]=100`                                                                | Greater than or equal to (`>=`).                 |
| `lt`         | `filter[stock][lt]=10`                                                                  | Less than (`<`).                                 |
| `lte`        | `filter[stock][lte]=10`                                                                 | Less than or equal to (`<=`).                    |
| `like`       | `filter[description][like]=important`                                                   | `LIKE '%important%'`.                            |
| `notlike`    | `filter[description][notlike]=trivial`                                                  | `NOT LIKE '%trivial%'`.                          |
| `startswith` | `filter[sku][startswith]=ABC`                                                           | `LIKE 'ABC%'`.                                   |
| `endswith`   | `filter[filename][endswith]=.pdf`                                                       | `LIKE '%.pdf'`.                                  |
| `in`         | `filter[id][in]=1,2,3` <br> `filter[id][in][]=1&filter[id][in][]=2`                      | Matches any of the comma-separated values.       |
| `notin`      | `filter[category][notin]=old,deprecated`                                                | Does not match any of the comma-separated values. |
| `between`    | `filter[date][between][]=2023-01-01&filter[date][between][]=2023-01-31`                 | Value is between two specified values.           |
| `notbetween` | `filter[price][notbetween][]=100&filter[price][notbetween][]=200`                       | Value is not between two specified values.       |
| `null`       | `filter[deleted_at][null]`                                                              | Value is NULL.                                   |
| `notnull`    | `filter[confirmed_at][notnull]`                                                         | Value is NOT NULL.                               |

**Note on `in`, `notin`, `between`, `notbetween`:**
For `between` and `notbetween`, the values must be provided as an array in the query string as shown. For `in` and `notin`, values can be a comma-separated string or an array.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).
