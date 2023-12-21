![Laravel Setting Pro](https://sajadsdi.github.io/images/advanced-laravel-repository.jpg)

# Advanced Laravel Repository

Advanced helper for implement repository pattern for Laravel to provides a robust set of tools to enhance your Eloquent
models. Aimed to abstract database layer complexity and allows you to standardize and reuse your database query logics.

## Features

- **Model Agnosticism**: Can be implemented for any Eloquent model.
- **Method Forwarding**: Enables dynamic method calls on the model or query builder.
- **Auto Query Scope** : You can define scope methods on your repository without any param depend on. 
- **Flexible Search**: Search across one or more model attributes easily.
- **Advanced Filtering**: Apply complex filtering logic including range and pattern matching.
- **Dynamic Sorting**: Order by multiple columns with different sorting strategies.
- **Pagination**: Integrate Laravel's pagination with additional query features.

## Installation

To use this package, require it via Composer:

```bash
composer require sajadsdi/laravel-repository
```

After installing, you should extend the main `Repository` class for each Eloquent model you wish to create a repository
for.

## Usage

### Extending the Repository

Create a new repository by extending the `Repository` class:

```php
use Sajadsdi\LaravelRepository\Repository;

class UserRepository extends Repository 
{
    // Implement abstract methods
}
```

### Implementing Abstract Methods

Each child repository must implement the following methods to define the model and its searchable, filterable, and
sortable attributes:

```php
public function getModelName(): string;
public function getSearchable(): array;
public function getFilterable(): array;
public function getSortable(): array;
```

For example:

```php
class UserRepository extends Repository 
{
    public function getModelName(): string 
    {
        return User::class;
    }

    public function getSearchable(): array 
    {
        return ['name', 'email'];
    }
    
    public function getFilterable(): array 
    {
        return ['name', 'email', 'created_at'];
    }
    
    public function getSortable(): array 
    {
        return ['name', 'email', 'created_at'];
    }

    // other methods...
}
```
### Auto Query Scope
In your repository, it is possible to implement scope methods that are independent of any parameters.

for example : 
```php
class UserRepository extends Repository 
{
    // abstract methods...
    
    public function getVerified()
    {
        return $this->whereNotNull('verified_at');
    }
    
    public function getByName(string $name)
    {
        return $this->where('name','LIKE' , '%'.$name.'%');
    }
    
    public function getVerifiedUsersByName(string $name)
    {
        return $this->getVerified()->getByName($name);
    }
    
    // other methods...
}
```

### Searching, Sorting, and Filtering

Execute search, sort, and filter operations:

```php
$userRepo = new UserRepository();

// Search by name or email
$users = $userRepo->search('John')->get();

// Sort by name in descending order
$users = $userRepo->sort('name:desc')->get();

// Filter between IDs
$users = $userRepo->filter('id:1_10')->get();
```

### The filter string :
uses a special syntax, such as:
- `id:1` for equality.
- `price:100_200` for range filtering.
- `status:f_is_null` for checking if a column is NULL.
- `status:f_is_not-null` for checking if a column is NOT NULL,
- `id:f_in_2,3,4` for checking if a column value equal 2 or 3 or 4.
- `id:f_not_in_2,3,4` for checking if a column value not equal 2 and 3 and 4.
- `id:f_not_between_2,6` for checking if a column is not in range 2 to 6.
- `name:f_not_like_john` for like condition.
- `id:f_not_equal_2` for not equal condition.

### Use Multiple Filters and Sort
`@` is used for separating multiple filter and sort conditions.
```php
// multiple sort 
$users = $userRepo->sort('name:desc@id:asc')->get();

// multiple filter
$users = $userRepo->filter('id:1_10@status:f_is_null')->get();
```

### Pagination

Leverage Laravel's pagination with added query capabilities:

```php
$users = $userRepo->search('John')->sort('id:desc')->paginate(10);
```

### Method Naming in Repository Classes
When you need to define a method in your repository with the same name as an Eloquent method, use `$this->query()` to avoid conflicts. This approach allows you to safely leverage Eloquent’s functionality.

For a create method:
```php
public function create(array $data)
{
    // Call the `create` method on the query builder provided by `$this->query()`
    return $this->query()->create($data);
}
```
This will use the query builder’s create method directly.

### Contributing

We welcome contributions from the community to improve and extend this library. If you'd like to contribute, please follow these steps:

1. Fork the repository on GitHub.
2. Clone your fork locally.
3. Create a new branch for your feature or bug fix.
4. Make your changes and commit them with clear, concise commit messages.
5. Push your changes to your fork on GitHub.
6. Submit a pull request to the main repository.

### Reporting Bugs and Security Issues

If you discover any security vulnerabilities or bugs in this project, please let us know through the following channels:

- **GitHub Issues**: You can [open an issue](https://github.com/sajadsdi/laravel-repository/issues) on our GitHub repository to report bugs or security concerns. Please provide as much detail as possible, including steps to reproduce the issue.

- **Contact**: For sensitive security-related issues, you can contact us directly through the following contact channels

### Contact

If you have any questions, suggestions, financial, or if you'd like to contribute to this project, please feel free to contact the maintainer:

- Email: thunder11like@gmail.com

We appreciate your feedback, support, and any financial contributions that help us maintain and improve this project.


## License

The Advanced Laravel Repository Package is open-sourced software licensed under the [MIT license](LICENSE).
